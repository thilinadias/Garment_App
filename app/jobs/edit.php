<?php
require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']);

/* ---------- helpers ---------- */
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

/* ---------- load job ---------- */
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM jobs WHERE id=?"); $st->execute([$id]); $job = $st->fetch();
if(!$job){ http_response_code(404); echo "Job not found"; exit; }

$msg=''; $ok=false;
function norm_date($v){ $v=trim((string)$v); if($v==='') return null; $dt=DateTime::createFromFormat('Y-m-d',$v); if($dt) return $dt->format('Y-m-d'); $t=strtotime($v); return $t?date('Y-m-d',$t):null; }
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['__edit_job'])){
  $job_number = trim($_POST['job_number'] ?? '');
  if($job_number===''){ $msg='Job Number is required.'; }
  else {
    $style_id=$_POST['style_id']!==''?(int)$_POST['style_id']:null;
    $cut_date=norm_date($_POST['cut_date']??'');
    $cut_qty=$_POST['cut_qty']!==''?(int)$_POST['cut_qty']:null;
    $factory_id=$_POST['factory_id']!==''?(int)$_POST['factory_id']:null;
    $out_date=norm_date($_POST['out_date']??'');
    $out_qty=$_POST['out_qty']!==''?(int)$_POST['out_qty']:null;
    $status=trim($_POST['status']??$job['status']);
    $notes=trim($_POST['notes']??'');
    $date_count=null; if($cut_date&&$out_date){ $d1=DateTime::createFromFormat('Y-m-d',$cut_date); $d2=DateTime::createFromFormat('Y-m-d',$out_date); if($d1&&$d2) $date_count=$d1->diff($d2)->days; }
    $sql="UPDATE jobs SET job_number=?, style_id=?, cut_date=?, cut_qty=?, factory_id=?, out_date=?, out_qty=?, date_count=?, status=?, notes=?, updated_at=NOW() WHERE id=?";
    $ok=$pdo->prepare($sql)->execute([$job_number,$style_id,$cut_date,$cut_qty,$factory_id,$out_date,$out_qty,$date_count,$status,$notes,$id]);
    if($ok){ log_event('job_update','job',$id,['status'=>$status]); $msg='Job updated.'; $st->execute([$id]); $job=$st->fetch(); }
    else { $msg='Update failed.'; }
  }
}

/* ---------- lists ---------- */
$factories=$pdo->query('SELECT id,name FROM factories ORDER BY name')->fetchAll();
$styles=$pdo->query('SELECT id,name FROM styles ORDER BY name')->fetchAll();

/* ---------- photos (coalesce across columns) ---------- */
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
    <h4 class="mb-0">Edit Job #<?php echo (int)$job['id']; ?></h4>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('jobs/index.php'); ?>">Back</a>
  </div>
  <?php if($msg):?><div class="alert <?php echo $ok?'alert-success':'alert-danger';?>"><?php echo h($msg);?></div><?php endif;?>

  <form method="post" class="row g-3">
    <input type="hidden" name="__edit_job" value="1">
    <div class="col-md-3"><label class="form-label">Job Number</label><input class="form-control" name="job_number" value="<?php echo h($job['job_number']); ?>" required></div>
    <div class="col-md-3"><label class="form-label">Style</label>
      <select class="form-select" name="style_id">
        <option value="">-- None --</option>
        <?php foreach($styles as $s): $sel = ($job['style_id']==$s['id'])?'selected':''; ?>
          <option value="<?php echo (int)$s['id']; ?>" <?php echo $sel; ?>><?php echo h($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Cut Date</label><input type="date" class="form-control" name="cut_date" value="<?php echo h($job['cut_date']); ?>"></div>
    <div class="col-md-3"><label class="form-label">Cut Qty</label><input type="number" class="form-control" name="cut_qty" value="<?php echo h($job['cut_qty']); ?>"></div>

    <div class="col-md-3"><label class="form-label">Factory</label>
      <select class="form-select" name="factory_id">
        <option value="">-- None --</option>
        <?php foreach($factories as $f): $sel = ($job['factory_id']==$f['id'])?'selected':''; ?>
          <option value="<?php echo (int)$f['id']; ?>" <?php echo $sel; ?>><?php echo h($f['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Out Date</label><input type="date" class="form-control" name="out_date" value="<?php echo h($job['out_date']); ?>"></div>
    <div class="col-md-3"><label class="form-label">Out Qty</label><input type="number" class="form-control" name="out_qty" value="<?php echo h($job['out_qty']); ?>"></div>
    <div class="col-md-3"><label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach(['New','In progress','On hold','Canceled','Finished','Billed'] as $st): ?>
          <option value="<?php echo $st; ?>" <?php echo ($job['status']===$st)?'selected':''; ?>><?php echo $st; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"><?php echo h($job['notes']); ?></textarea></div>
    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary">Save Changes</button></div>
  </form>

  <hr class="my-4">

  <div class="mb-2 d-flex align-items-center justify-content-between">
    <h5 class="mb-0">Photos</h5>
    <small class="text-muted">Upload JPG/PNG/GIF/WebP — multiple files allowed</small>
  </div>

  <form method="post" action="<?php echo url('jobs/photo_upload.php'); ?>?job_id=<?php echo (int)$id; ?>" enctype="multipart/form-data" class="d-flex gap-2 align-items-center mb-3">
    <input type="file" name="photos[]" multiple accept="image/*" class="form-control" required>
    <button class="btn btn-success">Upload</button>
  </form>

  <?php if($photos): ?>
    <div class="row g-3">
      <?php foreach($photos as $p): list($src,$how)=build_src_from_path($p['path'],$id); if(!$src) $src=url('jobs/photo_view.php?id='.(int)$p['id'].'&table='.urlencode($p['_table']).'&path_col=__coalesce'); ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <div class="card">
            <a href="<?php echo $src; ?>" target="_blank"><img src="<?php echo $src; ?>" class="card-img-top" style="object-fit:cover; height:160px;"></a>
            <div class="card-body p-2 d-flex justify-content-between align-items-center">
              <small class="text-muted">#<?php echo (int)$p['id']; ?> <em><?php echo h($how); ?></em></small>
              <form method="post" action="<?php echo url('jobs/photo_delete.php'); ?>" onsubmit="return confirm('Delete this photo?');">
                <input type="hidden" name="photo_id" value="<?php echo (int)$p['id']; ?>">
                <input type="hidden" name="job_id" value="<?php echo (int)$id; ?>">
                <input type="hidden" name="table" value="<?php echo h($p['_table']); ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-secondary">No photos.</div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
