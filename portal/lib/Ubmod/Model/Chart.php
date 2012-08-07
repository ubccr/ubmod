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
 * Chart generating
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

require_once('pChart/class/pDraw.class.php');

/**
 * Chart model.
 *
 * @package Ubmod
 */
class Ubmod_Model_Chart
{

  /**
   * Returns a query string for use with a chart URL.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return string
   */
  public static function getQueryString(Ubmod_Model_QueryParams $params)
  {
    $interval = Ubmod_Model_TimeInterval::getByParams($params);

    $query['interval_id'] = $interval['interval_id'];

    if ($interval['is_custom']) {
      $query['start_date'] = $interval['start'];
      $query['end_date']   = $interval['end'];
    }

    if ($params->hasClusterId()) {
      $query['cluster_id']  = $params->getClusterId();
    }

    if ($params->hasGroupId()) {
      $query['group_id'] = $params->getGroupId();
    }

    if ($params->hasTag()) {
      $query['tag'] = $params->getTag();
    }

    if ($params->hasTagKey()) {
      $query['tag_key'] = $params->getTagKey();
    }

    // Append time to prevent browser caching
    $query['t'] = time();

    $querySegments = array();
    foreach ($query as $key => $value) {
      $querySegments[] = $key . '=' . urlencode($value);
    }

    return implode('&amp;', $querySegments);
  }

  /**
   * Returns the subtitle used on various charts.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return string
   */
  private static function getSubtitle(Ubmod_Model_QueryParams $params)
  {
    $parts = array();

    if ($params->hasClusterId()) {
      $cluster = Ubmod_Model_Cluster::getById($params->getClusterId());
      $name
        = $cluster['display_name']
        ? $cluster['display_name']
        : $cluster['name'];
      $parts[] = "Cluster: $name";
    } else {
      $parts[] = 'All Clusters';
    }

    if ($params->hasTimeIntervalId()) {
      $interval = Ubmod_Model_TimeInterval::getByParams($params);
      $parts[] = "From: {$interval['start']} To: {$interval['end']}";
    }

    if ($params->hasGroupId()) {
      $group = Ubmod_Model_Job::getEntity('group', $params);
      $parts[] = 'Group: '
        . self::formatName($group['name'], $group['display_name']);
    }

    if ($params->hasTag()) {
      $parts[] = 'Tag: ' . $params->getTag();
    }

    if ($params->hasTagKey()) {
      $parts[] = 'Tag Key: ' . $params->getTagKey();
    }

    if ($params->hasTagParentId()) {
      $tag = Ubmod_Model_Tag::getTagById($params->getTagParentId());
      $parts[] = 'Tag Parent: ' . $tag['name'];
    }

    return implode(', ', $parts);
  }

