<script type="text/javascript" src="/js/stats-grid.js"></script>
<script type="text/javascript">
Ext.onReady(function(){
    Ubmod.app.addStatsPanel({
        store: Ext.create('Ubmod.store.Queue'),
        renderTo: 'stats',
        gridTitle: 'All Queues',
        recordFormat: {
            label: 'Queues',
            key: 'queue',
            id: 'queue_id',
            detailsUrl: '/queue/details'
        }
    });
});
</script>
<div id="stats" style="width: 735px; height: 400px;"></div>
<br/>
<div class="chart-desc">
This table provides detailed information on machine queues, including average job size, average wait time, and average run time.   Clicking once on the headings in each of the columns will sort the column (Table) from high to low. A second click with inverse the sort.   The Search capability allows you to search for a particular queue.
</div>
