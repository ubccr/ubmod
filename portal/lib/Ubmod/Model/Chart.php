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
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

require_once('pChart/class/pDraw.class.php');
require_once('pChart/class/pImage.class.php');
require_once('pChart/class/pData.class.php');
require_once('pChart/class/pPie.class.php');

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
      $name = $cluster['display_name'];
      $parts[] = "Cluster: $name";
    } else {
      $parts[] = "All Clusters";
    }

    if ($params->hasTimeIntervalId()) {
      $interval = Ubmod_Model_TimeInterval::getByParams($params);
      $parts[] = "From: {$interval['start']} To: {$interval['end']}";
    }

    if ($params->hasGroupId()) {
      $group = Ubmod_Model_Job::getEntity('group', $params);
      $parts[] = 'Group: '
        . self::formatNameShort($group['name'], $group['display_name']);
    }

    if ($params->hasTag()) {
      $parts[] = 'Tag: ' . $params->getTag();
    }

    if ($params->hasTagKey()) {
      $parts[] = 'Tag Key: ' . $params->getTagKey();
    }

    return implode(', ', $parts);
  }

  /**
   * Returns CPU consumption data to be displayed in a chart.
   *
   * Note: if no data is found for a given cpu interval, that interval
   * is ommitted from the returned array.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  private static function getCpuConsumption(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_cpus');
    $qb->addSelectExpressions(array(
      'cput'  => 'ROUND(COALESCE(SUM(cput), 0) / 86400, 1)',
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
   * Create a CPU consumption chart period and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderCpuConsumptionPeriod(
    Ubmod_Model_QueryParams $params)
  {
    $cputForLabel = array();
    foreach (self::getCpuConsumption($params) as $cpu) {
      $cputForLabel[$cpu['label']] = $cpu['cput'];
    }

    $cpus = array();
    $time = array();
    foreach (self::getCpuIntervalLabels() as $label) {
      $cpus[] = $label;
      $time[] = isset($cputForLabel[$label]) ? $cputForLabel[$label] : 0;
    }

    self::renderBarChart(array(
      'width'         => 700,
      'height'        => 400,
      'title'         => 'CPU Consumption vs. Job Size',
      'subtitle'      => self::getSubtitle($params),
      'yLabel'        => 'Delivered CPU Time (CPU Days)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => TRUE,
    ));
  }

  /**
   * Create a wait time period chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderWaitTimePeriod(Ubmod_Model_QueryParams $params)
  {
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

    self::renderBarChart(array(
      'width'         => 700,
      'height'        => 400,
      'title'         => 'Job Wait vs. Job Size',
      'subtitle'      => self::getSubtitle($params),
      'yLabel'        => 'Average Wait Time (Hours)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => TRUE,
    ));
  }

  /**
   * Create a CPU consumption monthly chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderCpuConsumptionMonthly(
    Ubmod_Model_QueryParams $params)
  {
    $cpuLabels  = self::getCpuIntervalLabels();
    $months     = Ubmod_Model_TimeInterval::getMonths($params);
    $monthNames = array();

    // array( cpuLabel => array( month => cput, ... ), ... )
    $serieForCpus = array();

    foreach ($months as $monthKey => $month) {

      // array( cpuLabel => avg_cput, ... )
      $cputForLabel = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (self::getCpuConsumption($monthParams) as $cpu) {
        $cputForLabel[$cpu['label']] = $cpu['cput'];
      }

      foreach ($cpuLabels as $label) {
        $cpuLabel = "$label CPUs/Job";
        $serieForCpus[$cpuLabel][$monthKey]
          = isset($cputForLabel[$label]) ? $cputForLabel[$label] : 0;
      }
    }

    self::renderStackedAreaChart(array(
      'width'      => 700,
      'height'     => 400,
      'title'      => 'CPU Consumption vs. Job Size (Monthly)',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Delivered CPU Time (CPU Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForCpus,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a wait time monthly chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderWaitTimeMonthly(
    Ubmod_Model_QueryParams $params)
  {
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

    self::renderStackedAreaChart(array(
      'width'      => 700,
      'height'     => 400,
      'title'      => 'Job Wait vs. Job Size (Monthly)',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Average Wait Time (Hours)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForCpus,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a user utilization pie chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderUserPie(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('user');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $user) {
      if ($user['wallt'] == 0) { continue; }

      $users[] = self::formatNameShort($user['name'], $user['display_name']);
      $time[]  = $user['wallt'];
    }

    self::renderPieChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'User Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $users,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => "Remaining\nUsers",
    ));
  }

  /**
   * Create a group utilization pie chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderGroupPie(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('group');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $group) {
      if ($group['wallt'] == 0) { continue; }

      $groups[]
        = self::formatNameShort($group['name'], $group['display_name']);
      $time[] = $group['wallt'];
    }

    self::renderPieChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'Group Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $groups,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => "Remaining\nGroups",
    ));
  }

  /**
   * Create a tag utilization pie chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderTagPie(Ubmod_Model_QueryParams $params)
  {
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $tags = array();
    $time   = array();
    foreach (Ubmod_Model_Tag::getActivityList($params) as $tag) {
      if ($tag['wallt'] == 0) { continue; }

      $tags[] = self::formatNameShort($tag['tag_value']);
      $time[] = $tag['wallt'];
    }

    self::renderPieChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'Tag Utilization',
      'subtitle'   => self::getSubtitle($params),
      'labels'     => $tags,
      'series'     => $time,
      'maxSlices'  => 10,
      'otherLabel' => "Remaining\nTags",
    ));
  }

  /**
   * Create a user utilization bar chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderUserBar(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('user');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $user) {
      if ($user['wallt'] == 0) { continue; }

      $users[]
        = self::formatNameShort($user['name'], $user['display_name'], 10);
      $time[] = $user['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'User Utilization',
      'subtitle' => self::getSubtitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $users,
      'series'   => $time,
    ));
  }

  /**
   * Create a group utilization bar chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderGroupBar(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('group');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $group) {
      if ($group['wallt'] == 0) { continue; }

      $groups[]
        = self::formatNameShort($group['name'], $group['display_name'], 10);
      $time[]   = $group['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
      'subtitle' => self::getSubtitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $groups,
      'series'   => $time,
    ));
  }

  /**
   * Create a tag utilization bar chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderTagBar(Ubmod_Model_QueryParams $params)
  {
    $params->setModel('tag');
    $params->setLimitRowCount(21);
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $tags = array();
    $time = array();
    foreach (Ubmod_Model_Tag::getActivityList($params) as $tag) {
      if ($tag['wallt'] == 0) { continue; }

      $tags[] = self::formatNameShort($tag['tag_value'], null, 10);
      $time[] = $tag['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Tag Utilization',
      'subtitle' => self::getSubtitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $tags,
      'series'   => $time,
    ));
  }

  /**
   * Create a user utilization stacked area chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderUserStackedArea(
    Ubmod_Model_QueryParams $params)
  {
    $params->setModel('user');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

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
      $name = self::formatNameLong($user['name'], $user['display_name']);
      $serieForUser[$name] = $serieForUserId[$user['user_id']];
    }

    if ($haveOther) {
      $serieForUser[$otherUser] = $otherSerie;
    }

    self::renderStackedAreaChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'Monthly User Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForUser,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a group utilization stacked area chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderGroupStackedArea(
    Ubmod_Model_QueryParams $params)
  {
    $params->setModel('group');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

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
      $name = self::formatNameLong($group['name'], $group['display_name']);
      $serieForGroup[$name] = $serieForGroupId[$group['group_id']];
    }

    if ($haveOther) {
      $serieForGroup[$otherGroup] = $otherSerie;
    }

    self::renderStackedAreaChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'Monthly Group Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForGroup,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a tag utilization stacked area chart and send it to the browser.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public static function renderTagStackedArea(
    Ubmod_Model_QueryParams $params)
  {
    $params->setModel('tag');
    $params->setOrderByColumn('wallt');
    $params->setOrderByDescending(TRUE);

    $maxTags = 10;
    $topTags = array();

    // array( value => array( wallt, ... ), ... )
    $serieForTagValue = array();

    $tags     = Ubmod_Model_Tag::getActivityList($params);
    $tagCount = count($tags);

    foreach (Ubmod_Model_Tag::getActivityList($params) as $tag) {
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

    self::renderStackedAreaChart(array(
      'width'      => 400,
      'height'     => 350,
      'title'      => 'Monthly Tag Utilization',
      'subtitle'   => self::getSubtitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForTagValue,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Render a pie chart.
   *
   * @param array $params
   *
   * @return void
   */
  private static function renderPieChart(array $params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $total  = 0;
    $other  = 0;
    $count  = 0;
    $series = array();
    $labels = array();

    // Calculate total and "other" values, copy data into $series and
    // $labels arrays.
    foreach ($params['series'] as $i => $value) {
      if ($count < $params['maxSlices'] - 1) {
        $series[] = $value;
        $labels[] = $params['labels'][$i];
      } else {
        $other += $value;
      }

      $total += $value;
      $count++;
    }

    if ($total > 0) {
      while (list($i, $t) = each($series)) {
        $percentage = round($t / $total * 100);

        // Don't include slices with a small percentage. Add them to the
        // "other" slice. This improves the display of the pie chart
        // labels.
        if ($percentage <= 2) {
          unset($labels[$i]);
          unset($series[$i]);
          $other += $t;
        } else {
          $labels[$i] .= " ($percentage%)";
        }
      }

      // Don't include the remaining users if the percentage is too
      // small. This prevents problems when rendering the pie chart.
      if ($other / $total > 0.0028) {
        $percentage = round($other / $total * 100);
        if ($percentage < 1) { $percentage = '<1'; }
        $labels[] = "{$params['otherLabel']} ($percentage%)";
        $series[] = $other;
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

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $pie = new pPie($chart, $data);

    $pie->draw2DPie($center, $middle, array(
      'Radius'        => 80,
      'DrawLabels'    => TRUE,
      'LabelStacked'  => TRUE,
      'Border'        => TRUE,
      'SecondPass'    => TRUE,
    ));

    $chart->setShadow(TRUE, array(
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

    $chart->stroke();
    exit(0);
  }

  /**
   * Render a bar chart.
   *
   * @param array $params
   *
   * @return void
   */
  private static function renderBarChart(array $params)
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

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->setShadow(TRUE, array(
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
      'LabelRotation' => 45,
    ));

    $displayValues
      = isset($params['displayValues']) && $params['displayValues'];

    $chart->drawBarChart(array(
      'DisplayValues' => $displayValues,
      'DisplayR'      => 0,
      'DisplayG'      => 0,
      'DisplayB'      => 0,
      'DisplayShadow' => TRUE,
    ));

    $chart->stroke();
    exit(0);
  }

  /**
   * Render a stacked area chart chart.
   *
   * @param array $params
   *
   * @return void
   */
  private static function renderStackedAreaChart(array $params)
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

    $chart->setShadow(TRUE, array(
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
      'LabelRotation' => 45,
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

    $chart->setShadow(TRUE, array(
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
   * Format a name, shortening it when necessary.
   *
   * @param string $name The name to use when no display name is given.
   * @param string $displayName The display name.
   * @param int $maxLength Maximum number of characters to allow before
   *   truncating the name.
   *
   * @return string
   */
  private static function formatNameShort($name, $displayName = null,
    $maxLength = 8)
  {
    if ($displayName) {
      $name = $displayName;
    }
    if (strlen($name) > $maxLength) {
      $name = substr($name, 0, $maxLength - 2) . '...';
    }
    return $name;
  }

  /**
   * Format a name, combining the display name when available.
   *
   * @param string $name The name to use when no display name is given.
   * @param string $displayName The display name.
   *
   * @return string
   */
  private static function formatNameLong($name, $displayName = null)
  {
    if ($displayName) {
      $name .= " ($displayName)";
    }
    return $name;
  }
}
