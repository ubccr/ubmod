<script type="text/javascript">
<?php if ($request->isAllowed('dashboard', 'utilization')): ?>
  Ext.onReady(function () {
      Ubmod.app.createPartial({
          renderTo: 'dash-chart',
          url: Ubmod.baseUrl + '/dashboard/utilization'
      });
  });
<?php else: ?>
  Ext.onReady(function () {
      Ubmod.app.createPartial({
          renderTo: 'dash-chart',
          url: Ubmod.baseUrl + '/group/details',
          params: {
              group_id: <?php echo $request->getGroupId(); ?>
          }
      });
  });
<?php endif; ?>
</script>
<div id="dash-chart"></div>

