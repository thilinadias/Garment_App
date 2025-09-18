<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/app.php';
$u = current_user();
$logo = app_setting('company_logo');
$company = app_setting('company_name') ?: 'Garment App';
$header_text = app_setting('header_text');
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo h($company); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4 bg-primary shadow-sm"><div class="container-fluid">
  <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo url('dashboard.php'); ?>">
    <?php if ($logo): ?><img src="<?php echo url(h($logo)); ?>" alt="logo" style="height:28px"><?php endif; ?><span><?php echo h($company); ?></span>
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="nav">
    <?php if ($u): ?>
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item"><a class="nav-link" href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Jobs</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?php echo url('jobs/index.php'); ?>">All Jobs</a></li>
          <li><a class="dropdown-item" href="<?php echo url('jobs/create.php'); ?>">New Job</a></li>
          <li><a class="dropdown-item" href="<?php echo url('import/index.php'); ?>">Bulk Upload</a></li>
        </ul>
      </li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Factories</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?php echo url('factories/index.php'); ?>">All Factories</a></li>
          <li><a class="dropdown-item" href="<?php echo url('factories/create.php'); ?>">Add Factory</a></li>
        </ul>
      </li>
      <li class="nav-item"><a class="nav-link" href="<?php echo url('styles/index.php'); ?>">Styles</a></li>
      <li class="nav-item"><a class="nav-link" href="<?php echo url('reports/index.php'); ?>">Reports</a></li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Analytics</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?php echo url('analytics/custom.php'); ?>">Custom Chart</a></li>
          <?php if($u && ($u['role']==='admin'||$u['role']==='manager')): ?>
          <li><a class="dropdown-item" href="<?php echo url('analytics/manage.php'); ?>">Manage Charts</a></li>
          <?php endif; ?>
        </ul>
      </li>
      <?php if ($u['role']==='admin' || $u['role']==='manager'): ?>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Users</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?php echo url('users/index.php'); ?>">User Management</a></li>
          <li><a class="dropdown-item" href="<?php echo url('users/create.php'); ?>">Add User</a></li>
        </ul>
      </li><?php endif; ?>
      <?php if ($u['role']==='admin'): ?>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="<?php echo url('settings/index.php'); ?>">Branding & Settings</a></li>
          <li><a class="dropdown-item" href="<?php echo url('admin/logs.php'); ?>">Application Logs</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo url('admin/seed_demo.php'); ?>">Seed Demo Data</a></li>
          <li><a class="dropdown-item" href="<?php echo url('admin/migrate_5_1.php'); ?>">Run Migrations</a></li>
        </ul>
      </li><?php endif; ?>
    </ul>
    <div class="d-flex align-items-center gap-3">
      <a class="nav-link" href="<?php echo url('profile.php'); ?>">My Profile</a>
      <span class="text-white-50">Hi <strong><?php echo h($u['name'] ?: $u['username']); ?></strong> (<?php echo h($u['role']); ?>)</span>
      <a class="btn btn-light btn-sm" href="<?php echo url('logout.php'); ?>">Logout</a>
    </div>
    <?php else: ?><div class="ms-auto"><a class="btn btn-light" href="<?php echo url('login.php'); ?>">Login</a></div><?php endif; ?>
  </div>
</div></nav>
<?php if (!empty($header_text)): ?><div class="container"><div class="alert alert-info py-2 small"><?php echo nl2br(h($header_text)); ?></div></div><?php endif; ?>
<div class="container mb-5">
