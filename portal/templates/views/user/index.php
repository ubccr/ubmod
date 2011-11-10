<script type="text/javascript" src="/js/stats-grid.js"></script>
<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.setUpdateCallback(function (params) {
        var tabs = Ext.create('Ubmod.widget.TabPanel', {
            renderTo: 'stats',
            items: [
                Ext.create('Ubmod.widget.Grid', {
                    title: 'All Users',
                    store: Ext.create('Ubmod.store.User'),
                    params: params
                })
            ]
        });
    });

    /*
    var statsGrid = new StatsGrid({
        dataUrl: '/api/rest/json/user/list',
        root: 'users',
        id: 'user_id',
        display: 'user',
        label: 'User',
        displayUrl: '/user/details'
    });
     */
});
</script>
<div id="stats" style="width: 735px; height: 400px;"></div>
<br/>
<div class="chart-desc">
This table provides detailed information on users, including average job size, average wait time, and average run time.   Clicking once on the headings in each of the columns will sort the column (Table) from high to low. A second click with inverse the sort.   The Search capability allows you to search for a particular user.
</div>
