<?php
/**
 * Group model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Group Model
 *
 * @package Ubmod
 **/
class Ubmod_Model_Group
{

  /**
   * Returns an array of all groups.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT * FROM research_group ORDER BY group';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Return the number of groups with activities.
   *
   * @param array params The parameters for the query
   * @return int
   */
  public static function getActivityCount($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT COUNT(*)
      FROM research_group g
      JOIN group_activity ga
        ON g.group_id = ga.group_id
        AND ga.interval_id = :interval_id
        AND ga.cluster_id = :cluster_id
      JOIN activity a
        ON ga.activity_id = a.activity_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    );

    if (isset($params['filter']) && $params['filter'] != '') {
      $sql .= ' WHERE g.group LIKE :filter';
      $dbParams[':filter'] = '%' . $params['filter'] . '%';
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    $result = $stmt->fetch();
    return $result[0];
  }

  /**
   * Retuns an array of groups joined with their activities.
   *
   * @param array params The parameters for the query
   * @return array
   */
  public static function getActivities($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT
        g.group_id,
        IFNULL(g.pi_name, g.group_name) AS group_name,
        g.pi_name,
        IFNULL(a.jobs, 0) AS jobs,
        IFNULL(ROUND(a.cput/cast(86400 AS DECIMAL), 2), 0) AS cput,
        IFNULL(ROUND(a.wallt/cast(86400 AS DECIMAL), 2), 0) AS wallt,
        IFNULL(ROUND(a.avg_wait/cast(3600 AS DECIMAL), 2), 0) AS avg_wait,
        IFNULL(a.avg_cpus, 0) AS avg_cpus,
        IFNULL(ROUND(a.avg_mem/1024,1), 0) AS avg_mem
      FROM research_group g
      JOIN group_activity ga
        ON g.group_id = ga.group_id
        AND ga.interval_id = :interval_id
        AND ga.cluster_id = :cluster_id
      JOIN activity a
        ON ga.activity_id = a.activity_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    );

    if (isset($params['filter']) && $params['filter'] != '') {
      $sql .= ' WHERE g.group_name LIKE :filter';
      $dbParams[':filter'] = '%' . $params['filter'] . '%';
    }

    if (isset($params['sort'])) {
      $sql .= sprintf(' ORDER BY %s %s', $params['sort'], $params['dir']);
    }

    if (isset($params['start'])) {
      $sql .= sprintf(' LIMIT %d, %d', $params['start'], $params['limit']);
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns the research_group for a given id and parameters
   *
   * @param array params The parameters for the query
   * @return array
   */
  public static function getActivityById($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT
        g.group_id,
        IFNULL(g.pi_name, g.group_name) AS group_name,
        g.pi_name,
        ga.user_count,
        IFNULL(a.jobs, 0) AS jobs,
        IFNULL(a.wallt, 0) AS wallt,
        IFNULL(ROUND(a.avg_wallt/86400, 1), 0) AS avg_wallt,
        IFNULL(ROUND(a.max_wallt/86400, 1), 0) AS max_wallt,
        IFNULL(a.cput, 0) AS cput,
        IFNULL(ROUND(a.avg_cput/3600, 1),0) AS avg_cput,
        IFNULL(a.max_cput, 0) AS max_cput,
        IFNULL(ROUND(a.avg_mem/1024, 1), 0) AS avg_mem,
        IFNULL(a.max_mem, 0) AS max_mem,
        IFNULL(a.avg_vmem, 0) AS avg_vmem,
        IFNULL(a.max_vmem, 0) AS max_vmem,
        IFNULL(ROUND(a.avg_wait/3600, 1), 0) AS avg_wait,
        IFNULL(ROUND(a.avg_exect/3600, 1), 0) AS avg_exect,
        IFNULL(a.avg_nodes, 0) AS avg_nodes,
        IFNULL(a.max_nodes, 0) AS max_nodes,
        IFNULL(a.avg_cpus, 0) AS avg_cpus,
        IFNULL(a.max_cpus, 0) AS max_cpus
      FROM research_group g
      JOIN
        group_activity ga
        ON g.group_id = ga.group_id
        AND ga.cluster_id = :cluster_id
        AND ga.interval_id = :interval_id
      JOIN
        activity a
        ON ga.activity_id = a.activity_id
      WHERE g.group_id = :group_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
      ':group_id'     => $params['id'],
    );

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
