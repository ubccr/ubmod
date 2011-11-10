<script type="text/javascript">
Ext.onReady(function() {
    Ubmod.app.setUpdateCallback(function (params) {
        Ext.get('dash-chart').load({
            url: '/cpu-consumption/chart',
            params: params
        });
    });
});
</script>
<div id="dash-chart">
</div>
<div class="chart-desc">
Plot of the distribution of CPU utilization in CPU days delivered versus job size (number of cpu's)
</div>
