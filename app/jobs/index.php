<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login();
$q=trim($_GET['q']??''); $factory_id=$_GET['factory_id']??''; $style_id=$_GET['style_id']??''; $date_from=$_GET['from']??''; $date_to=$_GET['to']??''; $date_type=$_GET['date_type']??'cut'; $status=$_GET['status']??'';
$params=[]; $where=[];
if($q!==''){ $where[]="j.job_number LIKE ?"; $params[]='%'.$q.'%'; }
if($factory_id!==''){ $where[]="j.factory_id=?"; $params[]=(int)$factory_id; }
if($style_id!==''){ $where[]="j.style_id=?"; $params[]=(int)$style_id; }
if($status!==''){ $where[]="j.status=?"; $params[]=$status; }
if($date_from){ $col=$date_type==='out'?'j.out_date':'j.cut_date'; $where[]="$col >= ?"; $params[]=$date_from; }
if($date_to){ $col=$date_type==='out'?'j.out_date':'j.cut_date'; $where[]="$col <= ?"; $params[]=$date_to; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';
$sql="SELECT j.*, f.name AS factory_name, s.name AS style_name FROM jobs j LEFT JOIN factories f ON j.factory_id=f.id LEFT JOIN styles s ON j.style_id=s.id $whereSql ORDER BY j.id DESC LIMIT 300";
$st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
$factories=$pdo->query("SELECT id,name FROM factories ORDER BY name")->fetchAll(); $styles=$pdo->query("SELECT id,name FROM styles ORDER BY name")->fetchAll();
include __DIR__ . '/../includes/header.php'; $me=current_user(); ?>
<div class="card p-3">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
    <h4 class="mb-0">Jobs</h4>
    <form class="row g-2" method="get">
      <div class="col-auto"><input class="form-control" name="q" placeholder="Job Number" value="<?php echo h($q); ?>"></div>
      <div class="col-auto"><select class="form-select" name="factory_id"><option value="">All Factories</option><?php foreach($factories as $f):?><option value="<?php echo (int)$f['id'];?>" <?php echo $factory_id==$f['id']?'selected':'';?>><?php echo h($f['name']);?></option><?php endforeach;?></select></div>
      <div class="col-auto"><select class="form-select" name="style_id"><option value="">All Styles</option><?php foreach($styles as $s):?><option value="<?php echo (int)$s['id'];?>" <?php echo $style_id==$s['id']?'selected':'';?>><?php echo h($s['name']);?></option><?php endforeach;?></select></div>
      <div class="col-auto"><select class="form-select" name="date_type"><option value="cut" <?php echo $date_type==='cut'?'selected':''; ?>>Cut Date</option><option value="out" <?php echo $date_type==='out'?'selected':''; ?>>Out Date</option></select></div>
      <div class="col-auto"><input type="date" class="form-control" name="from" value="<?php echo h($date_from); ?>"></div>
      <div class="col-auto"><input type="date" class="form-control" name="to" value="<?php echo h($date_to); ?>"></div>
      <div class="col-auto"><select class="form-select" name="status"><option value="">All Status</option><?php foreach(['New','In Progress','On Hold','Canceled','Finished','Billed'] as $s):?><option value="<?php echo $s;?>" <?php echo $status===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach;?></select></div>
      <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
      <div class="col-auto"><a class="btn btn-primary" href="<?php echo url('jobs/create.php'); ?>">New Job</a></div>
    </form>
  </div>
  <div class="table-responsive"><table class="table table-sm align-middle">
    <thead><tr><th>ID</th><th>Job Number</th><th>Style</th><th>Cut Date</th><th>Cut Qty</th><th>Factory</th><th>Out Date</th><th>Out Qty</th><th>Days</th><th>Status</th><th></th></tr></thead>
    <tbody><?php foreach($rows as $r):?><tr>
      <td><?php echo (int)$r['id'];?></td><td><?php echo h($r['job_number']);?></td><td><?php echo h($r['style_name']);?></td><td><?php echo h($r['cut_date']);?></td><td><?php echo (int)$r['cut_qty'];?></td>
      <td><?php echo h($r['factory_name']);?></td><td><?php echo h($r['out_date']);?></td><td><?php echo (int)$r['out_qty'];?></td><td><?php echo (int)$r['date_count'];?></td>
      <td><form method="post" action="<?php echo url('jobs/status.php'); ?>" class="d-flex"><input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()"><?php foreach(['New','In Progress','On Hold','Canceled','Finished','Billed'] as $s):?><option value="<?php echo $s;?>" <?php echo $r['status']===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach;?></select></form></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('jobs/view.php'); ?>?id=<?php echo (int)$r['id']; ?>">View</a>
        <?php if($me && ($me['role']==='admin'||$me['role']==='manager')):?>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('jobs/edit.php'); ?>?id=<?php echo (int)$r['id']; ?>">Edit</a>
        <form method="post" action="<?php echo url('jobs/delete.php'); ?>" class="d-inline" onsubmit="return confirm('Delete this job? This also removes photos.');"><input type="hidden" name="id" value="<?php echo (int)$r['id'];?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
        <?php endif; ?>
      </td></tr><?php endforeach; ?></tbody></table></div>
</div><?php include __DIR__ . '/../includes/footer.php'; ?>