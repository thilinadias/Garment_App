<?php $footer = app_setting('footer_text'); ?>
</div>
<?php if (!empty($footer)): ?>
<footer class="border-top py-3 text-center text-muted small"><div class="container"><?php echo nl2br(h($footer)); ?></div></footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</body></html>