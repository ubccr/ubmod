<div style="padding:10px;" id="<?php echo $groupId ?>-details">
  <?php if ($group): ?>
    <script type="text/javascript">
        Ext.onReady(function () {
            var params = <?php echo $params ?>,
                currentType = 'pie',
                groupId = '<?php echo $groupId ?>',
                detailsId = groupId + '-details',
                link = Ext.get(groupId + '-swap-link'),
                pie = Ext.select('#' + detailsId + ' .pie'),
                bar = Ext.select('#' + detailsId + ' .bar');

            pie.each(function (el) {
                el.setVisibilityMode(Ext.Element.DISPLAY);
            });

            bar.each(function (el) {
                el.setVisibilityMode(Ext.Element.DISPLAY);
            });

            link.on('click', function (e) {
                e.preventDefault();

                if (currentType === 'bar') {
                    bar.each(function (el) { el.hide(); });
                    pie.each(function (el) { el.show(); });
                    currentType = 'pie';
                    link.dom.innerHTML = 'Bar';
                } else if (currentType === 'pie') {
                    pie.each(function (el) { el.hide(); });
                    bar.each(function (el) { el.show(); });
                    currentType = 'bar';
                    link.dom.innerHTML = 'Pie';
                }
            });

            Ubmod.app.extendPanelHeight();

            Ubmod.app.loadChart('<?php echo $groupId ?>-pie', 'user', 'pie',
                params);
            Ubmod.app.loadChart('<?php echo $groupId ?>-bar', 'user', 'bar',
                params);

            <?php if ($interval['multi_month']): ?>
                Ubmod.app.loadChart('<?php echo $groupId ?>-stacked-area',
                    'user', 'stackedArea', params);
            <?php endif; ?>
        });
    </script>
    <div style="padding-top:5px;" class="labelHeading">
      Group: <span class="labelHeader"><?php echo $group['name'] ?></span>
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <div class="chart-desc" style="font-weight:bold;">
        Group Statistics
      </div>
      <table class="dtable">
        <tr>
          <th>Users:</th>
          <td style="font-weight:bold;"><?php echo $group['user_count'] ?></td>
          <th>Total Jobs:</th>
          <td style="font-weight:bold;"><?php echo number_format($group['jobs']) ?></td>
          <th>Avg. Wall Time (d):</th>
          <td style="font-weight:bold;"><?php echo $group['avg_wallt'] ?></td>
          <th>Avg. Wait Time (h):</th>
          <td style="font-weight:bold;"><?php echo $group['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Avg. Mem (MB):</th>
          <td style="font-weight:bold;"><?php echo number_format($group['avg_mem'], 1) ?></td>
          <th>Avg. Job Size (CPUs):</th>
          <td style="font-weight:bold;"><?php echo $group['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes):</th>
          <td style="font-weight:bold;"><?php echo $group['avg_nodes'] ?></td>
          <th>Avg. Exec Time (h):</th>
          <td style="font-weight:bold;"><?php echo $group['avg_exect'] ?></td>
        </tr>
      </table>
      <div style="font-size:x-small;">
        Plot format: <a id="<?php echo $groupId ?>-swap-link" class="editLink" href="#">Bar</a>
      </div>
      <div class="pie" style="margin-top:10px;"><img id="<?php echo $groupId ?>-pie" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <div class="bar" style="margin-top:10px; display:none;"><img id="<?php echo $groupId ?>-bar" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <?php if ($interval['multi_month']): ?>
        <div style="margin-top:10px;"><img id="<?php echo $groupId ?>-stacked-area" src="<?php echo $BASE_URL ?>/images/loading.gif" /></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    No job data found for group in given time period.
  <?php endif; ?>
</div>

