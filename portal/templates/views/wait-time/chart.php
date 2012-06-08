<script type="text/javascript">
Ext.onReady(function () {
    var params = <?php echo $params ?>;

    Ubmod.app.loadChart('wait-time-period', 'waitTime', 'period', params);

    <?php if ($interval['multi_month']): ?>
        Ubmod.app.loadChart('wait-time-monthly', 'waitTime', 'monthly',
            params);
    <?php endif; ?>
});
</script>
<div><img id="wait-time-period" src="/images/loading.gif"/></div>
<?php if ($interval['multi_month']): ?>
  <div><img id="wait-time-monthly" src="/images/loading.gif"/></div>
<?php endif; ?>