  /**
   * Returns wall time data to be displayed in a chart.
   *
   * Note: if no data is found for a given cpu interval, that interval
   * is ommitted from the returned array.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  private static function getWallTime(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_cpus');
    $qb->addSelectExpressions(array(
      'wallt' => 'ROUND(COALESCE(SUM(wallt), 0) / 86400, 1)',
      'label' => 'dim_cpus.display_name',
      'sort'  => 'dim_cpus.view_order',
    ));
    $qb->setQueryParams($params);
    $qb->setGroupBy('label');
    $qb->setOrderBy('sort');
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
   * Returns wait time data to be displayed in a chart.
   *
   * Note: if no data is found for a given cpu interval, that interval
   * is ommitted from the returned array.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  private static function getWaitTime(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_cpus');
    $qb->addSelectExpressions(array(
      'avg_wait' => 'ROUND(COALESCE(AVG(wait), 0) / 3600, 1)',
      'label'    => 'dim_cpus.display_name',
      'sort'     => 'dim_cpus.view_order',
    ));
    $qb->setQueryParams($params);
    $qb->setGroupBy('label');
    $qb->setOrderBy('sort');
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
   * Returns the CPU interval labels.
   *
   * These are used as labels on the x-axis of some charts.
   *
   * @return array
   */
  private static function getCpuIntervalLabels()
  {
    $sql = '
      SELECT DISTINCT
        display_name AS label,
        view_order
      FROM dim_cpus
      ORDER BY view_order
    ';
    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $labels = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $labels[] = $row['label'];
    }
    return $labels;
  }

  /**
   * Get the data for a wall time period chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getWallTimePeriodData(
    Ubmod_Model_QueryParams $params
  ) {
    $walltForLabel = array();
    foreach (self::getWallTime($params) as $cpu) {
      $walltForLabel[$cpu['label']] = $cpu['wallt'];
    }

    $cpus = array();
    $time = array();
    foreach (self::getCpuIntervalLabels() as $label) {
      $cpus[] = $label;
      $time[] = isset($walltForLabel[$label]) ? $walltForLabel[$label] : 0;
    }

    return array(
      'width'         => 700,
      'height'        => 400,
      'title'         => 'Wall Time vs. Job Size',
      'subtitle'      => self::getSubtitle($params),
      'yLabel'        => 'Wall Time (Days)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => true,
    );
  }

  /**
   * Get the data for a wait time period chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getWaitTimePeriodData(
    Ubmod_Model_QueryParams $params
  ) {
    $waitForLabel = array();
    foreach (self::getWaitTime($params) as $cpu) {
      $waitForLabel[$cpu['label']] = $cpu['avg_wait'];
    }

    $cpus = array();
    $time = array();
    foreach (self::getCpuIntervalLabels() as $label) {
      $cpus[] = $label;
      $time[] = isset($waitForLabel[$label]) ? $waitForLabel[$label] : 0;
    }

    return array(
      'width'         => 700,
      'height'        => 400,
      'title'         => 'Job Wait vs. Job Size',
      'subtitle'      => self::getSubtitle($params),
      'yLabel'        => 'Average Wait Time (Hours)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => true,
    );
  }

  /**
   * Get the data for a wall time monthly chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getWallTimeMonthlyData(
    Ubmod_Model_QueryParams $params
  ) {
    $cpuLabels  = self::getCpuIntervalLabels();
    $months     = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    // array( cpuLabel => array( month => wallt, ... ), ... )
    $serieForCpus = array();

    foreach ($months as $monthKey => $month) {

      // array( cpuLabel => avg_wallt, ... )
      $walltForLabel = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (self::getWallTime($monthParams) as $cpu) {
        $walltForLabel[$cpu['label']] = $cpu['wallt'];
      }

      foreach ($cpuLabels as $label) {
        $cpuLabel = "$label CPUs/Job";
        $serieForCpus[$cpuLabel][$monthKey]
          = isset($walltForLabel[$label]) ? $walltForLabel[$label] : 0;
      }
    }

    return array(
      'width'      => 700,
      'height'     => 400,
      'title'      => 'Wall Time vs. Job Size (Monthly)',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForCpus,
      'legendMode' => LEGEND_VERTICAL,
    );
  }

  /**
   * Get the data for a wait time monthly chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getWaitTimeMonthlyData(
    Ubmod_Model_QueryParams $params
  ) {
    $cpuLabels  = self::getCpuIntervalLabels();
    $months     = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    // array( cpuLabel => array( month => avg_wait, ... ), ... )
    $serieForCpus = array();

    foreach ($months as $monthKey => $month) {

      // array( cpuLabel => avg_wait, ... )
      $waitForLabel = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (self::getWaitTime($monthParams) as $cpu) {
        $waitForLabel[$cpu['label']] = $cpu['avg_wait'];
      }

      foreach ($cpuLabels as $label) {
        $cpuLabel = "$label CPUs/Job";
        $serieForCpus[$cpuLabel][$monthKey]
          = isset($waitForLabel[$label]) ? $waitForLabel[$label] : 0;
      }
    }

    return array(
      'width'      => 700,
      'height'     => 400,
      'title'      => 'Job Wait vs. Job Size (Monthly)',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Average Wait Time (Hours)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForCpus,
      'legendMode' => LEGEND_VERTICAL,
    );
  }

  /**
   * Get the data for a user utilization pie chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getUserPieData(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('user');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $user) {
      if ($user['wallt'] == 0) { continue; }

      $users[] = self::formatName($user['name'], $user['display_name']);
      $time[]  = $user['wallt'];
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'User Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $users,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => 'Remaining Users',
    );
  }

  /**
   * Get the data for a group utilization pie chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getGroupPieData(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('group');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $groups    = array();
    $time      = array();
    $mapParams = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $group) {
      if ($group['wallt'] == 0) { continue; }

      $groups[] = self::formatName($group['name'], $group['display_name']);
      $time[]   = $group['wallt'];

      $mapParams[] = array(
        'chart_type' => 'user',
        'group_id'   => $group['group_id'],
        'tag'        => $params->getTag(),
      );
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'Group Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $groups,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => 'Remaining Groups',
      'mapParams'  => $mapParams,
    );
  }

  /**
   * Get the data for a tag utilization pie chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getTagPieData(Ubmod_Model_QueryParams $params)
  {
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $tags      = array();
    $time      = array();
    $mapParams = array();
    foreach (Ubmod_Model_Tag::getActivityList($params) as $tag) {
      if ($tag['wallt'] == 0) { continue; }

      $tags[] = $tag['tag_value'];
      $time[] = $tag['wallt'];

      if (Ubmod_Model_Tag::hasChildren($tag['tag_id'])) {
        $mapParams[] = array(
          'chart_type'    => 'tag',
          'tag_parent_id' => $tag['tag_id'],
        );
      } else {
        $mapParams[] = array(
          'chart_type' => 'group',
          'tag'        => $tag['name'],
        );
      }
    }

    $totalActivity = Ubmod_Model_Job::getActivity($params);
    $otherWallt = $totalActivity['wallt'] - array_sum($time);

    if ($otherWallt > 0) {
      $tags[] = 'Other';
      $time[] = $otherWallt;
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'Tag Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $tags,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => 'Other',
      'mapParams'  => $mapParams,
    );
  }

  /**
   * Get the data for a user utilization bar chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getUserBarData(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('user');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $user) {
      if ($user['wallt'] == 0) { continue; }

      $users[]
        = isset($user['display_name'])
        ? $user['display_name']
        : $user['name'];
      $time[] = $user['wallt'];
    }

    return array(
      'width'    => 700,
      'height'   => 350,
      'title'    => 'User Utilization',
      'subtitle' => self::getSubtitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $users,
      'series'   => $time,
    );
  }

  /**
   * Get the data for a group utilization bar chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getGroupBarData(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('group');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $groups    = array();
    $time      = array();
    $mapParams = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $group) {
      if ($group['wallt'] == 0) { continue; }

      $groups[]
        = isset($group['display_name'])
        ? $group['display_name']
        : $group['name'];

      $time[] = $group['wallt'];

      $mapParams[] = array(
        'chart_type' => 'user',
        'group_id'   => $group['group_id'],
        'tag'        => $params->getTag(),
      );
    }

    return array(
      'width'     => 700,
      'height'    => 350,
      'title'     => 'Group Utilization',
      'subtitle'  => self::getSubtitle($params),
      'yLabel'    => 'Wall Time (Days)',
      'labels'    => $groups,
      'series'    => $time,
      'mapParams' => $mapParams,
    );
  }

  /**
   * Get the data for a tag utilization bar chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getTagBarData(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('tag');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $tags      = array();
    $time      = array();
    $mapParams = array();
    foreach (Ubmod_Model_Tag::getActivityList($params) as $tag) {
      if ($tag['wallt'] == 0) { continue; }

      $tags[] = $tag['tag_value'];
      $time[] = $tag['wallt'];

      if (Ubmod_Model_Tag::hasChildren($tag['tag_id'])) {
        $mapParams[] = array(
          'chart_type'    => 'tag',
          'tag_parent_id' => $tag['tag_id'],
        );
      } else {
        $mapParams[] = array(
          'chart_type' => 'group',
          'tag'        => $tag['name'],
        );
      }
    }

    return array(
      'width'     => 700,
      'height'    => 350,
      'title'     => 'Tag Utilization',
      'subtitle'  => self::getSubtitle($params),
      'yLabel'    => 'Wall Time (Days)',
      'labels'    => $tags,
      'series'    => $time,
      'mapParams' => $mapParams,
    );
  }

  /**
   * Get the data for a user utilization stacked area chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getUserStackedAreaData(
    Ubmod_Model_QueryParams $params
  ) {
    $params->setModel('user');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $maxUsers = 10;
    $topUsers = array();

    // array( user_id => array( wallt, ... ), ... )
    $serieForUserId = array();

    $users     = Ubmod_Model_Job::getActivityList($params);
    $userCount = count($users);

    foreach ($users as $user) {
      if ($user['wallt'] == 0) { continue; }

      // Always include the first ($maxUsers - 1) users. If the number
      // of users is less than or equal to $maxUsers, include them all
      // (this is the case were there is no "other" user).
      if ($userCount <= $maxUsers || count($topUsers) < $maxUsers - 1) {
        $topUsers[] = $user;
        $serieForUserId[$user['user_id']] = array();
      }
    }

    $otherUser  = 'Remaining Users';
    $otherSerie = array();
    $haveOther  = false;

    $months = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    foreach ($months as $monthKey => $month) {
      $otherWallt = 0;
      $userWallt  = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (Ubmod_Model_Job::getActivityList($monthParams) as $user) {
        if (isset($serieForUserId[$user['user_id']])) {
          $userWallt[$user['user_id']] = $user['wallt'];
        } else {
          $otherWallt += $user['wallt'];
        }
      }

      // It's possible a top user may not have any activity in a given
      // month, so zeros must be added when that is the case.
      foreach ($topUsers as $user) {
        $userId = $user['user_id'];
        $serieForUserId[$userId][]
          = isset($userWallt[$userId]) ? $userWallt[$userId] : 0;
      }

      if ($otherWallt > 0) { $haveOther = true; }
      $otherSerie[] = $otherWallt;
    }

    // array( name => array( wallt, ... ), ... )
    $serieForUser = array();

    foreach ($topUsers as $user) {
      $name = self::formatName($user['name'], $user['display_name']);
      $serieForUser[$name] = $serieForUserId[$user['user_id']];
    }

    if ($haveOther) {
      $serieForUser[$otherUser] = $otherSerie;
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'Monthly User Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForUser,
      'legendMode' => LEGEND_VERTICAL,
    );
  }

  /**
   * Get the data for a group utilization stacked area chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getGroupStackedAreaData(
    Ubmod_Model_QueryParams $params
  ) {
    $params->setModel('group');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $maxGroups = 10;
    $topGroups = array();

    // array( group_id => array( wallt, ... ), ... )
    $serieForGroupId = array();

    $groups     = Ubmod_Model_Job::getActivityList($params);
    $groupCount = count($groups);

    foreach ($groups as $group) {
      if ($group['wallt'] == 0) { continue; }

      // Always include the first ($maxGroups - 1) groups. If the number
      // of groups is less than or equal to $maxGroups, include them all
      // (this is the case were there is no "other" group).
      if ($groupCount <= $maxGroups || count($topGroups) < $maxGroups - 1) {
        $topGroups[] = $group;
        $serieForGroupId[$group['group_id']] = array();
      }
    }

    $otherGroup = 'Remaining Groups';
    $otherSerie = array();
    $haveOther  = false;

    $months = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    foreach ($months as $monthKey => $month) {
      $groupWallt = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (Ubmod_Model_Job::getActivityList($monthParams) as $group) {
        if (isset($serieForGroupId[$group['group_id']])) {
          $groupWallt[$group['group_id']] = $group['wallt'];
        } else {
          $otherWallt += $group['wallt'];
        }
      }

      // It's possible a top group may not have any activity in a given
      // month, so zeros must be added when that is the case.
      foreach ($topGroups as $group) {
        $groupId = $group['group_id'];
        $serieForGroupId[$groupId][]
          = isset($groupWallt[$groupId]) ? $groupWallt[$groupId] : 0;
      }

      if ($otherWallt > 0) { $haveOther = true; }
      $otherSerie[] = $otherWallt;
    }

    // array( name => array( wallt, ... ), ... )
    $serieForGroup = array();

    foreach ($topGroups as $group) {
      $name = self::formatName($group['name'], $group['display_name']);
      $serieForGroup[$name] = $serieForGroupId[$group['group_id']];
    }

    if ($haveOther) {
      $serieForGroup[$otherGroup] = $otherSerie;
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'Monthly Group Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForGroup,
      'legendMode' => LEGEND_VERTICAL,
    );
  }

  /**
   * Get the data for a tag utilization stacked area chart.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getTagStackedAreaData(
    Ubmod_Model_QueryParams $params
  ) {
    $params->setModel('tag');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(true);

    $maxTags = 10;
    $topTags = array();

    // array( value => array( wallt, ... ), ... )
    $serieForTagValue = array();

    $tags     = Ubmod_Model_Tag::getActivityList($params);
    $tagCount = count($tags);

    foreach ($tags as $tag) {
      if ($tag['wallt'] == 0) { continue; }

      // Always include the first ($maxTags - 1) tags. If the number
      // of tags is less than or equal to $maxTags, include them all
      // (this is the case were there is no "other" tag).
      if ($tagCount <= $maxTags || count($topTags) < $maxTags - 1) {
        $topTags[] = $tag;
        $serieForTagValue[$tag['tag_value']] = array();
      }
    }

    $otherTag   = 'Remaining Tags';
    $otherSerie = array();
    $haveOther  = false;

    $months = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    foreach ($months as $monthKey => $month) {
      $tagWallt   = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (Ubmod_Model_Tag::getActivityList($monthParams) as $tag) {
        if (isset($serieForTagValue[$tag['tag_value']])) {
          $tagWallt[$tag['tag_value']] = $tag['wallt'];
        } else {
          $otherWallt += $tag['wallt'];
        }
      }

      // It's possible a top tag may not have any activity in a given
      // month, so zeros must be added when that is the case.
      foreach ($topTags as $tag) {
        $tagValue = $tag['tag_value'];
        $serieForTagValue[$tagValue][]
          = isset($tagWallt[$tagValue]) ? $tagWallt[$tagValue] : 0;
      }

      if ($otherWallt > 0) { $haveOther = true; }
      $otherSerie[] = $otherWallt;
    }

    if ($haveOther) {
      $serieForTagValue[$otherTag] = $otherSerie;
    }

    return array(
      'width'      => 700,
      'height'     => 350,
      'title'      => 'Monthly Tag Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForTagValue,
      'legendMode' => LEGEND_VERTICAL,
    );
  }

  /**
   * Render a pie chart.
   *
   * @param array $params
   *   - int    width      The chart image width.
   *   - int    height     The chart image height.
   *   - string title      The chart title.
   *   - string subtitle   The chart subtitle (optional).
   *   - array  labels     The pie slice labels.
   *   - array  series     The values of each pie slice.
   *   - int    maxSlices  The maximum number of slices to use.
   *   - string otherLabel The label used for excess slices.
   *
   * @return void
   */
  public static function renderPieChart(array $params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $total  = 0;
    $other  = 0;
    $count  = 0;
    $series = array();
    $labels = array();
    $values = array();

    // Calculate total and "other" values, copy data into $series and
    // $labels arrays.
    foreach ($params['series'] as $i => $value) {
      if ($count < $params['maxSlices'] - 1) {
        $series[] = $value;
        $labels[] = $params['labels'][$i];

        $mapValue = array(
          'label' => $params['labels'][$i],
          'value' => $value,
        );
        if (isset($params['mapParams']) && isset($params['mapParams'][$i])) {
          $mapValue['params'] = $params['mapParams'][$i];
        }
        $values[] = json_encode($mapValue);
      } else {
        $other += $value;
      }

      $total += $value;
      $count++;
    }

    if ($total > 0) {
      $haveOther = false;

      while (list($i, $t) = each($series)) {
        $percentage = round($t / $total * 100);

        // Don't include slices with a small percentage. Add them to the
        // "other" slice. This improves the display of the pie chart
        // labels. If a small slice is found, all subsequent slices are
        // considered small regardless of size. This allows an "other"
        // slice to be put at the end of the data as is done with the
        // tag chart.
        if ($haveOther || $percentage <= 2) {
          $haveOther = true;
          unset($labels[$i]);
          unset($series[$i]);
          unset($values[$i]);
          $other += $t;
        } else {
          $labels[$i] .= " ($percentage%)";
        }
      }

      // Make sure this is a numerical array after possibly unsetting
      // elements.
      $values = array_values($values);

      // Don't include the "other" slice if the percentage is too small.
      // This prevents problems when rendering the pie chart.
      if ($other / $total > 0.004) {
        $percentage = round($other / $total * 100);
        if ($percentage < 1) { $percentage = '<1'; }
        $labels[] = "{$params['otherLabel']} ($percentage%)";
        $series[] = $other;
        $values[] = json_encode(array(
          'label' => $params['otherLabel'],
          'value' => $other,
        ));
      }
    }

    $center = $params['width'] / 2;
    $middle = $params['height'] / 2;
    $radius = 85;

    $data = new pData();
    $data->loadPalette(PALETTE_FILE, true);

    $data->addPoints($series, 'series');
    $data->addPoints($labels, 'labels');
    $data->setAbscissa('labels');

    $chart = new pImage($params['width'], $params['height'], $data);

    if (isset($params['id'])) {
      $chart->initialiseImageMap($params['id'], IMAGE_MAP_STORAGE_SESSION);
    }

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $pie = new pPie($chart, $data);

    $pie->draw2DPie($center, $middle, array(
      'Radius'         => 80,
      'DrawLabels'     => true,
      'LabelStacked'   => true,
      'Border'         => true,
      'SecondPass'     => true,
      'RecordImageMap' => true,
    ));

    $chart->setShadow(true, array(
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $chart->drawText($center, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    if (isset($params['subtitle'])) {
      $chart->drawText($center, 16, $params['subtitle'], array(
        'Align'    => TEXT_ALIGN_TOPMIDDLE,
        'FontSize' => 8,
      ));
    }

    if (isset($params['id'])) {
      self::replacePieChartMapValues($params['id'], $values);
    }

    $chart->stroke();
    exit(0);
  }

  /**
   * Render a bar chart.
   *
   * @param array $params
   *   - int    width         The chart image width.
   *   - int    height        The chart image height.
   *   - string title         The chart title.
   *   - string subtitle      The chart subtitle (optional).
   *   - array  xLabel        The x-axis label (optional).
   *   - array  yLabel        The y-axis label.
   *   - array  labels        The bar labels.
   *   - array  series        The values of each bar.
   *   - bool   displayLabels Display a label on each bar?
   *
   * @return void
   */
  public static function renderBarChart(array $params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $areaX1 = 60;
    $areaY1 = 35;
    $areaX2 = $params['width'] - 10;
    $areaY2 = $params['height'] - 70;

    $center = $params['width'] / 2;

    $data = new pData();
    $data->loadPalette(PALETTE_FILE, true);

    $data->addPoints($params['series'], 'series');
    $data->setAxisName(0, $params['yLabel']);

    $data->addPoints($params['labels'], 'labels');
    $data->setAbscissa('labels');

    if (isset($params['xLabel'])) {
      $data->setAbscissaName($params['xLabel']);
    } else {
      $areaY2 += 10;
    }

    $chart = new pImage($params['width'], $params['height'], $data);

    if (isset($params['id'])) {
      $chart->initialiseImageMap($params['id'], IMAGE_MAP_STORAGE_SESSION);
    }

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->setShadow(true, array(
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $chart->drawText($center, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    if (isset($params['subtitle'])) {
      $chart->drawText($center, 16, $params['subtitle'], array(
        'Align'    => TEXT_ALIGN_TOPMIDDLE,
        'FontSize' => 8,
      ));
    } else {
      $areaY1 -= 10;
    }

    $chart->setGraphArea($areaX1, $areaY1, $areaX2, $areaY2);

    $chart->drawScale(array(
      'Mode'          => SCALE_MODE_START0,
      'GridR'         => 0,
      'GridG'         => 0,
      'GridB'         => 0,
      'GridAlpha'     => 20,
      'LabelRotation' => 30,
    ));

    $displayValues
      = isset($params['displayValues']) && $params['displayValues'];

    $chart->drawBarChart(array(
      'DisplayValues'  => $displayValues,
      'DisplayR'       => 0,
      'DisplayG'       => 0,
      'DisplayB'       => 0,
      'DisplayShadow'  => true,
      'RecordImageMap' => true,
    ));

    $values = array();
    foreach ($params['series'] as $i => $value) {
        $mapValue = array(
          'label' => $params['labels'][$i],
          'value' => $value,
        );
      if (isset($params['mapParams']) && isset($params['mapParams'][$i])) {
        $mapValue['params'] = $params['mapParams'][$i];
      }
      $values[] = json_encode($mapValue);
    }

    $chart->replaceImageMapValues('series', $values);
    $chart->replaceImageMapTitle('series', $params['labels']);

    $chart->stroke();
    exit(0);
  }

  /**
   * Render a stacked area chart chart.
   *
   * @param array $params
   *   - int    width         The chart image width.
   *   - int    height        The chart image height excluding legend.
   *   - string title         The chart title.
   *   - string subtitle      The chart subtitle (optional).
   *   - array  xLabel        The x-axis label (optional).
   *   - array  yLabel        The y-axis label.
   *   - array  labels        The bar labels.
   *   - array  series        The values of each bar.
   *   - bool   displayLabels Display a label on each bar?
   *   - int    legendMode    Display a vertical or horizontal legend?
   *                          Use either LEGEND_VERTICAL or
   *                          LEGEND_HORIZONTAL. Defaults to vertical
   *                          (optional).
   *
   * @return void
   */
  public static function renderStackedAreaChart(array $params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $areaX1 = 60;
    $areaY1 = 35;
    $areaX2 = $params['width'] - 10;
    $areaY2 = $params['height'] - 70;

    $center = $params['width'] / 2;

    $data = new pData();
    $data->loadPalette(PALETTE_FILE, true);

    foreach ($params['series'] as $key => $serie) {
      $data->addPoints($serie, $key);
    }
    $data->setAxisName(0, $params['yLabel']);

    // Prevent too many labels from being displayed
    $labelCount = count($params['labels']);
    if ($labelCount > 20) {
      $x = (int)($labelCount / 20) + 1;
      foreach (range(0, $labelCount - 1) as $i) {
        if ($i % $x !== 0) {
          $params['labels'][$i] = '';
        }
      }
    }

    $data->addPoints($params['labels'], 'labels');
    $data->setAbscissa('labels');

    if (isset($params['xLabel'])) {
      $data->setAbscissaName($params['xLabel']);
    } else {
      $areaY2 += 10;
    }

    $legendMode = LEGEND_VERTICAL;
    if (isset($params['legendMode'])) {
      $legendMode = $params['legendMode'];
    }

    // XXX Should perform calculation based on font size
    $legendHeight
      = $legendMode === LEGEND_VERTICAL
      ?count($params['series']) * 13
      : 13;

    $height = $params['height'] + $legendHeight;
    $chart = new pImage($params['width'], $height, $data);

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->setShadow(true, array(
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $chart->drawText($center, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    if (isset($params['subtitle'])) {
      $chart->drawText($center, 16, $params['subtitle'], array(
        'Align'    => TEXT_ALIGN_TOPMIDDLE,
        'FontSize' => 8,
      ));
    } else {
      $areaY1 -= 10;
    }

    $chart->setGraphArea($areaX1, $areaY1, $areaX2, $areaY2);

    $chart->drawScale(array(
      'Mode'          => SCALE_MODE_ADDALL_START0,
      'GridR'         => 0,
      'GridG'         => 0,
      'GridB'         => 0,
      'GridAlpha'     => 20,
      'LabelRotation' => 30,
    ));

    $displayValues
      = isset($params['displayValues']) && $params['displayValues'];

    $chart->drawStackedAreaChart(array(
      'DisplayValues' => $displayValues,
      'DisplayR'      => 0,
      'DisplayG'      => 0,
      'DisplayB'      => 0,
    ));

    $chart->drawLegend(10, $params['height'], array(
      'Style'  => LEGEND_NOBORDER,
      'Mode'   => $legendMode,
      'Family' => LEGEND_FAMILY_CIRCLE,
    ));

    $chart->stroke();
    exit(0);
  }

  /**
   * Render an image stating no data is found.
   *
   * @param array $params
   *   - int    width    The chart image width.
   *   - int    height   The chart image height.
   *   - string title    The chart title.
   *   - string subtitle The chart subtitle (optional).
   *
   * @return void
   */
  private static function renderNoDataImage(array $params)
  {
    $center = $params['width'] / 2;
    $middle = $params['height'] / 2;

    $chart = new pImage($params['width'], $params['height']);

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->setShadow(true, array(
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $chart->drawText($center, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    if (isset($params['subtitle'])) {
      $chart->drawText($center, 16, $params['subtitle'], array(
        'Align'    => TEXT_ALIGN_TOPMIDDLE,
        'FontSize' => 8,
      ));
    }

    $chart->drawText($center, $middle, 'No data found', array(
      'FontSize' => 10,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    $chart->stroke();
    exit(0);
  }

  /**
   * Output the image map data for a pie chart.
   *
   * @see renderPieChart
   */
  public static function outputPieChartImageMap($params)
  {
    if (isset($params['id'])) {
      self::outputChartMap($params['id']);
    } else {
      throw new Exception('Chart map id not found in paramters');
    }
  }

  /**
   * Output the image map data for a bar chart.
   *
   * @see renderBarChart
   */
  public static function outputBarChartImageMap($params)
  {
    if (isset($params['id'])) {
      self::outputChartMap($params['id']);
    } else {
      throw new Exception('Chart map id not found in paramters');
    }
  }

  /**
   * Format a name, combining the display name when available.
   *
   * @param string $name The name to use when no display name is given.
   * @param string $displayName The display name.
   *
   * @return string
   */
  private static function formatName($name, $displayName = null)
  {
    if ($displayName) {
      $name .= " ($displayName)";
    }
    return $name;
  }

  /**
   * Set cache data.
   *
   * @param mixed $data The data to store in the cache.
   *
   * @return string An identifier to retrieve the data.
   */
  public static function cacheSet($data)
  {
    $json = json_encode($data);

    $id = md5($json);

    if ( !isset($_SESSION['chart-cache'])
      || !is_array($_SESSION['chart-cache'])
    ) {
      $_SESSION['chart-cache'] = array();
    }

    $_SESSION['chart-cache'][$id] = $json;

    return $id;
  }

  /**
   * Get data from the cache.
   *
   * @param string $id The data identifier.
   *
   * @param mixed The data to stored in the cache.
   */
  public static function cacheGet($id)
  {
    if (!is_array($_SESSION['chart-cache'])) {
      return false;
    }

    if (!array_key_exists($id, $_SESSION['chart-cache'])) {
      return false;
    }

    try {
      $data = json_decode($_SESSION['chart-cache'][$id], true);
    } catch (Exception $e) {
      $msg = 'Failed to decode cached data: ' . $e->getMessage();
      throw new Exception($msg);
    }

    return $data;
  }

  /**
   * Clear a cache element.
   *
   * @param string $id The data identifier.
   *
   * @return bool Indicates if that the data was found and cleared.
   */
  public static function cacheClear($id)
  {
    if ( !isset($_SESSION['chart-cache'])
      || !is_array($_SESSION['chart-cache'])
    ) {
      return false;
    }

    unset($_SESSION['chart-cache'][$id]);

    return true;
  }

  /**
   * Output chart map data.
   *
   * This is a replacement pImage::dumpImageMap, but only supports the
   * session storage mode.
   *
   * @param string $name The chart key.
   */
  public static function outputChartMap($name)
  {
    if (!isset($_SESSION[$name])) {
      echo '[]';
      exit(0);
    }

    $data = array();

    foreach ($_SESSION[$name] as $i => $params) {
      $shape  = $params[0];
      $coords = $params[1];

      if (strtolower($shape) === 'rect') {
        $coords = self::fixRectCoords($coords);
      }

      $value = json_decode($params[4], true);

      $area = array(
        'id'     => $name . '-' . $i,
        'shape'  => $shape,
        'coords' => $coords,
        'color'  => $params[2],
        'title'  => $params[3],
        'label'  => $value['label'],
        'value'  => $value['value'],
      );

      if (isset($value['params'])) {
        $area['params'] = $value['params'];
      }

      $data[] = $area;
    }

    echo json_encode($data);
    exit(0);
  }

  /**
   * Format rectangle coordinates as "left,top,right,bottom".
   *
   * pChart 2.1.3 incorrectly swaps the top and bottom coordinates,
   * which results in warnings from Firebug.
   *
   * @param string $coords Rectangle coordinates
   *
   * @return string
   */
  private static function fixRectCoords($coords)
  {
    list($x1, $y1, $x2, $y2) = explode(',', $coords);

    $left   = min($x1, $x2);
    $right  = max($x1, $x2);
    $top    = min($y1, $y2);
    $bottom = max($y1, $y2);

    return implode(',', array($left, $top, $right, $bottom));
  }

  /**
   * Replace values in chart map data.
   *
   * This is a replacement for pImage::replaceImageMapValues, but only
   * supports the session storage mode.  The pChart method doesn't work
   * for pie charts, but this will.
   *
   * @param string $name The chart key.
   * @param array $values The new values for the chart
   */
  private static function replacePieChartMapValues($name, array $values)
  {
    foreach ($_SESSION[$name] as $i => &$params) {
      if (isset($values[$i])) {
        $params[4] = $values[$i];
      }
    }
  }
}

