<script type="text/javascript">
<?php if ($request->isAllowed('dashboard', 'utilization')): ?>
  Ext.onReady(function () {
      Ubmod.app.createPartial({
          renderTo: 'dash-chart',
          url: Ubmod.baseUrl + '/dashboard/utilization'
      });
  });
<?php elseif ($request->isAllowed('group', 'details')): ?>
  Ext.onReady(function () {
      Ubmod.app.createPartial({
          renderTo: 'dash-chart',
          url: Ubmod.baseUrl + '/group/details',
          params: {
              group_id: <?php echo $request->getGroupId(); ?>
          }
      });
  });
<?php else: ?>
  Ext.onReady(function () {
      Ubmod.app.createPartial({
          renderTo: 'dash-chart',
          url: Ubmod.baseUrl + '/user/details',
          params: {
              user_id: <?php echo $request->getUserId(); ?>
          }
      });
  });
<?php endif; ?>
</script>
<div id="dash-chart"></div>

