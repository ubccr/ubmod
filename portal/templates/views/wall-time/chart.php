<script type="text/javascript">
Ext.onReady(function () {
    var params = <?php echo $params ?>;

    Ubmod.app.loadChart('wall-time-period', 'wallTime', 'period', params);

    <?php if ($interval['multi_month']): ?>
        Ubmod.app.loadChart('wall-time-monthly', 'wallTime', 'monthly',
            params);
    <?php endif; ?>
});
</script>
<div><img id="wall-time-period" src="<?php echo $BASE_URL ?>/images/loading.gif"/></div>
<?php if ($interval['multi_month']): ?>
  <div><img id="wall-time-monthly" src="<?php echo $BASE_URL ?>/images/loading.gif"/></div>
<?php endif; ?>

