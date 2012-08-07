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
  days and in the Bar chart format in total days. The "Other" slice in the pie
  chart represents the sum of any tag keys that are not shown and the
  remaining wall time not associated with any key. If the tag keys are not
  mutually exclusive, this slice (as well as the percentages) may not be
  accurate with respect to the total amount of wall time used.
</div>
<div class="chart-desc">
  If the selected time period spans multiple months, stacked area charts are
  displayed with the total wall time in days for each month that is included
  in the time period.
</div>
<div class="chart-desc">
  Note: these charts ignore any tag that is selected in the toolbar.
</div>

