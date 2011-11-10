<div style="padding: 10px;">
<?php if ($user): ?>
<div style="padding-top: 5px;" class="labelHeading">
User: <span class="labelHeader"><?php echo $user['user'] ?></span> &nbsp;&nbsp;
</div>
<div id="user-container">
<div id="user-form" style="width: 600px;"></div>
</div>

<div style="padding: 5px;margin-bottom: 20px;margin-top:10px;">
<table class="dtable">
<tr>
	<th>Name: </th>
        <td style="font-weight: bold"><?php echo $user['display_name'] ?></td>
	<th>Total Jobs: </th>
        <td style="font-weight: bold"><?php echo number_format($user['jobs']) ?></td>
	<th>Avg. Wall (d): </th>
        <td style="font-weight: bold"><?php echo $user['avg_wallt'] ?></td>
	<th>Avg. Wait (h): </th>
        <td style="font-weight: bold"><?php echo $user['avg_wait'] ?></td>
</tr>
<tr>
	<th>Avg. MEM: </th>
        <td style="font-weight: bold"><?php echo number_format($user['avg_mem'], 1) ?></td>
	<th>Avg. Job Size (CPUs): </th>
        <td style="font-weight: bold"><?php echo $user['avg_cpus'] ?></td>
	<th>Avg. Job Size (Nodes): </th>
        <td style="font-weight: bold"><?php echo $user['avg_nodes'] ?></td>
	<th>Avg. Exec (h): </th>
        <td style="font-weight: bold"><?php echo $user['avg_exect'] ?></td>
</tr>
</table>
<input type="hidden" id="uid" value="<?php echo $user['user_id'] ?>"/>
</div>
<?php else: ?>
No job data found for user in given time period.
<?php endif; ?>
</div>
