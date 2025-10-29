<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Base paths
$root = dirname(__DIR__);
$dataDir = $root . DIRECTORY_SEPARATOR . 'data';

// Helpers to load static config
$doctorsPath = $dataDir . DIRECTORY_SEPARATOR . 'doctors.json';
$timingsPath = $dataDir . DIRECTORY_SEPARATOR . 'timings.json';
$appointmentsPath = $dataDir . DIRECTORY_SEPARATOR . 'appointments.json';
$messagesPath = $dataDir . DIRECTORY_SEPARATOR . 'messages.json';

// Ensure data files exist
if (!file_exists($appointmentsPath)) write_json_file($appointmentsPath, []);
if (!file_exists($messagesPath)) write_json_file($messagesPath, []);

// Router
try {
    // API: GET /api/doctors
    if ($method === 'GET' && preg_match('#^/api/doctors/?$#', $uriPath)) {
        $doctors = read_json_file($doctorsPath, []);
        // Keep consistent order ["1","3","2"] if ids present
        $desired = ['1', '3', '2'];
        usort($doctors, function ($a, $b) use ($desired) {
            $ai = array_search($a['id'] ?? '', $desired, true);
            $bi = array_search($b['id'] ?? '', $desired, true);
            $ai = $ai === false ? PHP_INT_MAX : $ai;
            $bi = $bi === false ? PHP_INT_MAX : $bi;
            return $ai <=> $bi;
        });
        return api_json($doctors);
    }

    // API: GET /api/timings
    if ($method === 'GET' && preg_match('#^/api/timings/?$#', $uriPath)) {
        $timings = read_json_file($timingsPath, []);
        return api_json($timings);
    }

    // Slot configuration (mirrors Node implementation)
    $slotConfig = [
        [ 'dayOfWeek' => 0, 'slots' => [], 'isOpen' => false ], // Sunday
        [ 'dayOfWeek' => 1, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
        [ 'dayOfWeek' => 2, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
        [ 'dayOfWeek' => 3, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
        [ 'dayOfWeek' => 4, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
        [ 'dayOfWeek' => 5, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
        [ 'dayOfWeek' => 6, 'slots' => [ '09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30' ], 'isOpen' => true ],
    ];

    // API: GET /api/slots?date=YYYY-MM-DD
    if ($method === 'GET' && preg_match('#^/api/slots/?$#', $uriPath)) {
        $date = $_GET['date'] ?? '';
        if (!$date) return api_error('Date parameter is required', 400);

        $dateObj = iso_date($date);
        if ($dateObj === null) return api_error('Invalid date', 400);
        $dayOfWeek = (int) $dateObj->format('w'); // 0..6

        $dayConfig = null;
        foreach ($slotConfig as $cfg) {
            if ($cfg['dayOfWeek'] === $dayOfWeek) { $dayConfig = $cfg; break; }
        }
        if (!$dayConfig || !$dayConfig['isOpen']) {
            return api_json([ 'date' => $date, 'slots' => [] ]);
        }

        $appointments = read_json_file($appointmentsPath, []);
        $slots = [];
        foreach ($dayConfig['slots'] as $time) {
            $taken = false;
            foreach ($appointments as $apt) {
                if (($apt['date'] ?? '') === $date && ($apt['slot'] ?? '') === $time && ($apt['status'] ?? '') !== 'cancelled') {
                    $taken = true; break;
                }
            }
            $slots[] = ['time' => $time, 'available' => !$taken];
        }
        return api_json([ 'date' => $date, 'slots' => $slots ]);
    }

    // API: POST /api/appointments
    if ($method === 'POST' && preg_match('#^/api/appointments/?$#', $uriPath)) {
        $body = get_request_json();
        if (!empty($body['website'])) return api_error('Invalid submission', 400);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!rate_limit_can_proceed($ip)) return api_error('Too many requests. Please wait a minute before trying again.', 429);

        // Basic validation
        $name = trim((string)($body['name'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $date = trim((string)($body['date'] ?? ''));
        $slot = trim((string)($body['slot'] ?? ''));
        $reason = isset($body['reason']) ? trim((string)$body['reason']) : null;
        $consent = (bool)($body['consent'] ?? false);

        if (strlen($name) < 2) return api_error('Name must be at least 2 characters', 400);
        if (!valid_phone_10($phone)) return api_error('Please enter a valid 10-digit phone number', 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return api_error('Please enter a valid email address', 400);
        $dateObj = iso_date($date);
        if ($dateObj === null) return api_error('Invalid date', 400);
        if (!$slot) return api_error('Invalid time slot', 400);
        if ($consent !== true) return api_error('You must agree to the terms and conditions', 400);

        $today = today_midnight();
        if ($dateObj < $today) return api_error('Cannot book appointments in the past', 400);
        $max = (new DateTimeImmutable('now'))->modify('+60 days')->setTime(0, 0, 0);
        if ($dateObj > $max) return api_error('Cannot book appointments more than 60 days in advance', 400);

        $appointments = read_json_file($appointmentsPath, []);
        foreach ($appointments as $apt) {
            if (($apt['date'] ?? '') === $date && ($apt['slot'] ?? '') === $slot && ($apt['status'] ?? '') !== 'cancelled') {
                return api_error('This time slot is already booked. Please select another time.', 409);
            }
        }

        $appointment = [
            'id' => generate_uuid(),
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'date' => $date,
            'slot' => $slot,
            'reason' => $reason,
            'consent' => true,
            'createdAt' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
            'status' => 'pending',
        ];
        $appointments[] = $appointment;
        if (!write_json_file($appointmentsPath, $appointments)) {
            return api_error('Failed to persist appointment', 500);
        }

        // Generate ICS and log two emails (clinic + patient)
        $ics = build_ics($appointment);
        $subject = 'New Appointment Request — ' . $appointment['date'] . ' ' . $appointment['slot'] . ' — ' . $appointment['name'];

        // Force subject/date display in friendly format
        $displayWhen = format_appt_display($appointment['date'], $appointment['slot']);
        $subject = 'New Appointment Request — ' . $displayWhen . ' — ' . $appointment['name'];

        $clinicBody = '<h2>New Appointment Request</h2>' .
            '<ul>' .
            '<li><strong>Name:</strong> ' . htmlspecialchars($name) . '</li>' .
            '<li><strong>Phone:</strong> ' . htmlspecialchars($phone) . '</li>' .
            '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>' .
            '<li><strong>Date/Time:</strong> ' . htmlspecialchars($displayWhen) . '</li>' .
            '<li><strong>Reason:</strong> ' . htmlspecialchars($reason ?? 'Not specified') . '</li>' .
            '</ul>';
        $patientBody = '<h2>Appointment Request Confirmation</h2>' .
            '<p>Dear ' . htmlspecialchars($name) . ',</p>' .
            '<p>Thank you for booking an appointment with Dr. Roots Dental Clinic!</p>' .
            '<h3>Appointment Details:</h3>' .
            '<ul>' .
            '<li><strong>Date/Time:</strong> ' . htmlspecialchars($displayWhen) . '</li>' .
            '<li><strong>Reason:</strong> ' . htmlspecialchars($reason ?? 'General consultation') . '</li>' .
            '</ul>' .
            '<p>We will confirm your appointment shortly.</p>';

        send_email('info@drrootsdc.in', $subject, $clinicBody, $ics);
        send_email($email, $subject, $patientBody, $ics);
        rate_limit_record($ip);

        return api_json($appointment, 201);
    }

    // API: POST /api/contact
    if ($method === 'POST' && preg_match('#^/api/contact/?$#', $uriPath)) {
        $body = get_request_json();
        if (!empty($body['website'])) return api_error('Invalid submission', 400);

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!rate_limit_can_proceed($ip)) return api_error('Too many requests. Please wait a minute before trying again.', 429);

        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $phone = isset($body['phone']) ? trim((string)$body['phone']) : null;
        $subject = trim((string)($body['subject'] ?? ''));
        $messageText = trim((string)($body['message'] ?? ''));

        if (strlen($name) < 2) return api_error('Name must be at least 2 characters', 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return api_error('Please enter a valid email address', 400);
        if (strlen($subject) < 5) return api_error('Subject must be at least 5 characters', 400);
        if (strlen($messageText) < 10) return api_error('Message must be at least 10 characters', 400);

        $messages = read_json_file($messagesPath, []);
        $message = [
            'id' => generate_uuid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $messageText,
            'createdAt' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
        ];
        $messages[] = $message;
        if (!write_json_file($messagesPath, $messages)) {
            return api_error('Failed to persist message', 500);
        }

        $bodyHtml = '<h2>New Contact Form Submission</h2>' .
            '<ul>' .
            '<li><strong>Name:</strong> ' . htmlspecialchars($name) . '</li>' .
            '<li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>' .
            '<li><strong>Phone:</strong> ' . htmlspecialchars($phone ?? 'Not provided') . '</li>' .
            '<li><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</li>' .
            '</ul>' .
            '<h3>Message:</h3><p>' . nl2br(htmlspecialchars($messageText)) . '</p>';
        send_email('info@drrootsdc.in', 'Contact Form: ' . $subject, $bodyHtml);
        rate_limit_record($ip);

        return api_json($message, 201);
    }

    // GET /sitemap.xml
    if ($method === 'GET' && preg_match('#^/sitemap\.xml$#', $uriPath)) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $proto . '://' . $host;
        $pages = ['/', '/services', '/doctors', '/gallery', '/contact'];
        header('Content-Type: application/xml');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $today = (new DateTimeImmutable('now'))->format('Y-m-d');
        foreach ($pages as $page) {
            $priority = $page === '/' ? '1.0' : '0.8';
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($base . $page) . "</loc>\n";
            echo '    <lastmod>' . $today . "</lastmod>\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . $priority . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
        return;
    }

    // GET /robots.txt
    if ($method === 'GET' && preg_match('#^/robots\.txt$#', $uriPath)) {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        header('Content-Type: text/plain');
        echo "User-agent: *\nAllow: /\n\nSitemap: {$proto}://{$host}/sitemap.xml\n";
        return;
    }

    // Not found fallback
    return api_error('Not found', 404);
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    return api_error('Internal Server Error', 500);
}
