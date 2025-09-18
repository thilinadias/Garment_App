<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);

// Handle delete
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])){
  $id=(int)$_POST['delete_id']; $pdo->prepare("DELETE FROM chart_configs WHERE id=?")->execute([$id]);
  log_event('chart_delete','chart',$id,null);
}

// Handle reorder (bulk sort_order update)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['orders']) && is_array($_POST['orders'])){
  foreach($_POST['orders'] as $id=>$order){
    $id=(int)$id; $order=(int)$order;
    $pdo->prepare("UPDATE chart_configs SET sort_order=? WHERE id=?")->execute([$order,$id]);
  }
  log_event('charts_reorder','chart',null,['count'=>count($_POST['orders'])]);
}

$rows=$pdo->query("SELECT * FROM chart_configs ORDER BY sort_order, id")->fetchAll();
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Dashboard Charts</h4>
    <div class="d-flex gap-2">
      <a class="btn btn-primary btn-sm" href="<?php echo url('analytics/custom.php'); ?>">New Custom Chart</a>
    </div>
  </div>
  <form method="post">
  <div class="table-responsive">
    <table class="table table-sm align-middle"><thead><tr><th>ID</th><th>Title</th><th>Dim</th><th>Metric</th><th>Type</th><th style="width:140px">Order</th><th>Added</th><th class="text-end">Actions</th></tr></thead><tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?php echo (int)$r['id']; ?></td>
        <td><?php echo h($r['title']); ?></td>
        <td><?php echo h($r['dim']); ?></td>
        <td><?php echo h($r['metric']); ?></td>
        <td><?php echo h($r['type']); ?></td>
        <td><input type="number" class="form-control form-control-sm" name="orders[<?php echo (int)$r['id']; ?>]" value="<?php echo (int)$r['sort_order']; ?>"></td>
        <td><?php echo h($r['created_at']); ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="<?php echo url('analytics/edit.php'); ?>?id=<?php echo (int)$r['id']; ?>">Edit</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Remove this chart from Dashboard?');">
            <input type="hidden" name="delete_id" value="<?php echo (int)$r['id']; ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="d-flex justify-content-end"><button class="btn btn-success btn-sm">Save Order</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
