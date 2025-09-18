<?php require_once __DIR__ . '/config/auth.php'; if (is_logged_in()) { header('Location: ' . url('dashboard.php')); exit; }
$error=''; if($_SERVER['REQUEST_METHOD']==='POST'){ $username=trim($_POST['username']??''); $password=$_POST['password']??''; if(!$username||!$password)$error='Please enter username and password.'; else if(login($username,$password)){ header('Location: '.url('dashboard.php')); exit; } else $error='Invalid credentials.'; }
include __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-6"><div class="card p-4">
<h3 class="mb-3">Sign in</h3><?php if($error):?><div class="alert alert-danger"><?php echo h($error);?></div><?php endif;?>
<form method="post"><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
<div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
<div class="d-flex justify-content-between align-items-center"><a href="<?php echo url('install.php'); ?>" class="link-primary">First time setup?</a><button class="btn btn-primary">Login</button></div></form>
</div></div></div><?php include __DIR__ . '/includes/footer.php'; ?>