<div style="padding:10px;">
  <?php if ($tag): ?>
    <div style="padding-top:5px;" class="labelHeading">
      Tag: <span class="labelHeader"><?php echo htmlspecialchars($tag['name']) ?></span>
    </div>
    <div style="padding:5px; margin-bottom:20px; margin-top:10px;">
      <table class="dtable">
        <tr>
          <th>Name: </th>
          <td style="font-weight:bold;"><?php echo htmlspecialchars($tag['name']) ?></td>
          <th>Total Jobs: </th>
          <td style="font-weight:bold;"><?php echo number_format($tag['jobs']) ?></td>
        </td>
          <th>Avg. Wall (d): </th>
          <td style="font-weight:bold;"><?php echo $tag['avg_wallt'] ?></td>
          <th>Avg. Wait (h): </th>
          <td style="font-weight:bold;"><?php echo $tag['avg_wait'] ?></td>
        </tr>
        <tr>
          <th>Avg. Mem (MB): </th>
          <td style="font-weight:bold;"><?php echo number_format($tag['avg_mem'], 1) ?></td>
          <th>Avg. Job Size (CPUs): </th>
          <td style="font-weight:bold;"><?php echo $tag['avg_cpus'] ?></td>
          <th>Avg. Job Size (Nodes): </th>
          <td style="font-weight:bold;"><?php echo $tag['avg_nodes'] ?></td>
          <th>Avg. Exec (h): </th>
          <td style="font-weight:bold;"><?php echo $tag['avg_exect'] ?></td>
        </tr>
      </table>
      <div style="margin-top:10px;"><img src="<?php echo $pieChart ?>" /></div>
      <div style="margin-top:10px;"><img src="<?php echo $barChart ?>" /></div>
      <div style="margin-top:10px;"><img src="<?php echo $areaChart ?>" /></div>
    </div>
  <?php else: ?>
    No job data found for this tag in given time period.
  <?php endif; ?>
</div>
