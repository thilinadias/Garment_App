<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);
$id=(int)($_GET['id']??0); $st=$pdo->prepare("SELECT * FROM chart_configs WHERE id=?"); $st->execute([$id]); $cfg=$st->fetch();
if(!$cfg){ http_response_code(404); echo 'Chart not found'; exit; }
$msg=''; $ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title=trim($_POST['title']??$cfg['title']); $dim=$_POST['dim']??$cfg['dim']; $metric=$_POST['metric']??$cfg['metric']; $type=$_POST['type']??$cfg['type']; $order=(int)($_POST['sort_order']??$cfg['sort_order']);
  $pdo->prepare("UPDATE chart_configs SET title=?, dim=?, metric=?, type=?, sort_order=? WHERE id=?")->execute([$title,$dim,$metric,$type,$order,$id]);
  log_event('chart_update','chart',$id,['title'=>$title,'dim'=>$dim,'metric'=>$metric,'type'=>$type,'order'=>$order]);
  $ok=true; $msg='Chart updated.'; $st->execute([$id]); $cfg=$st->fetch();
}
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Edit Chart</h4><a class="btn btn-outline-secondary btn-sm" href="<?php echo url('analytics/manage.php'); ?>">Back</a></div>
  <?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>
  <form method="post" class="row g-3">
    <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" value="<?php echo h($cfg['title']); ?>" required></div>
    <div class="col-md-3"><label class="form-label">Order</label><input type="number" class="form-control" name="sort_order" value="<?php echo (int)$cfg['sort_order']; ?>"></div>
    <div class="col-md-3"><label class="form-label">Chart Type</label>
      <select class="form-select" name="type"><?php foreach(['bar','pie','doughnut','line'] as $t):?><option value="<?php echo $t; ?>" <?php echo $cfg['type']===$t?'selected':''; ?>><?php echo $t; ?></option><?php endforeach;?></select>
    </div>
    <div class="col-md-4"><label class="form-label">Dimension</label>
      <select class="form-select" name="dim"><?php foreach(['status','factory','style','month'] as $d):?><option value="<?php echo $d; ?>" <?php echo $cfg['dim']===$d?'selected':''; ?>><?php echo $d; ?></option><?php endforeach;?></select>
    </div>
    <div class="col-md-4"><label class="form-label">Metric</label>
      <select class="form-select" name="metric"><?php foreach(['jobs','cut_qty','out_qty'] as $m):?><option value="<?php echo $m; ?>" <?php echo $cfg['metric']===$m?'selected':''; ?>><?php echo $m; ?></option><?php endforeach;?></select>
    </div>
    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
