<div class="labelHeading" style="font-weight: bold">
Utilization for cluster: <?php echo $cluster['display_name'] ?> for period from: <?php echo $interval['start'] ?> to: <?php echo $interval['end'] ?>
</div>
<div>
<table>
<tr><td colspan="2" style="font-size: x-small">Plot format: <a class="editLink" onclick="chartswap('pie')">Pie</a> | <a class="editLink" onclick="chartswap('bar')">Bar</a></td></tr>
<tr>
    <td><img id="upie" src="<?php echo $userPieChart ?>" /><img id="ubar" style="display: none" src="<?php echo $userBarChart ?>" /></td>
    <td><img id="gpie" src="<?php echo $groupPieChart ?>" /><img id="gbar" style="display: none" src="<?php echo $groupBarChart ?>" /></td>
</tr>
<?php if ($interval['multi_month']): ?>
<tr>
    <td style="vertical-align:top;"><img src="<?php echo $userAreaChart ?>" /></td>
    <td style="vertical-align:top;"><img src="<?php echo $groupAreaChart ?>" /></td>
</tr>
<?php endif; ?>
</table>
</div>
<div style="padding: 5px;margin-bottom: 20px;margin-top:10px;">
<div class="chart-desc" style="font-weight: bold">
Overall Statistics
</div>
<table class="dtable">
<tr>
    <th>Users: </th>
    <td style="font-weight: bold"><?php echo $cluster['user_count'] ?></td>
    <th>Total Jobs: </th>
    <td style="font-weight: bold"><?php echo number_format($cluster['jobs']) ?></td>
    <th>Avg. Wall Time (d): </th>
    <td style="font-weight: bold"><?php echo $cluster['avg_wallt'] ?></td>
    <th>Avg. Wait Time (h): </th>
    <td style="font-weight: bold"><?php echo $cluster['avg_wait'] ?></td>
</tr>
<tr>
    <th>Groups: </th>
    <td style="font-weight: bold"><? echo $cluster['group_count'] ?></td>
    <th>Avg. Job Size (CPUs): </th>
    <td style="font-weight: bold"><? echo $cluster['avg_cpus'] ?></td>
    <th>Avg. Job Size (Nodes): </th>
    <td style="font-weight: bold"><? echo $cluster['avg_nodes'] ?></td>
    <th>Avg. Exec Time (h): </th>
    <td style="font-weight: bold"><? echo $cluster['avg_exect'] ?></td>
</tr>
</table>
</div>
<div class="chart-desc">
These plots provide a quick snapshot of machine utilization.   Data is presented in either Pie or Bar chart format.  In the Pie chart format, the utilization is given as a percentage of total CPU days consumed and in the Bar chart format in total CPU days consumed.   A summary table is also included that provides detailed overall statistics, such as the total number of jobs submitted, the average wait time, and the average length of a job.
</div>
