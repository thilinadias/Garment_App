<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3">
  <h4 class="mb-3">Custom Chart</h4>
  <form class="row g-3" id="chartForm">
    <div class="col-md-3"><label class="form-label">Dimension</label><select class="form-select" name="dim"><option value="status">Status</option><option value="factory">Factory</option><option value="style">Style</option><option value="month">Month (by Cut Date)</option></select></div>
    <div class="col-md-3"><label class="form-label">Metric</label><select class="form-select" name="metric"><option value="jobs">Job Count</option><option value="cut_qty">Total Cut Qty</option><option value="out_qty">Total Out Qty</option></select></div>
    <div class="col-md-3"><label class="form-label">Chart Type</label><select class="form-select" name="type"><option value="bar">Bar</option><option value="pie">Pie</option><option value="doughnut">Doughnut</option><option value="line">Line</option></select></div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button class="btn btn-primary">Create</button>
      <?php $u=current_user(); if($u && ($u['role']==='admin' || $u['role']==='manager')): ?>
        <button id="saveBtn" class="btn btn-success" type="button">Save to Dashboard</button>
      <?php endif; ?>
    </div>
    <div class="col-12 collapse" id="savePanel">
      <div class="card card-body">
        <div class="row g-2">
          <div class="col-md-6"><input class="form-control" placeholder="Chart Title" id="chartTitle"></div>
          <div class="col-md-3"><input type="number" class="form-control" id="chartOrder" placeholder="Order (lower = first)" value="100"></div>
          <div class="col-md-3"><button class="btn btn-success w-100" id="confirmSave" type="button">Confirm Save</button></div>
        </div>
        <div class="text-muted small mt-1">Saved charts appear on the Dashboard for all users.</div>
      </div>
    </div>
  </form>
  <div class="mt-4"><canvas id="chart"></canvas></div>
</div>
<script>
document.getElementById('chartForm').addEventListener('submit', async (e)=>{
  e.preventDefault(); const fd=new FormData(e.target);
  const params=new URLSearchParams(fd); const res=await fetch('<?php echo url('analytics/data.php'); ?>?'+params.toString());
  const j=await res.json(); if(window._chart) window._chart.destroy();
  window._chart=new Chart(document.getElementById('chart'), {type:fd.get('type'), data:{labels:j.labels, datasets:[{data:j.values}]}, options:{responsive:true, scales:{y:{beginAtZero:true}}}});
});
const saveBtn=document.getElementById('saveBtn'); if(saveBtn){ saveBtn.addEventListener('click', ()=>{document.getElementById('savePanel').classList.add('show');}); }
const confirmSave=document.getElementById('confirmSave'); if(confirmSave){ confirmSave.addEventListener('click', async ()=>{
  const fd=new FormData(document.getElementById('chartForm')); fd.append('title', document.getElementById('chartTitle').value || 'Custom Chart'); fd.append('sort_order', document.getElementById('chartOrder').value || '100');
  const res=await fetch('<?php echo url('analytics/save.php'); ?>', {method:'POST', body:fd}); const j=await res.json();
  alert(j.ok ? 'Saved to dashboard!' : ('Save failed: '+j.msg));
}); }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>