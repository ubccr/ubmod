<script type="text/javascript">
Ext.onReady(function() {
	var toolbar = new PBSToolbar({el: 'dash-chart', displayUrl: '/cpu-consumption/chart'});
    var e = Ext.get("dash-chart");
    var updater = e.getUpdateManager();
    updater.update("/partial/cpu-consumption", {});
});
</script> 
<div id="dash-chart">
</div>
<div class="chart-desc">
Plot of the distribution of CPU utilization in CPU days delivered versus job size (number of cpu's)
</div>
