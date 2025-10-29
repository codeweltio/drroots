<?php
// Seed an admin user with provided credentials.
// Usage: php scripts/seed_admin.php admin@local admin123

require_once __DIR__ . '/../api/lib.php';
require_once __DIR__ . '/../api/db.php';

$email = $argv[1] ?? 'admin@local';
$pass = $argv[2] ?? 'admin123';

$pdo = db_pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS users (
  id CHAR(36) PRIMARY KEY,
  email VARCHAR(190) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM("admin","staff") NOT NULL DEFAULT "staff",
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "User already exists: {$email}\n"; exit(0);
}

$id = generate_uuid();
$hash = password_hash($pass, PASSWORD_DEFAULT);
$now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
$ins = $pdo->prepare('INSERT INTO users (id,email,password_hash,role,created_at) VALUES (?,?,?,?,?)');
$ins->execute([$id,$email,$hash,'admin',$now]);
echo "Created admin user: {$email}\n";

