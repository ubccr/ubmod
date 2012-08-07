<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 */

/**
 * Job model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Job Model.
 *
 * @package Ubmod
 */
class Ubmod_Model_Job
{

  /**
   * Return the number of users with activity for the given parameters.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return int
   */
  public static function getActivityCount(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->setQueryParams($params);

    list($sql, $dbParams) = $qb->buildCountQuery();

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['count'];
  }

  /**
   * Returns job activity.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getActivityList(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();

    $qb->setFactTable('fact_job');

    // Common fields
    $qb->addSelectExpressions(array(
      'jobs'        => 'COUNT(*)',
      'user_count'  => 'COUNT(DISTINCT dim_user_id)',
      'group_count' => 'COUNT(DISTINCT dim_group_id)',
      'queue_count' => 'COUNT(DISTINCT dim_queue_id)',
      'wallt'       => 'ROUND(SUM(wallt) / 86400, 1)',
      'avg_wallt'   => 'ROUND(AVG(wallt) / 86400, 1)',
      'max_wallt'   => 'ROUND(MAX(wallt) / 86400, 1)',
      'cput'        => 'ROUND(SUM(cput)  / 86400, 1)',
      'avg_cput'    => 'ROUND(AVG(cput)  / 86400, 1)',
      'max_cput'    => 'ROUND(MAX(cput)  / 86400, 1)',
      'avg_mem'     => 'ROUND(AVG(mem)   / 1024,  1)',
      'max_mem'     => 'ROUND(MAX(mem)   / 1024,  1)',
      'avg_vmem'    => 'ROUND(AVG(vmem)  / 1024,  1)',
      'max_vmem'    => 'ROUND(MAX(vmem)  / 1024,  1)',
      'avg_wait'    => 'ROUND(AVG(wait)  / 3600,  1)',
      'avg_exect'   => 'ROUND(AVG(exect) / 3600,  1)',
      'max_nodes'   => 'ROUND(MAX(nodes),         1)',
      'avg_nodes'   => 'ROUND(AVG(nodes),         1)',
      'max_cpus'    => 'ROUND(MAX(cpus),          1)',
      'avg_cpus'    => 'ROUND(AVG(cpus),          1)',
    ));

    $qb->setQueryParams($params);

    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns a single array of activity.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getActivity(Ubmod_Model_QueryParams $params)
  {
    $activity = self::getActivityList($params);

    if (count($activity) > 0) {
      return $activity[0];
    } else {
      return null;
    }
  }

  /**
   * Returns a single array with the activity with the specific model
   * data added.
   *
   * @param string $type The model type (user, group, queue or cluster).
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getEntity($type, Ubmod_Model_QueryParams $params)
  {
    $params->setModel($type);
    return self::getActivity($params);
  }

  /**
   * Return the standard columns for given query parameters.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getColumns(Ubmod_Model_QueryParams $params)
  {
    if (!$params->hasModel()) {
      throw new Exception('No model specified');
    }

    switch ($params->getModel()) {
    case 'user':
      return array(
        'name'         => 'User',
        'display_name' => 'Name',
        'group'        => 'Group',
        'jobs'         => '# Jobs',
        'avg_cpus'     => 'Avg. Job Size (cpus)',
        'avg_wait'     => 'Avg. Wait Time (h)',
        'wallt'        => 'Wall Time (d)',
        'avg_mem'      => 'Avg. Mem (MB)',
      );
      break;
    case 'group':
      return array(
        'name'         => 'Group',
        'display_name' => 'Name',
        'jobs'         => '# Jobs',
        'avg_cpus'     => 'Avg. Job Size (cpus)',
        'avg_wait'     => 'Avg. Wait Time (h)',
        'wallt'        => 'Wall Time (d)',
        'avg_mem'      => 'Avg. Mem (MB)',
      );
      break;
    case 'queue':
      return array(
        'name'         => 'Queue',
        'display_name' => 'Name',
        'jobs'         => '# Jobs',
        'avg_cpus'     => 'Avg. Job Size (cpus)',
        'avg_wait'     => 'Avg. Wait Time (h)',
        'wallt'        => 'Wall Time (d)',
        'avg_mem'      => 'Avg. Mem (MB)',
      );
      break;
    default:
      throw new Exception('Unknown model');
      break;
    }
  }

  /**
   * Return a suitable filename for the given query parameters.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return string
   */
  public static function getFilename(Ubmod_Model_QueryParams $params)
  {
    if (!$params->hasModel()) {
      throw new Exception('No model specified');
    }

    switch ($params->getModel()) {
    case 'user':
      return 'users';
      break;
    case 'group':
      return 'groups';
      break;
    case 'queue':
      return 'queues';
      break;
    default:
      throw new Exception('Unknown model');
      break;
    }
  }
}
