<script type="text/javascript">
Ext.onReady(function() {
    Ubmod.app.createPartial({
        renderTo: 'dash-chart',
        url: '/cpu-consumption/chart'
    });
});
</script>
<div id="dash-chart"></div>
<div class="chart-desc">
  Plot of the distribution of CPU utilization in CPU days delivered versus job
  size (number of cpu's)
</div>
