<script type="text/javascript" src="/js/stats-grid.js"></script>
<script type="text/javascript">
Ext.onReady(function(){
    Ubmod.app.addStatsPanel({
        store: Ext.create('Ubmod.store.Group'),
        renderTo: 'stats',
        gridTitle: 'All Groups',
        recordFormat: {
            label: 'Group',
            key: 'group_name',
            id: 'group_id',
            detailsUrl: '/group/details'
        }
    });
});
</script>
<div id="stats" style="width: 735px;"></div>
<br/>
<div class="chart-desc">
This table provides detailed information on groups, including average job size, average wait time, and average run time.   Clicking once on the headings in each of the columns will sort the column (Table) from high to low. A second click with inverse the sort.   The Search capability allows you to search for a particular group.
</div>
