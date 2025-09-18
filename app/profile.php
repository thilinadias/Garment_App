<?php require_once __DIR__ . '/config/auth.php'; ensure_login(); $u=current_user(); $msg=''; $ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){ $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $password=$_POST['password']??''; $avatar=$u['avatar'];
 if(!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])){ ensure_uploads(); $ext=strtolower(pathinfo($_FILES['avatar']['name'],PATHINFO_EXTENSION));
  if(in_array($ext,['png','jpg','jpeg','webp'])){ $new='uploads/avatar_'.$u['id'].'.'.$ext; move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__.'/'.$new); $avatar=$new; } }
 try{ if($password){ $hash=password_hash($password,PASSWORD_DEFAULT); $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,avatar=?,password_hash=? WHERE id=?")->execute([$name,$email,$phone,$avatar,$hash,$u['id']]); }
 else{ $pdo->prepare("UPDATE users SET name=?,email=?,phone=?,avatar=? WHERE id=?")->execute([$name,$email,$phone,$avatar,$u['id']]); }
 $_SESSION['__flush_user_cache']=true; $ok=true; $msg='Profile updated.'; }catch(Exception $e){ $msg='Error: '.h($e->getMessage()); } }
include __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center"><div class="col-md-8"><div class="card p-4"><h4 class="mb-3">My Profile</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo h($u['name']);?>" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?php echo h($u['email']);?>" required></div>
<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo h($u['phone']);?>"></div>
<div class="col-md-6"><label class="form-label">New Password (optional)</label><input type="password" class="form-control" name="password"></div>
<div class="col-12"><label class="form-label">Avatar</label><?php if($u['avatar']):?><div class="mb-2"><img src="<?php echo url(h($u['avatar']));?>" style="height:60px;border-radius:8px"></div><?php endif;?><input type="file" class="form-control" name="avatar" accept=".png,.jpg,.jpeg,.webp"></div>
<div class="d-flex justify-content-end"><button class="btn btn-primary">Save</button></div></form>
</div></div></div><?php include __DIR__ . '/includes/footer.php'; ?>