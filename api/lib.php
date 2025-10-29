<?php
// Lightweight helpers for JSON IO, validation, ICS generation, and email delivery.

$__APP_CONFIG = require __DIR__ . '/config.php';

function api_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function api_error(string $message, int $status = 400): void {
    api_json([ 'error' => $message ], $status);
}

function read_json_file(string $path, $default) {
    if (!file_exists($path)) {
        return $default;
    }
    $content = file_get_contents($path);
    if ($content === false || $content === '') {
        return $default;
    }
    $data = json_decode($content, true);
    return $data === null ? $default : $data;
}

function write_json_file(string $path, $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmp, $json) === false) return false;
    return rename($tmp, $path);
}

function get_request_json(): array {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function generate_uuid(): string {
    // RFC 4122 version 4 UUID using random_bytes
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function valid_phone_10(string $phone): bool {
    return preg_match('/^[0-9]{10}$/', $phone) === 1;
}

function iso_date(string $date): ?DateTimeImmutable {
    try {
        return new DateTimeImmutable($date . 'T00:00:00');
    } catch (Throwable $e) {
        return null;
    }
}

function today_midnight(): DateTimeImmutable {
    $now = new DateTimeImmutable('now');
    return $now->setTime(0, 0, 0);
}

function build_ics(array $appointment): string {
    // Minimal ICS content for a 30-minute slot in local time
    $dt = new DateTimeImmutable($appointment['date'] . ' ' . $appointment['slot']);
    $end = $dt->modify('+30 minutes');
    $fmt = fn(DateTimeImmutable $d) => $d->format('Ymd\THis');

    $uid = generate_uuid() . '@drrootsdc.in';
    $summary = 'Dental Appointment - Dr. Roots Dental Clinic';
    $desc = 'Appointment with Dr. Roots Dental Clinic\n' .
            'Reason: ' . ($appointment['reason'] ?? 'General consultation') . "\n" .
            'Patient: ' . $appointment['name'];

    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Dr Roots Dental Clinic//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $fmt(new DateTimeImmutable('now')),
        'DTSTART:' . $fmt($dt),
        'DTEND:' . $fmt($end),
        'SUMMARY:' . $summary,
        'DESCRIPTION:' . str_replace(["\r", "\n"], ['\\n', '\\n'], $desc),
        'LOCATION:Dr. Roots Dental Clinic, Palakkad, Kerala',
        'STATUS:CONFIRMED',
        'END:VEVENT',
        'END:VCALENDAR',
    ];
    return implode("\r\n", $lines) . "\r\n";
}

function log_email(string $to, string $subject, string $html, ?string $icsContent = null): void {
    $logPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'email.log';
    $entry = str_repeat('=', 80) . "\n";
    $entry .= 'EMAIL (logged, not sent)' . "\n";
    $entry .= 'To: ' . $to . "\n";
    $entry .= 'Subject: ' . $subject . "\n";
    $entry .= 'Body: ' . $html . "\n";
    if ($icsContent !== null) {
        $entry .= 'Attachment: appointment.ics (' . strlen($icsContent) . " bytes)\n";
    }
    $entry .= str_repeat('=', 80) . "\n";
    file_put_contents($logPath, $entry, FILE_APPEND);
}

function send_email_mail_function(string $to, string $subject, string $html, ?string $icsContent = null): bool {
    global $__APP_CONFIG;
    $from = $__APP_CONFIG['MAIL_FROM'] ?? 'noreply@localhost';
    $fromName = $__APP_CONFIG['MAIL_FROM_NAME'] ?? 'Website';
    $replyTo = $__APP_CONFIG['MAIL_REPLY_TO'] ?? $from;

    // Encode non-ASCII safely for mail headers (RFC 2047)
    $needs_encoding = function (string $s): bool { return preg_match('/[^\x20-\x7E]/', $s) === 1; };
    $mime_encode = function (string $s): string {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($s, 'UTF-8', 'B', "\r\n");
        }
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    };
    $encodedSubject = $needs_encoding($subject) ? $mime_encode($subject) : $subject;
    $encodedFromName = $needs_encoding($fromName) ? $mime_encode($fromName) : $fromName;

    $boundary = 'b_' . bin2hex(random_bytes(8));
    $boundaryAlt = 'ba_' . bin2hex(random_bytes(8));

    $headers = [];
    $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($encodedFromName), $from);
    $headers[] = 'Reply-To: ' . $replyTo;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body = [];
    $body[] = '--' . $boundary;
    $body[] = 'Content-Type: multipart/alternative; boundary="' . $boundaryAlt . '"';
    $body[] = '';
    // Plaintext part (fallback)
    $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ["\n","\n","\n"], $html));
    $body[] = '--' . $boundaryAlt;
    $body[] = 'Content-Type: text/plain; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = '';
    $body[] = $plain;
    // HTML part
    $body[] = '--' . $boundaryAlt;
    $body[] = 'Content-Type: text/html; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = '';
    $body[] = $html;
    $body[] = '--' . $boundaryAlt . '--';

    // ICS attachment
    if ($icsContent !== null) {
        $filename = 'appointment.ics';
        $body[] = '--' . $boundary;
        $body[] = 'Content-Type: text/calendar; name="' . $filename . '"; method=REQUEST; charset=UTF-8';
        $body[] = 'Content-Transfer-Encoding: base64';
        $body[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
        $body[] = '';
        $body[] = chunk_split(base64_encode($icsContent));
    }

    $body[] = '--' . $boundary . '--';

    $headersStr = implode("\r\n", $headers);
    $bodyStr = implode("\r\n", $body);

    // Some providers require '-f' to set Return-Path to domain mailbox
    $params = '-f' . $from;
    return @mail($to, $encodedSubject, $bodyStr, $headersStr, $params);
}

function send_email(string $to, string $subject, string $html, ?string $icsContent = null): void {
    global $__APP_CONFIG;
    $mode = strtolower((string)($__APP_CONFIG['MAIL_DELIVERY'] ?? 'log'));
    if ($mode === 'mail') {
        $ok = send_email_mail_function($to, $subject, $html, $icsContent);
        if (!$ok) {
            // Fallback to log if mail() fails
            log_email($to, $subject, $html, $icsContent);
        }
        return;
    }
    // Default: log only
    log_email($to, $subject, $html, $icsContent);
}

function rate_limit_can_proceed(string $ip, int $windowSeconds = 60): bool {
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ratelimit.json';
    $map = read_json_file($path, []);
    $now = time();
    if (!isset($map[$ip]) || !is_int($map[$ip])) return true;
    return ($now - $map[$ip]) > $windowSeconds;
}

function rate_limit_record(string $ip): void {
    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ratelimit.json';
    $map = read_json_file($path, []);
    $map[$ip] = time();
    write_json_file($path, $map);
}
