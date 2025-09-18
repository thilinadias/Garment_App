<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role('admin'); ensure_uploads();
$msg=''; $ok=false; $created=0;
if($_SERVER['REQUEST_METHOD']==='POST'){ try{ $pdo->beginTransaction();
  $pdo->exec("INSERT IGNORE INTO styles (name) VALUES ('Pants'),('Blouse'),('Skirt')");
  $factoryNames=['Alpha Apparels','Beta Clothing','Cotton Line','DenimWorks','StitchPro']; $factoryIds=[];
  foreach($factoryNames as $fn){ $st=$pdo->prepare("SELECT id FROM factories WHERE name=?"); $st->execute([$fn]); $id=$st->fetchColumn(); if(!$id){ $pdo->prepare("INSERT INTO factories (name) VALUES (?)")->execute([$fn]); $id=$pdo->lastInsertId(); } $factoryIds[]=(int)$id; }
  $styleNames=['Pants','Blouse','Skirt']; $styleIds=[]; foreach($styleNames as $sn){ $st=$pdo->prepare("SELECT id FROM styles WHERE name=?"); $st->execute([$sn]); $styleIds[]=(int)$st->fetchColumn(); }
  $statuses=['New','In Progress','On Hold','Canceled','Finished','Billed']; $base=new DateTime(date('Y-01-01'));
  for($i=1;$i<=20;$i++){ $job_number='DEMO-'.str_pad($i,4,'0',STR_PAD_LEFT); $style_id=$styleIds[array_rand($styleIds)]; $cut_date=(clone $base)->add(new DateInterval('P'.rand(0,200).'D'))->format('Y-m-d');
    $cut_qty=rand(100,2000); $fid=$factoryIds[array_rand($factoryIds)]; $out_offset=rand(0,30); $out_date=(new DateTime($cut_date))->add(new DateInterval('P'.$out_offset.'D'))->format('Y-m-d'); $out_qty=rand(80,$cut_qty);
    $status=$statuses[array_rand($statuses)]; $d1=new DateTime($cut_date); $d2=new DateTime($out_date); $date_count=$d1->diff($d2)->days;
    $exists=$pdo->prepare("SELECT COUNT(*) FROM jobs WHERE job_number=?"); $exists->execute([$job_number]); if($exists->fetchColumn()==0){ $pdo->prepare("INSERT INTO jobs (job_number,style_id,cut_date,cut_qty,factory_id,out_date,out_qty,date_count,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)")
      ->execute([$job_number,$style_id,$cut_date,$cut_qty,$fid,$out_date,$out_qty,$date_count,$status,'Demo data']); $jid=$pdo->lastInsertId();
      $b64='iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAIAAACM/r1jAAAADElEQVR4nGNkZGT4//8/AwUACfsC/ln9kJ0AAAAASUVORK5CYII='; $bin=base64_decode($b64); $fn1='uploads/jobs/job'.$jid.'_1.png'; file_put_contents(__DIR__.'/../'.$fn1,$bin);
      $fn2='uploads/jobs/job'.$jid.'_2.png'; file_put_contents(__DIR__.'/../'.$fn2,$bin);
      $pdo->prepare("INSERT INTO job_photos (job_id,filename) VALUES (?,?),(?,?)")->execute([$jid,$fn1,$jid,$fn2]);
      $created++; }
  }
  $pdo->commit(); log_event('seed_demo','admin',null,['created'=>$created]); $ok=true; $msg="Inserted $created demo jobs with photos."; } catch(Exception $e){ $pdo->rollBack(); $msg='Seed failed: '.h($e->getMessage()); }
}
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Seed Demo Data (Admin)</h4><?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
<p class="text-muted">Creates demo factories, styles and jobs with sample photos.</p>
<form method="post" onsubmit="return confirm('Insert demo data?');"><button class="btn btn-warning">Insert Demo Data</button>
<a class="btn btn-outline-primary ms-2" href="<?php echo url('dashboard.php'); ?>">Back to Dashboard</a></form></div><?php include __DIR__ . '/../includes/footer.php'; ?>