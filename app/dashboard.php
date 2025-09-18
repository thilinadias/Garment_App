<?php require_once __DIR__ . '/config/auth.php'; ensure_login();
$statuses=['New','In Progress','On Hold','Canceled','Finished','Billed']; $ph=implode(',',array_fill(0,count($statuses),'?'));
$st=$pdo->prepare("SELECT status, COUNT(*) c FROM jobs WHERE status IN ($ph) GROUP BY status"); $st->execute($statuses);
$counts=array_fill_keys($statuses,0); foreach($st as $r){ $counts[$r['status']]=(int)$r['c']; }
$q=$pdo->query("SELECT COALESCE(f.name,'Unassigned') factory, COUNT(*) c FROM jobs j LEFT JOIN factories f ON j.factory_id=f.id GROUP BY factory ORDER BY c DESC LIMIT 6")->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
  <div class="col-md-4"><div class="card p-3"><h5>Jobs by Status</h5><canvas id="statusPie"></canvas></div></div>
  <div class="col-md-4"><div class="card p-3"><h5>Jobs by Status (Doughnut)</h5><canvas id="statusDoughnut"></canvas></div></div>
  <div class="col-md-4"><div class="card p-3"><h5>Jobs per Factory</h5><canvas id="factoryBar"></canvas></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const labels = <?php echo json_encode(array_keys($counts)); ?>;
  const values = <?php echo json_encode(array_values($counts)); ?>;
  const colors = ['#0d6efd','#198754','#fd7e14','#dc3545','#6610f2','#20c997'];
  new Chart(document.getElementById('statusPie'), {type:'pie', data:{labels, datasets:[{data:values, backgroundColor:colors}]}});
  new Chart(document.getElementById('statusDoughnut'), {type:'doughnut', data:{labels, datasets:[{data:values, backgroundColor:colors}]}});
  const facLabels = <?php echo json_encode(array_map(fn($r)=>$r['factory'],$q)); ?>;
  const facValues = <?php echo json_encode(array_map(fn($r)=>(int)$r['c'],$q)); ?>;
  new Chart(document.getElementById('factoryBar'), {type:'bar', data:{labels:facLabels, datasets:[{data:facValues}]}, options:{scales:{y:{beginAtZero:true}}}});
});
</script>
<?php
$charts=[];
try { $charts=$pdo->query("SELECT * FROM chart_configs ORDER BY sort_order, id")->fetchAll(); } catch(Exception $e) { $charts=[]; }
if ($charts): ?>
<div class="row g-3 mt-1">
  <div class="col-12"><h5 class="mt-4 mb-2">Saved Dashboard Charts</h5></div>
  <?php foreach($charts as $c): ?>
    <div class="col-md-6"><div class="card p-3">
      <div class="d-flex justify-content-between align-items-center"><h6 class="mb-0"><?php echo h($c['title']); ?></h6></div>
      <canvas id="chart_<?php echo (int)$c['id']; ?>"></canvas>
    </div></div>
  <?php endforeach; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', async function() {
  const charts = <?php echo json_encode(array_map(function($c){ return ['id'=>$c['id'], 'dim'=>$c['dim'], 'metric'=>$c['metric'], 'type'=>$c['type']]; }, $charts)); ?>;
  for (const c of charts) {
    const res = await fetch('<?php echo url('analytics/data.php'); ?>?dim='+encodeURIComponent(c.dim)+'&metric='+encodeURIComponent(c.metric));
    const j = await res.json();
    new Chart(document.getElementById('chart_'+c.id), {type: c.type, data: {labels: j.labels, datasets:[{data:j.values}]}, options:{responsive:true, scales:{y:{beginAtZero:true}}}});
  }
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>