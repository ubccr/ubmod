<div style="padding:10px;">
  <?php if ($activity): ?>
    <div style="padding-top:5px;" class="labelHeading">
      Tag: <span class="labelHeader"><?php echo htmlspecialchars($tagName) ?></span>
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <table class="dtable">
        <tr>
          <th>Name: </th>
          <td style="font-weight:bold;"><?php echo htmlspecialchars($tagName) ?></td>
          <th>Total Jobs: </th>
          <td style="font-weight:bold;"><?php echo number_format($activity['jobs']) ?></td>
        </td>
          <th>Avg. Wall (d): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_wallt'] ?></td>
          <th>Avg. Wait (h): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Avg. Mem (MB): </th>
          <td style="font-weight:bold;"><?php echo number_format($activity['avg_mem'], 1) ?></td>
          <th>Avg. Job Size (CPUs): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_nodes'] ?></td>
          <th>Avg. Exec (h): </th>
          <td style="font-weight:bold;"><?php echo $activity['avg_exect'] ?></td>
        </tr>
      </table>
      <div style="margin-top:10px;"><img src="<?php echo $pieChart ?>" /></div>
      <div style="margin-top:10px;"><img src="<?php echo $barChart ?>" /></div>
      <?php if ($interval['multi_month']): ?>
        <div style="margin-top:10px;"><img src="<?php echo $areaChart ?>" /></div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    No job data found for this tag in given time period.
  <?php endif; ?>
</div>
