<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role('admin');

$ok=true; $msgs=[];

try{
  // Ensure table exists
  $pdo->exec("CREATE TABLE IF NOT EXISTS job_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(job_id),
    CONSTRAINT fk_job_photos_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  $msgs[] = 'job_photos table ensured.';

  // If path-like column doesn't exist, add path
  $cols = $pdo->query('SHOW COLUMNS FROM job_photos')->fetchAll(PDO::FETCH_COLUMN);
  if(!in_array('path',$cols) && !in_array('file_path',$cols) && !in_array('photo_path',$cols) && !in_array('image_path',$cols) && !in_array('url',$cols)){
    $pdo->exec("ALTER TABLE job_photos ADD COLUMN path VARCHAR(255) NULL;");
    $msgs[] = 'Added path column to job_photos.';
  }
} catch(Exception $e){
  $ok=false; $msgs[]='Migration error: '.$e->getMessage();
}

include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <h4 class="mb-3">Photo Table Migration</h4>
  <div class="alert <?php echo $ok?'alert-success':'alert-danger';?>">
    <?php foreach($msgs as $m){ echo h($m).'<br>'; } ?>
  </div>
  <a class="btn btn-primary" href="<?php echo url('dashboard.php'); ?>">Back to Dashboard</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
