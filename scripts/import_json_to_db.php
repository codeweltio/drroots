<?php
// Import existing JSON data into MariaDB
// Usage: php scripts/import_json_to_db.php

require_once __DIR__ . '/../api/lib.php';
require_once __DIR__ . '/../api/db.php';

function import_appointments(PDO $pdo, string $jsonPath): void {
    $data = read_json_file($jsonPath, []);
    if (!is_array($data) || empty($data)) {
        echo "No appointments to import from {$jsonPath}\n";
        return;
    }
    $ins = $pdo->prepare('INSERT INTO appointments (id,name,phone,email,date,slot,reason,status,created_at,updated_at,confirmed_by) VALUES (?,?,?,?,?,?,?,?,?,NULL,NULL) ON DUPLICATE KEY UPDATE id = id');
    $n = 0;
    foreach ($data as $apt) {
        try {
            $id = $apt['id'] ?? generate_uuid();
            $name = $apt['name'] ?? '';
            $phone = $apt['phone'] ?? '';
            $email = $apt['email'] ?? '';
            $date = $apt['date'] ?? '';
            $slot = $apt['slot'] ?? '';
            $reason = $apt['reason'] ?? null;
            $status = $apt['status'] ?? 'pending';
            $createdAtIso = $apt['createdAt'] ?? (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);
            $createdAt = (new DateTimeImmutable($createdAtIso))->format('Y-m-d H:i:s');
            $ins->execute([$id,$name,$phone,$email,$date,$slot,$reason,$status,$createdAt]);
            $n++;
        } catch (Throwable $e) {
            echo "Failed to import appointment {$id}: {$e->getMessage()}\n";
        }
    }
    echo "Imported {$n} appointments.\n";
}

function import_messages(PDO $pdo, string $jsonPath): void {
    $data = read_json_file($jsonPath, []);
    if (!is_array($data) || empty($data)) {
        echo "No messages to import from {$jsonPath}\n";
        return;
    }
    $ins = $pdo->prepare('INSERT INTO contact_messages (id,name,email,phone,subject,message,created_at) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id = id');
    $n = 0;
    foreach ($data as $m) {
        try {
            $id = $m['id'] ?? generate_uuid();
            $name = $m['name'] ?? '';
            $email = $m['email'] ?? '';
            $phone = $m['phone'] ?? null;
            $subject = $m['subject'] ?? '';
            $message = $m['message'] ?? '';
            $createdAtIso = $m['createdAt'] ?? (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);
            $createdAt = (new DateTimeImmutable($createdAtIso))->format('Y-m-d H:i:s');
            $ins->execute([$id,$name,$email,$phone,$subject,$message,$createdAt]);
            $n++;
        } catch (Throwable $e) {
            echo "Failed to import message {$id}: {$e->getMessage()}\n";
        }
    }
    echo "Imported {$n} messages.\n";
}

try {
    $pdo = db_pdo();
    $root = dirname(__DIR__);
    import_appointments($pdo, $root . '/data/appointments.json');
    import_messages($pdo, $root . '/data/messages.json');
    echo "Done.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . "\n");
    exit(1);
}

