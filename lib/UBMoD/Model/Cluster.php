<?php
/**
 * Cluster model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Cluster Model
 *
 * @package UBMoD
 **/
class UBMoD_Model_Cluster
{

  /**
   * Returns an array of all clusters.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT
        c.cluster_id,
        IFNULL(c.display_name, c.host) AS display_name,
        c.host
      FROM cluster c
      ORDER BY display_name';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
