<div style="padding:10px;">
  <?php if ($user): ?>
    <script type="text/javascript">
        Ext.onReady(function () {
            var params = <?php echo $params ?>;

            Ubmod.app.extendPanelHeight();

            <?php if ($interval['multi_month']): ?>
                Ubmod.app.loadChart('<?php echo $userId ?>-stacked-area',
                    'user', 'stackedArea', params);
            <?php endif; ?>
        });
    </script>
    <div style="padding-top:5px;" class="labelHeading">
      User: <span class="labelHeader"><?php echo $user['name'] ?></span> &nbsp;&nbsp;
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <div class="chart-desc" style="font-weight:bold;">
        User Statistics
      </div>
      <table class="dtable">
        <tr>
          <th>Name:</th>
          <td style="font-weight:bold;"><?php echo $user['display_name'] ?></td>
          <th>Total Jobs:</th>
          <td style="font-weight:bold;"><?php echo number_format($user['jobs']) ?></td>
          <th>Avg. Mem (MB):</th>
          <td style="font-weight:bold;"><?php echo number_format($user['avg_mem'], 1) ?></td>
          <th>Avg. Exec Time (h):</th>
          <td style="font-weight:bold;"><?php echo $user['avg_exect'] ?></td>
          <th>Avg. Wait Time (h):</th>
          <td style="font-weight:bold;"><?php echo $user['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Group:</th>
          <td style="font-weight:bold;"><?php echo $user['group'] ?></td>
          <th>Total Wall Time (d):</th>
          <td style="font-weight:bold;"><?php echo number_format($user['wallt']) ?></td>
          <th>Avg. Wall Time (d):</th>
          <td style="font-weight:bold;"><?php echo $user['avg_wallt'] ?></td>
          <th>Avg. Job Size (CPUs):</th>
          <td style="font-weight:bold;"><?php echo $user['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes):</th>
          <td style="font-weight:bold;"><?php echo $user['avg_nodes'] ?></td>
        </tr>
      </table>
      <?php if ($interval['multi_month']): ?>
        <div style="margin-top:10px;"><img id="<?php echo $userId ?>-stacked-area" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    No job data found for user in given time period.
  <?php endif; ?>
</div>

