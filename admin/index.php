<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/lib.php';
require_once __DIR__ . '/../api/db.php';

session_name('drroots_admin');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

function is_logged_in(): bool { return isset($_SESSION['user']); }
function require_login(): void { if (!is_logged_in()) { header('Location: /admin/login'); exit; } }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check_csrf(): void { if ($_SERVER['REQUEST_METHOD']==='POST') { $t = $_POST['csrf'] ?? ''; if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(400); echo 'Invalid CSRF token'; exit; } } }
function require_admin(): void {
    $isAdmin = is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
    if ($isAdmin) return;
    // For GET requests, redirect to dashboard with a flash message instead of a hard 403
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['flash'] = [ 'type' => 'warning', 'msg' => 'You need admin access to view that page.' ];
        header('Location: /admin');
        exit;
    }
    // For POST/others, keep 403 to avoid masking failed actions
    http_response_code(403); echo 'Forbidden'; exit;
}

function audit_log(array $actor, string $action, string $entityType, string $entityId, array $meta = []): void {
    // Try DB first (activity_log); fall back to file.
    try {
        static $dbChecked = false; static $useDb = false;
        if (!$dbChecked) {
            $pdo = db_pdo();
            $exists = $pdo->query("SHOW TABLES LIKE 'activity_log'")->fetch();
            $useDb = (bool)$exists; $dbChecked = true;
        }
        if ($useDb) {
            $pdo = db_pdo();
            $createdAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            // include actor_email inside meta for convenience
            if (!isset($meta['actor_email']) && isset($actor['email'])) $meta['actor_email'] = $actor['email'];
            $ins = $pdo->prepare('INSERT INTO activity_log (actor_id, action, entity_type, entity_id, meta, created_at) VALUES (?,?,?,?,?,?)');
            $ins->execute([
                $actor['id'] ?? null,
                $action,
                $entityType,
                $entityId,
                json_encode($meta, JSON_UNESCAPED_SLASHES),
                $createdAt,
            ]);
            return;
        }
    } catch (Throwable $e) { /* fall back to file */ }

    $logPath = dirname(__DIR__) . '/data/audit.log';
    $record = [
        'ts' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        'actor_id' => $actor['id'] ?? null,
        'actor_email' => $actor['email'] ?? null,
        'action' => $action,
        'entity' => $entityType,
        'entity_id' => $entityId,
        'meta' => $meta,
    ];
    file_put_contents($logPath, json_encode($record, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
// Normalize trailing slashes so /admin/ behaves like /admin
if ($path !== '/') { $path = rtrim($path, '/'); }

// Simple auto-seed helper: creates admin user if users table exists and empty
function ensure_admin_seed(PDO $pdo): void {
    $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$exists) return;
    $count = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch();
    if ($count && (int)$count['c'] === 0) {
        $id = generate_uuid();
        $email = 'admin@local';
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO users (id,email,password_hash,role,created_at) VALUES (?,?,?,?,?)');
        try { $stmt->execute([$id,$email,$hash,'admin',$now]); } catch (Throwable $e) {}
    }
}

// Views
function render($view, $params = []) {
    extract($params);
    include __DIR__ . '/views/_layout_top.php';
    include __DIR__ . '/views/' . $view . '.php';
    include __DIR__ . '/views/_layout_bottom.php';
}

$pdo = db_pdo();
ensure_admin_seed($pdo);

// Routes
if ($path === '/admin/login' && $method === 'GET') {
    render('login', ['csrf' => csrf_token()]);
    return;
}
if ($path === '/admin/login' && $method === 'POST') {
    check_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id,email,password_hash,role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user'] = [ 'id' => $user['id'], 'email' => $user['email'], 'role' => $user['role'] ];
        header('Location: /admin'); exit;
    }
    render('login', ['error' => 'Invalid credentials', 'csrf' => csrf_token()]);
    return;
}
if ($path === '/admin/logout') {
    session_destroy();
    header('Location: /admin/login'); exit;
}

if ($path === '/admin' && $method === 'GET') {
    require_login();
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $pending = $pdo->prepare('SELECT * FROM appointments WHERE status = "pending" ORDER BY date, slot LIMIT 100');
    $pending->execute();
    $todayStmt = $pdo->prepare('SELECT * FROM appointments WHERE date = ? ORDER BY slot');
    $todayStmt->execute([$today]);
    render('dashboard', ['csrf' => csrf_token(), 'pending' => $pending->fetchAll(), 'today' => $todayStmt->fetchAll()]);
    return;
}

// Actions: confirm, cancel, reschedule
if (preg_match('#^/admin/appointments/(.+)/(confirm|cancel)$#', $path, $m) && $method === 'POST') {
    require_login(); check_csrf();
    $id = $m[1]; $action = $m[2];
    $apt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? LIMIT 1');
    $apt->execute([$id]);
    $a = $apt->fetch();
    if (!$a) { http_response_code(404); echo 'Not found'; return; }

    // Prepare calendar UID/sequence
    $uid = $a['calendar_uid'] ?? null; if (!$uid) $uid = generate_uuid() . '@drrootsdc.in';
    $seq = (int)($a['calendar_seq'] ?? 0) + 1;

    if ($action === 'confirm') {
        $pdo->prepare('UPDATE appointments SET status = "confirmed", calendar_uid = ?, calendar_seq = ?, updated_at = NOW() WHERE id = ?')->execute([$uid,$seq,$id]);
        $ics = build_ics_admin($a, $uid, $seq, 'REQUEST');
        $subject = 'Appointment Confirmed — ' . format_appt_display($a['date'], $a['slot']);
        $body = '<p>Dear ' . htmlspecialchars($a['name']) . ',</p><p>Your appointment is confirmed.</p><ul><li><strong>Date/Time:</strong> ' . htmlspecialchars(format_appt_display($a['date'],$a['slot'])) . '</li></ul>';
        send_email($a['email'], $subject, $body, $ics);
        audit_log($_SESSION['user'] ?? [], 'confirm', 'appointment', $id, []);
    } else { // cancel
        $pdo->prepare('UPDATE appointments SET status = "cancelled", calendar_uid = ?, calendar_seq = ?, updated_at = NOW() WHERE id = ?')->execute([$uid,$seq,$id]);
        $ics = build_ics_admin($a, $uid, $seq, 'CANCEL');
        $subject = 'Appointment Cancelled — ' . format_appt_display($a['date'], $a['slot']);
        $body = '<p>Dear ' . htmlspecialchars($a['name']) . ',</p><p>Your appointment has been cancelled. Please contact us to reschedule.</p>';
        send_email($a['email'], $subject, $body, $ics);
        audit_log($_SESSION['user'] ?? [], 'cancel', 'appointment', $id, []);
    }
    header('Location: /admin'); exit;
}

if ($path === '/admin/appointments/reschedule' && $method === 'POST') {
    require_login(); check_csrf();
    $id = (string)($_POST['id'] ?? '');
    $newDate = trim((string)($_POST['date'] ?? ''));
    $newSlot = trim((string)($_POST['slot'] ?? ''));
    if (!$id || !$newDate || !$newSlot) { http_response_code(400); echo 'Missing fields'; return; }
    $apt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? LIMIT 1');
    $apt->execute([$id]);
    $a = $apt->fetch();
    if (!$a) { http_response_code(404); echo 'Not found'; return; }

    // Check slot availability
    $conf = $pdo->prepare('SELECT 1 FROM appointments WHERE date = ? AND slot = ? AND status <> "cancelled" AND id <> ? LIMIT 1');
    $conf->execute([$newDate,$newSlot,$id]);
    if ($conf->fetch()) { http_response_code(409); echo 'Slot taken'; return; }

    $uid = $a['calendar_uid'] ?? (generate_uuid() . '@drrootsdc.in');
    $seq = (int)($a['calendar_seq'] ?? 0) + 1;
    $pdo->prepare('UPDATE appointments SET date = ?, slot = ?, status = "confirmed", calendar_uid = ?, calendar_seq = ?, updated_at = NOW() WHERE id = ?')->execute([$newDate,$newSlot,$uid,$seq,$id]);
    $a['date'] = $newDate; $a['slot'] = $newSlot;
    $ics = build_ics_admin($a, $uid, $seq, 'REQUEST');
    $subject = 'Appointment Rescheduled — ' . format_appt_display($a['date'], $a['slot']);
    $body = '<p>Dear ' . htmlspecialchars($a['name']) . ',</p><p>Your appointment has been rescheduled.</p><ul><li><strong>Date/Time:</strong> ' . htmlspecialchars(format_appt_display($a['date'],$a['slot'])) . '</li></ul>';
    send_email($a['email'], $subject, $body, $ics);
    audit_log($_SESSION['user'] ?? [], 'reschedule', 'appointment', $id, ['date'=>$newDate,'slot'=>$newSlot]);
    header('Location: /admin'); exit;
}

// Fallback: list appointments
if ($path === '/admin/appointments' && $method === 'GET') {
    require_login();
    $status = $_GET['status'] ?? 'pending';
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE status = ? ORDER BY date, slot LIMIT 500');
    $stmt->execute([$status]);
    render('appointments', ['csrf' => csrf_token(), 'rows' => $stmt->fetchAll(), 'status' => $status]);
    return;
}

// Users management (admin only)
if ($path === '/admin/users' && $method === 'GET') {
    require_login(); require_admin();
    $rows = $pdo->query('SELECT id,email,role,created_at FROM users ORDER BY created_at DESC')->fetchAll();
    render('users', ['csrf' => csrf_token(), 'rows' => $rows]);
    return;
}
if ($path === '/admin/users/create' && $method === 'POST') {
    require_login(); require_admin(); check_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $role = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
    $pass = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) { http_response_code(400); echo 'Invalid email or password too short'; return; }
    $id = generate_uuid();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO users (id,email,password_hash,role,created_at) VALUES (?,?,?,?,?)');
    $stmt->execute([$id,$email,$hash,$role,$now]);
    audit_log($_SESSION['user'] ?? [], 'create', 'user', $id, ['email'=>$email,'role'=>$role]);
    header('Location: /admin/users'); exit;
}
if ($path === '/admin/users/reset' && $method === 'POST') {
    require_login(); require_admin(); check_csrf();
    $id = (string)($_POST['id'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    if (!$id || strlen($pass) < 8) { http_response_code(400); echo 'Invalid request'; return; }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([$hash,$id]);
    audit_log($_SESSION['user'] ?? [], 'reset_password', 'user', $id, []);
    header('Location: /admin/users'); exit;
}
if ($path === '/admin/users/role' && $method === 'POST') {
    require_login(); require_admin(); check_csrf();
    $id = (string)($_POST['id'] ?? '');
    $role = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';
    if (!$id) { http_response_code(400); echo 'Invalid request'; return; }
    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role,$id]);
    audit_log($_SESSION['user'] ?? [], 'change_role', 'user', $id, ['role'=>$role]);
    header('Location: /admin/users'); exit;
}
if ($path === '/admin/users/delete' && $method === 'POST') {
    require_login(); require_admin(); check_csrf();
    $id = (string)($_POST['id'] ?? '');
    if (!$id || $id === ($_SESSION['user']['id'] ?? '')) { http_response_code(400); echo 'Invalid request'; return; }
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    audit_log($_SESSION['user'] ?? [], 'delete', 'user', $id, []);
    header('Location: /admin/users'); exit;
}

