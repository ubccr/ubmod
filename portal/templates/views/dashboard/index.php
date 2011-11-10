<script type="text/javascript">
function chartswap(type) {
    var upie = Ext.get('upie'),
        ubar = Ext.get('ubar'),
        gpie = Ext.get('gpie'),
        gbar = Ext.get('gbar');

    if (upie !== null) { upie.setVisibilityMode(Ext.Element.DISPLAY); }
    if (ubar !== null) { ubar.setVisibilityMode(Ext.Element.DISPLAY); }
    if (gpie !== null) { gpie.setVisibilityMode(Ext.Element.DISPLAY); }
    if (gbar !== null) { gbar.setVisibilityMode(Ext.Element.DISPLAY); }

    if (type === 'bar') {
        if (upie !== null) { upie.hide(); }
        if (gpie !== null) { gpie.hide(); }
        if (ubar !== null) { ubar.show(); }
        if (gbar !== null) { gbar.show(); }
    } else if (type === 'pie') {
        if (ubar !== null) { ubar.hide(); }
        if (gbar !== null) { gbar.hide(); }
        if (upie !== null) { upie.show(); }
        if (gpie !== null) { gpie.show(); }
    }
}

Ext.onReady(function () {
    Ubmod.app.createPartial({
        renderTo: 'dash-chart',
        url: '/dashboard/utilization'
    });
});
</script>
<div id="dash-chart"></div>
