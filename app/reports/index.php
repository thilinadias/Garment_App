<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login();
$from=$_GET['from']??''; $to=$_GET['to']??''; $factory_id=$_GET['factory_id']??''; $style_id=$_GET['style_id']??'';
$date_field=$_GET['date_field']??'cut_date';
$allowed=['cut_date'=>'j.cut_date','out_date'=>'j.out_date','created_at'=>'j.created_at'];
if(!isset($allowed[$date_field])) $date_field='cut_date';
$col=$allowed[$date_field];

$params=[]; $where=[];
if($from){ $where[]="$col >= ?"; $params[]=$from; }
if($to){ $where[]="$col <= ?"; $params[]=$to; }
if($factory_id){ $where[]="j.factory_id = ?"; $params[]=(int)$factory_id; }
if($style_id){ $where[]="j.style_id = ?"; $params[]=(int)$style_id; }
$whereSql=$where?('WHERE '.implode(' AND ',$where)):'';

$sql="SELECT j.id,j.job_number,s.name AS style,j.cut_date,j.cut_qty,f.name AS factory,j.out_date,j.out_qty,j.date_count,j.status,j.created_at FROM jobs j LEFT JOIN factories f ON j.factory_id=f.id LEFT JOIN styles s ON j.style_id=s.id $whereSql ORDER BY $col DESC";
$st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
$total_out=0; foreach($rows as $r){ $total_out+=(int)$r['out_qty']; }
$factories=$pdo->query('SELECT id,name FROM factories ORDER BY name')->fetchAll();
$styles=$pdo->query('SELECT id,name FROM styles ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Reports</h4></div>
  <form method="get" class="row g-3 mb-3">
    <div class="col-md-3"><label class="form-label">From</label><input type="date" class="form-control" name="from" value="<?php echo h($from); ?>"></div>
    <div class="col-md-3"><label class="form-label">To</label><input type="date" class="form-control" name="to" value="<?php echo h($to); ?>"></div>
    <div class="col-md-3"><label class="form-label">Date Field</label>
      <select class="form-select" name="date_field">
        <option value="cut_date" <?php echo $date_field==='cut_date'?'selected':''; ?>>Cut Date</option>
        <option value="out_date" <?php echo $date_field==='out_date'?'selected':''; ?>>Out Date</option>
        <option value="created_at" <?php echo $date_field==='created_at'?'selected':''; ?>>Created At</option>
      </select>
      <div class="form-text">Choose which date the filter applies to.</div>
    </div>
    <div class="col-md-3"><label class="form-label">Factory</label><select class="form-select" name="factory_id"><option value="">All</option><?php foreach($factories as $f):?><option value="<?php echo (int)$f['id'];?>" <?php echo $factory_id==$f['id']?'selected':'';?>><?php echo h($f['name']);?></option><?php endforeach;?></select></div>
    <div class="col-md-3"><label class="form-label">Style</label><select class="form-select" name="style_id"><option value="">All</option><?php foreach($styles as $s):?><option value="<?php echo (int)$s['id'];?>" <?php echo $style_id==$s['id']?'selected':'';?>><?php echo h($s['name']);?></option><?php endforeach;?></select></div>
    <div class="col-12 d-flex gap-2">
      <button class="btn btn-outline-primary" type="submit">Run</button>
      <a class="btn btn-primary" href="<?php echo url('reports/export_csv.php'); ?>?from=<?php echo h($from); ?>&to=<?php echo h($to); ?>&factory_id=<?php echo h($factory_id); ?>&style_id=<?php echo h($style_id); ?>&date_field=<?php echo h($date_field); ?>">CSV</a>

    </div>
  </form>
  <div class="table-responsive"><table class="table table-sm">
    <thead><tr><th>ID</th><th>Job #</th><th>Style</th><th>Cut Date</th><th>Cut Qty</th><th>Factory</th><th>Out Date</th><th>Out Qty</th><th>Days</th><th>Status</th><th>Created</th></tr></thead>
    <tbody><?php foreach($rows as $r):?><tr>
      <td><?php echo (int)$r['id'];?></td><td><?php echo h($r['job_number']);?></td><td><?php echo h($r['style']);?></td><td><?php echo h($r['cut_date']);?></td><td><?php echo (int)$r['cut_qty'];?></td>
      <td><?php echo h($r['factory']);?></td><td><?php echo h($r['out_date']);?></td><td><?php echo (int)$r['out_qty'];?></td><td><?php echo (int)$r['date_count'];?></td><td><?php echo h($r['status']);?></td><td><?php echo h($r['created_at']);?></td>
    </tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="7" class="text-end">Total Out Qty</th><th><?php echo (int)$total_out; ?></th><th colspan="3"></th></tr></tfoot>
  </table></div></div><?php include __DIR__ . '/../includes/footer.php'; ?>
