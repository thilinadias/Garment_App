<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_uploads();
$factories=$pdo->query("SELECT id,name FROM factories ORDER BY name")->fetchAll();
$styles=$pdo->query("SELECT id,name FROM styles ORDER BY name")->fetchAll();
$msg=''; $ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $job_number=trim($_POST['job_number']??''); $style_id=(int)($_POST['style_id']??0); $cut_date=$_POST['cut_date']??null; $cut_qty=(int)($_POST['cut_qty']??0);
  $factory_id=(int)($_POST['factory_id']??0); $out_date=$_POST['out_date']??null; $out_qty=(int)($_POST['out_qty']??0); $status=$_POST['status']??'New'; $notes=trim($_POST['notes']??'');
  if(!$job_number){ $msg='Job Number is required.'; } else {
    $date_count = ($cut_date && $out_date) ? (new DateTime($cut_date))->diff(new DateTime($out_date))->days : null;
    $pdo->prepare("INSERT INTO jobs (job_number,style_id,cut_date,cut_qty,factory_id,out_date,out_qty,date_count,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$job_number,$style_id?:null,$cut_date?:null,$cut_qty?:null,$factory_id?:null,$out_date?:null,$out_qty?:null,$date_count,$status,$notes]);
    $job_id=$pdo->lastInsertId();
    if (!empty($_FILES['photos']['name'][0])) {
      for($i=0;$i<count($_FILES['photos']['name']);$i++){
        if(is_uploaded_file($_FILES['photos']['tmp_name'][$i])){
          $ext=strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
          if(in_array($ext,['png','jpg','jpeg','webp'])){
            $new='uploads/jobs/job'.$job_id.'_'.time().'_'.$i.'.'.$ext;
            move_uploaded_file($_FILES['photos']['tmp_name'][$i], __DIR__.'/../'.$new);
            $pdo->prepare("INSERT INTO job_photos (job_id,filename) VALUES (?,?)")->execute([$job_id,$new]);
          }
        }
      }
    }
    log_event('job_create','job',$job_id,null);
    $ok=true; $msg='Job created.';
  }
}
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">New Job</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="row g-3">
  <div class="col-md-4"><label class="form-label">Job Number</label><input class="form-control" name="job_number" required></div>
  <div class="col-md-4"><label class="form-label">Style</label><select class="form-select" name="style_id"><option value="">-- Select style --</option><?php foreach($styles as $s):?><option value="<?php echo (int)$s['id'];?>"><?php echo h($s['name']);?></option><?php endforeach;?></select></div>
  <div class="col-md-4"><label class="form-label">Factory</label><select class="form-select" name="factory_id"><option value="">-- Select factory --</option><?php foreach($factories as $f):?><option value="<?php echo (int)$f['id'];?>"><?php echo h($f['name']);?></option><?php endforeach;?></select></div>
  <div class="col-md-3"><label class="form-label">Cut Date</label><input type="date" class="form-control" name="cut_date"></div>
  <div class="col-md-3"><label class="form-label">Cut Qty</label><input type="number" class="form-control" name="cut_qty"></div>
  <div class="col-md-3"><label class="form-label">Out Date</label><input type="date" class="form-control" name="out_date"></div>
  <div class="col-md-3"><label class="form-label">Out Qty</label><input type="number" class="form-control" name="out_qty"></div>
  <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach(['New','In Progress','On Hold','Canceled','Finished','Billed'] as $s):?><option value="<?php echo $s;?>"><?php echo $s;?></option><?php endforeach;?></select></div>
  <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="3" name="notes"></textarea></div>
  <div class="col-12"><label class="form-label">Photos (multiple)</label><input type="file" class="form-control" name="photos[]" multiple accept="image/*"></div>
  <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save</button></div>
</form></div><?php include __DIR__ . '/../includes/footer.php'; ?>