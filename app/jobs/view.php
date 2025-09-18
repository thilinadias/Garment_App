<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login();

function cols(PDO $pdo,$t){ try{ return $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){ return []; } }
function path_candidates(){ return ['filename','path','file_path','photo_path','image_path','url','photo','file','image','img','photo_url','file_url','uri']; }
function path_coalesce_sql(PDO $pdo,$table){
  $cols = cols($pdo,$table); if(!$cols) return 'NULL';
  $present = [];
  foreach(path_candidates() as $c){ if(in_array($c,$cols)) $present[] = "`$c`"; }
  if(!$present) return 'NULL';
  return 'COALESCE('.implode(',', $present).')';
}
function build_src_from_path($raw,$job_id){
  $p = str_replace('\\','/', trim((string)$raw));
  if($p==='') return [null,'none'];
  if(preg_match('~^https?://~i',$p)) return [url($p),'url'];
  $pos = stripos($p,'/uploads/'); if($pos===false) $pos = stripos($p,'uploads/');
  if($pos!==false){ $rel = ltrim(substr($p,$pos),'/'); return [url($rel),'uploads']; }
  $rel = ltrim($p,'/'); if(is_file(__DIR__.'/../'.$rel)) return [url($rel),'relative'];
  $base = basename($p); if($job_id && $base){
    $guess = 'uploads/jobs/'.((int)$job_id).'/'.$base;
    if(is_file(__DIR__.'/../'.$guess)) return [url($guess),'guess'];
  }
  return [null,'viewer'];
}

$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT j.*, s.name AS style, f.name AS factory FROM jobs j LEFT JOIN styles s ON j.style_id=s.id LEFT JOIN factories f ON j.factory_id=f.id WHERE j.id=?");
$st->execute([$id]); $job=$st->fetch(PDO::FETCH_ASSOC);
if(!$job){ http_response_code(404); echo "Job not found"; exit; }

$photos=[];
foreach(['job_photos','job_images','photos','job_files','job_media'] as $t){
  $cols = cols($pdo,$t); if(!$cols) continue;
  $link_col = in_array('job_id',$cols)?'job_id':(in_array('job',$cols)?'job':null);
  $where_val = $link_col==='job_id' ? $id : $job['job_number'];
  if(!$link_col) continue;
  $co = path_coalesce_sql($pdo,$t);
  $sql = "SELECT id, {$co} AS path, ".(in_array('created_at',$cols)?'created_at':'NULL as created_at').", '{$t}' AS _table FROM `{$t}` WHERE `{$link_col}`=? ORDER BY id DESC";
  try{ $ps=$pdo->prepare($sql); $ps->execute([$where_val]); $rows=$ps->fetchAll(PDO::FETCH_ASSOC); if($rows) $photos=array_merge($photos,$rows); }catch(Exception $e){}
}

include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Job #<?php echo h($job['job_number']); ?></h4>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('jobs/index.php'); ?>">Back</a>
  </div>
  <div class="row">
    <div class="col-lg-6">
      <table class="table">
        <tr><th>Style</th><td><?php echo h($job['style'] ?? ''); ?></td></tr>
        <tr><th>Factory</th><td><?php echo h($job['factory'] ?? ''); ?></td></tr>
        <tr><th>Cut Date</th><td><?php echo h($job['cut_date']); ?></td></tr>
        <tr><th>Cut Qty</th><td><?php echo (int)$job['cut_qty']; ?></td></tr>
        <tr><th>Out Date</th><td><?php echo h($job['out_date']); ?></td></tr>
        <tr><th>Out Qty</th><td><?php echo (int)$job['out_qty']; ?></td></tr>
        <tr><th>Date Count</th><td><?php echo (int)$job['date_count']; ?></td></tr>
        <tr><th>Status</th><td><?php echo h($job['status']); ?></td></tr>
        <tr><th>Notes</th><td><?php echo nl2br(h($job['notes'] ?? '')); ?></td></tr>
      </table>
    </div>
    <div class="col-lg-6">
      <h6>Photos</h6>
      <?php if($photos): ?>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach($photos as $p): list($src,$how)=build_src_from_path($p['path'],$id); if(!$src) $src=url('jobs/photo_view.php?id='.(int)$p['id'].'&table='.$p['_table'].'&path_col=__coalesce'); ?>
            <div class="card" style="width: 180px;">
              <a href="<?php echo $src; ?>" target="_blank"><img src="<?php echo $src; ?>" class="card-img-top" style="object-fit:cover; height:160px;"></a>
              <div class="card-body py-2"><small class="text-muted">#<?php echo (int)$p['id']; ?> <em><?php echo h($how); ?></em></small></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?><div class="alert alert-secondary">No photos.</div><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
