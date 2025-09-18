<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);
$id=(int)($_GET['id']??0); $st=$pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); $user=$st->fetch(); if(!$user){ http_response_code(404); echo 'User not found'; exit; }
$msg=''; $ok=false; if($_SERVER['REQUEST_METHOD']==='POST'){ $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $username=trim($_POST['username']??''); $role=$_POST['role']??'staff'; $password=$_POST['password']??''; $phone=trim($_POST['phone']??'');
 try{ if($password){ $hash=password_hash($password,PASSWORD_DEFAULT); $pdo->prepare("UPDATE users SET name=?,email=?,username=?,role=?,phone=?,password_hash=? WHERE id=?")->execute([$name,$email,$username,$role,$phone,$hash,$id]); }
 else{ $pdo->prepare("UPDATE users SET name=?,email=?,username=?,role=?,phone=? WHERE id=?")->execute([$name,$email,$username,$role,$phone,$id]); } log_event('user_update','user',$id,null); $ok=true; $msg='User updated.'; }catch(Exception $e){ $msg='Error: '.h($e->getMessage()); }
 $st->execute([$id]); $user=$st->fetch(); }
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Edit User</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<form method="post" class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo h($user['name']);?>" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo h($user['email']);?>" required></div>
<div class="col-md-4"><label class="form-label">Username</label><input class="form-control" name="username" value="<?php echo h($user['username']);?>" required></div>
<div class="col-md-4"><label class="form-label">Role</label><select class="form-select" name="role"><?php foreach(['staff','manager','admin'] as $r):?><option value="<?php echo $r; ?>" <?php echo $user['role']===$r?'selected':''; ?>><?php echo $r; ?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo h($user['phone']);?>"></div>
<div class="col-md-6"><label class="form-label">Password (leave blank to keep)</label><input type="password" class="form-control" name="password"></div>
<div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save</button></div></form></div><?php include __DIR__ . '/../includes/footer.php'; ?>