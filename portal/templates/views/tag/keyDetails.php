<?php // TODO: Rename this view ?>
<?php if (isset($chartType)): ?>
  <script type="text/javascript">
  Ext.onReady(function () {

      var params = <?php echo $params ?>,
          currentType = 'pie',
          link = Ext.get('swap-link'),
          pie = Ext.select('.pie'),
          bar = Ext.select('.bar');

      pie.each(function (el) { el.setVisibilityMode(Ext.Element.DISPLAY); });
      bar.each(function (el) { el.setVisibilityMode(Ext.Element.DISPLAY); });

      link.on('click', function () {
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

      Ubmod.app.loadChart('chart-bar', '<?php echo $chartType ?>', 'bar',
         params);
      Ubmod.app.loadChart('chart-pie', '<?php echo $chartType ?>', 'pie',
         params);

      <?php if ($interval['multi_month']): ?>
          Ubmod.app.loadChart('chart-stacked-area', '<?php echo $chartType ?>',
              'stackedArea', params);
      <?php endif; ?>
  });
  </script>
  <div style="margin-bottom:20px; margin-top:10px;">
    Plot format: <a id="swap-link" class="editLink" href="#">Bar</a>
    <table style="margin-top:10px;">
      <tr>
        <td style="vertical-align:top;">
          <img id="chart-pie" class="pie" src="<?php echo $BASE_URL ?>/images/loading.gif" />
          <img id="chart-bar" class="bar" src="<?php echo $BASE_URL ?>/images/loading.gif" style="display:none;" />
        </td>
      </tr>
      <?php if ($interval['multi_month']): ?>
        <tr>
          <td style="vertical-align:top;">
            <img id="chart-stacked-area" src="<?php echo $BASE_URL ?>/images/loading.gif" />
          </td>
        </tr>
      <?php endif; ?>
    </table>
  </div>
<?php else: ?>
  <div style="margin:100px; font-weight:bold;">Select a Tag Key</div>
<?php endif; ?>

