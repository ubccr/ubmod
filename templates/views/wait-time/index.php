<script type="text/javascript">
Ext.onReady(function() {
	var toolbar = new PBSToolbar({el: 'dash-chart', displayUrl: '/wait-time/chart'});
    var e = Ext.get("dash-chart");
    var updater = e.getUpdateManager();
    updater.update("/partial/wait-time", {});
});
</script> 
<div id="dash-chart">
</div>
<div class="chart-desc">
Plot of the average wait time for a job to begin running versus the job size 
(number of processors). Note, this data is skewed by the users fairshare utilization, 
meaning that heavy users experience longer wait times than average users. Plots of average
wait times are also available. 
</div>
