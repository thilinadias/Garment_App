<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role('admin');
$msg=''; $ok=false;
try {
  $pdo->exec("
CREATE TABLE IF NOT EXISTS chart_configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  dim ENUM('status','factory','style','month') NOT NULL,
  metric ENUM('jobs','cut_qty','out_qty') NOT NULL,
  type ENUM('bar','pie','doughnut','line') NOT NULL DEFAULT 'bar',
  sort_order INT NOT NULL DEFAULT 100,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chart_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
  $ok=true; $msg='Migration complete. Table chart_configs is ready.';
} catch (Exception $e) { $msg='Migration failed: '.h($e->getMessage()); }
include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Migration</h4><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo $msg; ?></div>
<a class="btn btn-primary" href="<?php echo url('dashboard.php'); ?>">Back to Dashboard</a></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
