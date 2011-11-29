<?php if (isset($tagKey)): ?>
  <script type="text/javascript">
  Ext.onReady(function () {

      var currentType = 'pie',
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
  });
  </script>
  <div style="margin-bottom:20px; margin-top:10px;">
    Plot format: <a id="swap-link" class="editLink" href="#">Bar</a>
    <table style="margin-top:10px;">
      <tr>
        <td style="vertical-align:top;">
          <img class="pie" src="<?php echo $pieChart ?>" />
          <img class="bar" src="<?php echo $barChart ?>" style="display:none;" />
        </td>
      </tr>
      <?php if ($interval['multi_month']): ?>
        <tr>
          <td style="vertical-align:top;">
            <img src="<?php echo $areaChart ?>" />
          </td>
        </tr>
      <?php endif; ?>
    </table>
  </div>
<?php else: ?>
  <div style="margin:100px; font-weight:bold;">Select a Tag Key</div>
<?php endif; ?>
