<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createPartial({
        renderTo: 'dash-chart',
        url: Ubmod.baseUrl + '/dashboard/utilization'
    });
});
</script>
<div id="dash-chart"></div>
