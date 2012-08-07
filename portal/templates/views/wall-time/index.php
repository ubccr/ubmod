<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createPartial({
        renderTo: 'dash-chart',
        url: Ubmod.baseUrl + '/wall-time/chart'
    });
});
</script>
<div id="dash-chart"></div>
<div class="chart-desc">
  Plot of the distribution of wall time in days delivered versus job size
  (number of cpu's).
</div>
<div class="chart-desc">
  If the selected time period spans multiple months, stacked area charts are
  displayed with the total days consumed for each month that is included in
  the time period.
</div>