// Activity page (DB if available, else file) with basic filters
if ($path === '/admin/activity' && $method === 'GET') {
    require_login(); require_admin();
    $actionF = trim((string)($_GET['action'] ?? ''));
    $actorF = trim((string)($_GET['actor'] ?? ''));
    $fromF = trim((string)($_GET['from'] ?? ''));
    $toF   = trim((string)($_GET['to'] ?? ''));

    $events = [];
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'activity_log'")->fetch();
        if ($exists) {
            $clauses = [];
            $params = [];
            if ($actionF !== '') { $clauses[] = 'action = ?'; $params[] = $actionF; }
            if ($actorF !== '') { $clauses[] = "JSON_UNQUOTE(JSON_EXTRACT(meta, '$.actor_email')) = ?"; $params[] = $actorF; }
            if ($fromF !== '') { $clauses[] = 'created_at >= ?'; $params[] = $fromF . ' 00:00:00'; }
            if ($toF !== '')   { $clauses[] = 'created_at <= ?'; $params[] = $toF . ' 23:59:59'; }
            $where = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
            $sql = 'SELECT actor_id, action, entity_type, entity_id, meta, created_at FROM activity_log ' . $where . ' ORDER BY created_at DESC LIMIT 200';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $r) {
                $meta = json_decode((string)$r['meta'], true) ?: [];
                $events[] = [
                    'ts' => $r['created_at'],
                    'actor_id' => $r['actor_id'],
                    'actor_email' => $meta['actor_email'] ?? null,
                    'action' => $r['action'],
                    'entity' => $r['entity_type'],
                    'entity_id' => $r['entity_id'],
                    'meta' => $meta,
                ];
            }
        } else {
            $logPath = dirname(__DIR__) . '/data/audit.log';
            $lines = file_exists($logPath) ? array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -200) : [];
            $raw = array_reverse(array_map(fn($l)=>json_decode($l,true) ?: ['raw'=>$l], $lines));
            // Apply filters in PHP
            foreach ($raw as $e) {
                if (!is_array($e)) continue;
                if ($actionF !== '' && ($e['action'] ?? '') !== $actionF) continue;
                if ($actorF !== '' && ($e['actor_email'] ?? '') !== $actorF) continue;
                if ($fromF !== '' && ($e['ts'] ?? '') < ($fromF . 'T00:00:00')) continue;
                if ($toF !== '' && ($e['ts'] ?? '') > ($toF . 'T23:59:59')) continue;
                $events[] = $e;
            }
        }
    } catch (Throwable $e) {
        $logPath = dirname(__DIR__) . '/data/audit.log';
        $lines = file_exists($logPath) ? array_slice(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -200) : [];
        $events = array_reverse(array_map(fn($l)=>json_decode($l,true) ?: ['raw'=>$l], $lines));
    }
    render('activity', ['events' => $events, 'filters' => [ 'action'=>$actionF, 'actor'=>$actorF, 'from'=>$fromF, 'to'=>$toF ]]);
    return;
}

http_response_code(404);
echo 'Not found';
