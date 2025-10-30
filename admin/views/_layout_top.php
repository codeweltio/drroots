<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dr. Roots Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/admin/assets/admin.css" />
</head>
<body>
  <header class="header">
    <div class="container">
      <nav class="nav">
        <a href="/admin">Dashboard</a>
        <a href="/admin/appointments?status=pending">Pending</a>
        <a href="/admin/appointments?status=confirmed">Confirmed</a>
        <a href="/admin/appointments?status=cancelled">Cancelled</a>
        <?php $__role = $_SESSION['user']['role'] ?? null; if ($__role === 'admin'): ?>
          <a href="/admin/users">Users</a>
          <a href="/admin/activity">Activity</a>
        <?php endif; ?>
        <span class="right"><a href="/admin/logout">Logout</a></span>
      </nav>
    </div>
  </header>
  <main class="container">
  <?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); $t = $f['type'] ?? 'info'; $msg = htmlspecialchars((string)($f['msg'] ?? '')); $cls = in_array($t,['success','warning','danger','info'], true) ? $t : 'info'; ?>
    <div class="alert alert-<?= $cls ?> alert-dismissible fade show" role="alert" style="margin-top:12px">
      <?= $msg ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
