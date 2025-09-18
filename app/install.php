<?php
require_once __DIR__ . '/config/db.php';
$ok=false; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $username=trim($_POST['username']??''); $password=$_POST['password']??'';
  if(!$name||!$email||!$username||!$password){ $msg='All fields are required.'; }
  else{
    try{
      $pdo->exec(file_get_contents(__DIR__.'/sql/schema.sql'));
      $pdo->exec("INSERT IGNORE INTO styles (name) VALUES ('Pants'),('Blouse'),('Skirt')");
      $hash=password_hash($password,PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO users (name,email,username,password_hash,role) VALUES (?,?,?,?, 'admin')")->execute([$name,$email,$username,$hash]);
      $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?),(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)")
          ->execute(['company_name','My Garment Co.','footer_text','© '.date('Y').' My Garment Co.']);
      $ok=true; $msg='Setup complete. You can now log in.';
    }catch(Exception $e){ $msg='Setup failed: '.h($e->getMessage()); }
  }
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><title>Install</title></head>
<body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8">
<div class="card p-4">
<h3 class="mb-3">First-time Setup</h3><p class="text-muted">Creates database tables and your first admin user.</p>
<?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo $msg;?></div><?php endif; ?>
<?php if(!$ok):?><form method="post" class="row g-3">
  <div class="col-md-6"><label class="form-label">Admin Name</label><input class="form-control" name="name" required></div>
  <div class="col-md-6"><label class="form-label">Admin Email</label><input type="email" class="form-control" name="email" required></div>
  <div class="col-md-6"><label class="form-label">Admin Username</label><input class="form-control" name="username" required></div>
  <div class="col-md-6"><label class="form-label">Admin Password</label><input type="password" class="form-control" name="password" required></div>
  <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Run Setup</button></div>
</form><?php else: ?><a class="btn btn-success" href="login.php">Go to Login</a><?php endif; ?>
</div></div></div></div></body></html>