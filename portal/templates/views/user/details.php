<div style="padding:10px;">
  <?php if ($user): ?>
    <div style="padding-top:5px;" class="labelHeading">
      User: <span class="labelHeader"><?php echo $user['name'] ?></span> &nbsp;&nbsp;
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <table class="dtable">
        <tr>
          <th>Name: </th>
          <td style="font-weight:bold;"><?php echo $user['display_name'] ?></td>
          <th>Total Jobs: </th>
          <td style="font-weight:bold;"><?php echo number_format($user['jobs']) ?></td>
          <th>Avg. Mem (MB): </th>
          <td style="font-weight:bold;"><?php echo number_format($user['avg_mem'], 1) ?></td>
          <th>Avg. Exec (h): </th>
          <td style="font-weight:bold;"><?php echo $user['avg_exect'] ?></td>
          <th>Avg. Wait (h): </th>
          <td style="font-weight:bold;"><?php echo $user['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Group: </th>
          <td style="font-weight:bold;"><?php echo $user['group'] ?></td>
          <th>Total Wall (d): </th>
          <td style="font-weight:bold;"><?php echo number_format($user['wallt']) ?></td>
          <th>Avg. Wall (d): </th>
          <td style="font-weight:bold;"><?php echo $user['avg_wallt'] ?></td>
          <th>Avg. Job Size (CPUs): </th>
          <td style="font-weight:bold;"><?php echo $user['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes): </th>
          <td style="font-weight:bold;"><?php echo $user['avg_nodes'] ?></td>
        </tr>
      </table>
    </div>
  <?php else: ?>
    No job data found for user in given time period.
  <?php endif; ?>
</div>

