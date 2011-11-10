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
   * Return cluster data given a cluster id.
   *
   * @param int id The cluster id
   * @return array
   */
  public static function getById($id)
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT * from cluster WHERE cluster_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

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
