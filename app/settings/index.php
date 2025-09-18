<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role('admin'); ensure_uploads();
$msg=''; $ok=false; if($_SERVER['REQUEST_METHOD']==='POST'){ $pairs=['company_name'=>trim($_POST['company_name']??''),'header_text'=>trim($_POST['header_text']??''),'footer_text'=>trim($_POST['footer_text']??''),'company_email'=>trim($_POST['company_email']??''),'company_phone'=>trim($_POST['company_phone']??''),'company_address'=>trim($_POST['company_address']??'')];
 if(!empty($_FILES['company_logo']['name']) && is_uploaded_file($_FILES['company_logo']['tmp_name'])){ $ext=strtolower(pathinfo($_FILES['company_logo']['name'],PATHINFO_EXTENSION)); if(in_array($ext,['png','jpg','jpeg','webp'])){ $new='uploads/logo_'.time().'.'.$ext; move_uploaded_file($_FILES['company_logo']['tmp_name'], __DIR__.'/../'.$new); $pairs['company_logo']=$new; } }
 foreach($pairs as $k=>$v){ $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")->execute([$k,$v]); }
 log_event('settings_save','settings',null,null);
 $ok=true; $msg='Settings saved.'; }
include __DIR__ . '/../includes/header.php'; $fields=['company_name','header_text','footer_text','company_email','company_phone','company_address','company_logo']; $vals=[]; foreach($fields as $k) $vals[$k]=app_setting($k); ?>
<div class="card p-3"><h4 class="mb-3">Branding & Settings (Admin)</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="row g-3">
<div class="col-md-6"><label class="form-label">Company Name</label><input class="form-control" name="company_name" value="<?php echo h($vals['company_name']);?>"></div>
<div class="col-md-6"><label class="form-label">Company Email</label><input class="form-control" name="company_email" value="<?php echo h($vals['company_email']);?>"></div>
<div class="col-md-6"><label class="form-label">Company Phone</label><input class="form-control" name="company_phone" value="<?php echo h($vals['company_phone']);?>"></div>
<div class="col-md-6"><label class="form-label">Company Address</label><input class="form-control" name="company_address" value="<?php echo h($vals['company_address']);?>"></div>
<div class="col-12"><label class="form-label">Header Notice (optional)</label><textarea class="form-control" rows="2" name="header_text"><?php echo h($vals['header_text']);?></textarea></div>
<div class="col-12"><label class="form-label">Footer Text</label><textarea class="form-control" rows="2" name="footer_text"><?php echo h($vals['footer_text']);?></textarea></div>
<div class="col-md-6"><label class="form-label">Company Logo</label><?php if($vals['company_logo']):?><div class="mb-2"><img src="<?php echo url(h($vals['company_logo']));?>" style="height:60px"></div><?php endif;?><input type="file" class="form-control" name="company_logo" accept=".png,.jpg,.jpeg,.webp"></div>
<div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save Settings</button></div>
</form></div><?php include __DIR__ . '/../includes/footer.php'; ?>