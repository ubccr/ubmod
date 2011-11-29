<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createStatsPanel({
        store: Ext.create('Ubmod.store.UserActivity'),
        renderTo: 'stats',
        gridTitle: 'All Users',
        recordFormat: {
            label: 'User',
            key: 'name',
            id: 'user_id',
            detailsUrl: '/user/details'
        },
        downloadUrl: '/user/csv'
    });
});
</script>
<div id="stats"></div>
<br/>
<div class="chart-desc">
  This table provides detailed information on users, including average job
  size, average wait time, and average run time. Clicking once on the headings
  in each of the columns will sort the column (Table) from high to low. A
  second click will reverse the sort. The Search capability allows you to
  search for a particular user. Press enter in the search bar to filter.
  Double-click a row to open a detail tab for that user. Click the "Export
  Data" button to download a CSV file containing the data that is currently
  being displayed.
</div>
