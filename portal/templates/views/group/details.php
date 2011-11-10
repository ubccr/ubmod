<div style="padding: 10px;">
<?php if ($group): ?>
<div style="padding-top: 5px;" class="labelHeading">
Group: <span class="labelHeader"><?php echo $group['group_name'] ?></span> &nbsp;&nbsp;
</div>
<div id="group-container">
<div id="group-form" style="width: 600px;"></div>
</div>

<div style="padding: 5px;margin-bottom: 20px;margin-top:10px;">
<table class="dtable">
<tr>
	<th>Users: </th>
        <td style="font-weight: bold"><?php echo $group['user_count'] ?></td>
	<th>Total Jobs: </th>
        <td style="font-weight: bold"><?php echo number_format($group['jobs']) ?></td>
	<th>Avg. Wall (d): </th>
        <td style="font-weight: bold"><?php echo $group['avg_wallt'] ?></td>
	<th>Avg. Wait (h): </th>
        <td style="font-weight: bold"><?php echo $group['avg_wait'] ?></td>
</tr>
<tr>
	<th>Avg. MEM: </th>
        <td style="font-weight: bold"><?php echo number_format($group['avg_mem'], 1) ?></td>
	<th>Avg. Job Size (CPUs): </th>
        <td style="font-weight: bold"><?php echo $group['avg_cpus'] ?></td>
	<th>Avg. Job Size (Nodes): </th>
        <td style="font-weight: bold"><?php echo $group['avg_nodes'] ?></td>
	<th>Avg. Exec (h): </th>
        <td style="font-weight: bold"><?php echo $group['avg_exect'] ?></td>
</tr>
</table>
<input type="hidden" id="gid" value="<?php echo $group['group_id'] ?>"/>
</div>
<?php else: ?>
No job data found for group in given time period.
<?php endif; ?>
</div>
