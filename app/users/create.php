<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);
$msg=''; $ok=false; if($_SERVER['REQUEST_METHOD']==='POST'){ $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $username=trim($_POST['username']??''); $role=$_POST['role']??'staff'; $password=$_POST['password']??''; $phone=trim($_POST['phone']??'');
 if(!$name||!$email||!$username||!$password){ $msg='All fields except phone are required.'; } else { try{ $hash=password_hash($password,PASSWORD_DEFAULT); $pdo->prepare("INSERT INTO users (name,email,username,password_hash,role,phone) VALUES (?,?,?,?,?,?)")->execute([$name,$email,$username,$hash,$role,$phone]); log_event('user_create','user',$pdo->lastInsertId(),null); $ok=true; $msg='User created.'; }catch(Exception $e){ $msg='Error: '.h($e->getMessage()); } } }
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Add User</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<form method="post" class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
<div class="col-md-4"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
<div class="col-md-4"><label class="form-label">Role</label><select class="form-select" name="role"><option value="staff">staff</option><option value="manager">manager</option><option value="admin">admin</option></select></div>
<div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
<div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
<div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Create</button></div></form></div><?php include __DIR__ . '/../includes/footer.php'; ?>