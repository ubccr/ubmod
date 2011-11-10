<?php
/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
 */

/**
 * User model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * User Model
 *
 * @package Ubmod
 **/
class Ubmod_Model_User
{

  /**
   * Returns an array of all users.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT * FROM user ORDER BY user';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Return the number of users with activities.
   *
   * @param array params The parameters for the query
   * @return int
   */
  public static function getActivityCount($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT COUNT(*)
      FROM user u
      JOIN user_activity ua
        ON u.user_id = ua.user_id
        AND ua.interval_id = :interval_id
        AND ua.cluster_id = :cluster_id
      JOIN activity a
        ON ua.activity_id = a.activity_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    );

    if (isset($params['filter']) && $params['filter'] != '') {
      $sql .= ' WHERE u.user LIKE :filter';
      $dbParams[':filter'] = '%' . $params['filter'] . '%';
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    $result = $stmt->fetch();
    return $result[0];
  }

  /**
   * Retuns an array of users joined with their activities.
   *
   * @param array params The parameters for the query
   * @return array
   */
  public static function getActivities($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT
        u.user_id,
        u.user,
        u.display_name,
        IFNULL(a.jobs, 0) AS jobs,
        IFNULL(ROUND(a.cput/cast(86400 AS DECIMAL), 2), 0) AS cput,
        IFNULL(ROUND(a.wallt/cast(86400 AS DECIMAL), 2), 0) AS wallt,
        IFNULL(ROUND(a.avg_wait/cast(3600 AS DECIMAL), 2), 0) AS avg_wait,
        IFNULL(a.avg_cpus, 0) AS avg_cpus,
        IFNULL(ROUND(a.avg_mem/1024,1), 0) AS avg_mem
      FROM user u
      JOIN user_activity ua
        ON u.user_id = ua.user_id
        AND ua.interval_id = :interval_id
        AND ua.cluster_id = :cluster_id
      JOIN activity a
        ON ua.activity_id = a.activity_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    );

    if (isset($params['filter']) && $params['filter'] != '') {
      $sql .= ' WHERE u.user LIKE :filter';
      $dbParams[':filter'] = '%' . $params['filter'] . '%';
    }

    $sortFields
      = array('user', 'jobs', 'avg_cpus', 'avg_wait', 'wallt', 'avg_mem');

    if (isset($params['sort']) && in_array($params['sort'], $sortFields)) {
      if (!in_array($params['dir'], array('ASC', 'DESC'))) {
        $params['dir'] = 'ASC';
      }
      $sql .= sprintf(' ORDER BY %s %s', $params['sort'], $params['dir']);
    }

    if (isset($params['start']) && isset($params['limit'])) {
      $sql .= sprintf(' LIMIT %d, %d', $params['start'], $params['limit']);
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns the user for a given id and parameters
   *
   * @param array params The parameters for the query
   * @return array
   */
  public static function getActivityById($params)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = 'SELECT
        u.user_id,
        u.user,
        u.display_name,
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
      FROM user u
      JOIN
        user_activity ua
        ON u.user_id = ua.user_id
        AND ua.cluster_id = :cluster_id
        AND ua.interval_id = :interval_id
      JOIN
        activity a
        ON ua.activity_id = a.activity_id
      WHERE u.user_id = :user_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
      ':user_id'     => $params['id'],
    );

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
