<div style="padding:10px;">
  <?php if ($activity): ?>
    <script type="text/javascript">
        Ext.onReady(function () {
            var params = <?php echo $params ?>;

            Ubmod.app.extendPanelHeight();
            Ubmod.app.loadChart('<?php echo $tagId ?>-pie', 'user', 'pie',
                params);
            Ubmod.app.loadChart('<?php echo $tagId ?>-bar', 'user', 'bar',
                params);

            <?php if ($interval['multi_month']): ?>
                Ubmod.app.loadChart('<?php echo $tagId ?>-stacked-area',
                    'user', 'stackedArea', params);
            <?php endif; ?>
        });
    </script>
    <div style="padding-top:5px;" class="labelHeading">
      Tag: <span class="labelHeader"><?php echo htmlspecialchars($tagName) ?></span>
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <table class="dtable">
        <tr>
          <th>Name: </th>
          <td style="font-weight:bold;"><?php echo htmlspecialchars($tagName) ?></td>
          <th>Total Jobs: </th>
          <td style="font-weight:bold;"><?php echo number_format($activity['jobs']) ?></td>
        </td>
          <th>Avg. Wall (d): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_wallt'] ?></td>
          <th>Avg. Wait (h): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Avg. Mem (MB): </th>
          <td style="font-weight:bold;"><?php echo number_format($activity['avg_mem'], 1) ?></td>
          <th>Avg. Job Size (CPUs): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_nodes'] ?></td>
          <th>Avg. Exec (h): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_exect'] ?></td>
        </tr>
      </table>
      <div style="margin-top:10px;"><img id="<?php echo $tagId ?>-pie" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <div style="margin-top:10px;"><img id="<?php echo $tagId ?>-bar" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <?php if ($interval['multi_month']): ?>
        <div style="margin-top:10px;"><img id="<?php echo $tagId ?>-stacked-area" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    No job data found for this tag in given time period.
  <?php endif; ?>
</div>

