<?php require_once __DIR__ . '/../config/auth.php'; ensure_login(); ensure_role(['admin','manager']); include __DIR__ . '/../includes/header.php'; ?>
<div class="card p-3"><h4 class="mb-3">Bulk Upload Jobs</h4>
<p class="text-muted">Upload a CSV or Excel (.xlsx) file with headers:</p>
<pre class="bg-light p-2 border rounded">JOB NUMBER,STYLE,CUT DATE,CUT QTY,FACTORY,OUT DATE,OUT QTY,STATUS,NOTES</pre>
<form method="post" enctype="multipart/form-data" action="<?php echo url('import/upload.php'); ?>" class="row g-3">
  <div class="col-md-8"><input type="file" class="form-control" name="file" accept=".csv,.xlsx" required></div>
  <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary">Upload & Import</button></div>
</form><hr><a class="btn btn-outline-primary btn-sm" href="<?php echo url('import/template.csv'); ?>">Download CSV Template</a></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>