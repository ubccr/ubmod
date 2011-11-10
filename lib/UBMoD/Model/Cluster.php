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
    $sql = 'SELECT * FROM cluster WHERE cluster_id = ?';
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

  /**
   * Returns activity data for a given cluster and interval.
   *
   * @param array params The necessary parameters
   * @return array
   */
  public static function getActivity($params)
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT
        c.cluster_id,
        c.host,
        IFNULL(c.display_name, c.host) AS display_name,
        ca.user_count,
        ca.group_count,
        IFNULL(a.jobs, 0) AS jobs,
        IFNULL(a.wallt, 0) AS wallt,
        IFNULL(ROUND(a.avg_wallt/86400, 1), 0) AS avg_wallt,
        IFNULL(ROUND(a.max_wallt/86400, 1), 0) AS max_wallt,
        IFNULL(a.cput, 0) AS cput,
        IFNULL(ROUND(a.avg_cput/3600, 1),0) AS avg_cput,
        IFNULL(a.max_cput, 0) AS max_cput,
        IFNULL(ROUND(a.avg_mem/1024, 1), 0) AS avg_mem,
        IFNULL(a.max_mem, 0) AS max_mem,
        IFNULL(a.avg_vmem, 0) AS avg_mem,
        IFNULL(a.max_vmem, 0) AS max_vmem,
        IFNULL(ROUND(a.avg_wait/3600, 1), 0) AS avg_wait,
        IFNULL(ROUND(a.avg_exect/3600, 1), 0) AS avg_exect,
        IFNULL(a.avg_nodes, 0) AS avg_nodes,
        IFNULL(a.max_nodes, 0) AS max_nodes,
        IFNULL(a.avg_cpus, 0) AS avg_cpus,
        IFNULL(a.max_cpus, 0) AS max_cpus
      FROM cluster_activity ca
      JOIN activity a ON ca.activity_id = a.activity_id
      JOIN cluster c ON ca.cluster_id = c.cluster_id
      WHERE ca.cluster_id = :cluster_id
      AND ca.interval_id = :interval_id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':cluster_id'  => $params['cluster_id'],
      ':interval_id' => $params['interval_id'],
    ));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
