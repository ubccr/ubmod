<?php if (isset($tagKey)): ?>
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

      Ubmod.app.loadChart('tag-bar', 'tag', 'bar', params);
      Ubmod.app.loadChart('tag-pie', 'tag', 'pie', params);

      <?php if ($interval['multi_month']): ?>
          Ubmod.app.loadChart('tag-stacked-area', 'tag', 'stackedArea',
              params);
      <?php endif; ?>
  });
  </script>
  <div style="margin-bottom:20px; margin-top:10px;">
    Plot format: <a id="swap-link" class="editLink" href="#">Bar</a>
    <table style="margin-top:10px;">
      <tr>
        <td style="vertical-align:top;">
          <img id="tag-pie" class="pie" src="/images/loading.gif" />
          <img id="tag-bar" class="bar" src="/images/loading.gif" style="display:none;" />
        </td>
      </tr>
      <?php if ($interval['multi_month']): ?>
        <tr>
          <td style="vertical-align:top;">
            <img id="tag-stacked-area" src="/images/loading.gif" />
          </td>
        </tr>
      <?php endif; ?>
    </table>
  </div>
<?php else: ?>
  <div style="margin:100px; font-weight:bold;">Select a Tag Key</div>
<?php endif; ?>

