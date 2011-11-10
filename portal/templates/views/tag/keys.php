<script type="text/javascript">
Ext.onReady(function () {
    Ubmod.app.createTagKeyPanel({ renderTo: 'tags' });
});
</script>
<div id="tags"></div>
<br />
<div class="chart-desc">
  These plots provide a quick snapshot of utilization group by tag values for
  the selected tag key. Data is presented in either Pie or Bar chart format.
  In the Pie chart format, the utilization is given as a percentage of total
  CPU days consumed and in the Bar chart format in total CPU days consumed.
</div>
<div class="chart-desc">
  If the selected time period spans multiple months, stacked area charts are
  displayed with the total CPU days consumed for each month that is included
  in the time period.
</div>
