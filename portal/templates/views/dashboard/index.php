<script type="text/javascript">
var index = 1;
function chartswap(type) {
    var upie = Ext.get("upie");
    if(upie != null) upie.setVisibilityMode(Element.DISPLAY);
    var ubar = Ext.get("ubar");
    if(ubar != null) ubar.setVisibilityMode(Element.DISPLAY);
    var gpie = Ext.get("gpie");
    if(gpie != null) gpie.setVisibilityMode(Element.DISPLAY);
    var gbar = Ext.get("gbar");
    if(gbar != null) gbar.setVisibilityMode(Element.DISPLAY);
    if(index == 1 && type == 'bar') {
        if(upie != null) upie.setStyle("display", "none");
        if(gpie != null) gpie.setStyle("display", "none");
        if(ubar != null) ubar.setStyle("display", "block");
        if(gbar != null) gbar.setStyle("display", "block");
        index = 0;
    } else if(index == 0 && type == 'pie'){
        if(ubar != null) ubar.setStyle("display", "none");
        if(gbar != null) gbar.setStyle("display", "none");
        if(upie != null) upie.setStyle("display", "block");
        if(gpie != null) gpie.setStyle("display", "block");
        index = 1;
    }
}
Ext.onReady(function () {
    /*
    var url = "/dashboard/utilization";
    var toolbar = new PBSToolbar({el: 'dash-chart', displayUrl: url});
    Ext.get('dash-chart').load({
        url: '/dashboard/utilization',
            params: {
                cluster_id: 1,
                interval_id: 3
            }
    });
     */
    Ext.onReady(function () {
        Ubmod.app.setPage('dashboard');
    });
});
</script>
<div id="dash-chart">
</div>
