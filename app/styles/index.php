<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); $rows=$pdo->query("SELECT * FROM styles ORDER BY name")->fetchAll(); include __DIR__ . '/../includes/header.php'; $me=current_user(); ?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Styles</h4><a class="btn btn-primary btn-sm" href="<?php echo url('styles/create.php'); ?>">Add Style</a></div>
  <div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>Name</th><th></th></tr></thead><tbody>
  <?php foreach($rows as $r):?><tr><td><?php echo (int)$r['id'];?></td><td><?php echo h($r['name']);?></td>
  <td class="text-end"><?php if($me && ($me['role']==='admin'||$me['role']==='manager')):?>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo url('styles/edit.php'); ?>?id=<?php echo (int)$r['id']; ?>">Edit</a>
    <form method="post" action="<?php echo url('styles/delete.php'); ?>" class="d-inline" onsubmit="return confirm('Delete this style?');"><input type="hidden" name="id" value="<?php echo (int)$r['id'];?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
  <?php endif;?></td></tr><?php endforeach; ?></tbody></table></div>
</div><?php include __DIR__ . '/../includes/footer.php'; ?>