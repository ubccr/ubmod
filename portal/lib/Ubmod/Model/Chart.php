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
      $parts[] = 'Group: ' . $group['group_name'];
    }

    if ($params->hasTag()) {
      $parts[] = 'Tag: ' . $params->getTag();
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
  public static function getCpuConsumption(Ubmod_Model_QueryParams $params)
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
  public static function getWaitTime(Ubmod_Model_QueryParams $params)
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

    $total = 0;
    $other = 0;
    $count = 0;
    $max   = 11;

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $user) {
      if ($user['wallt'] == 0) { continue; }

      if ($count < $max) {
        $users[] = $user['user'];
        $time[]  = $user['wallt'];
      } else {
        $other += $user['wallt'];
      }
      $total += $user['wallt'];
      $count++;
    }

    if ($total > 0) {
      while (list($i, $t) = each($time)) {
        $percentage = round($t / $total * 100);

        // Don't include users with a small percentage. Add them to the
        // remaining users. This improves the display of the pie chart
        // labels.
        if ($percentage <= 2) {
          unset($users[$i]);
          unset($time[$i]);
          $other += $t;
        } else {
          $users[$i] .= " ($percentage%)";
        }
      }

      // Don't include the remaining users if the percentage is too
      // small. This prevents problems when rendering the pie chart.
      if ($other / $total > 0.0028) {
        $percentage = round($other / $total * 100);
        if ($percentage < 1) { $percentage = '<1'; }
        $users[] = "Remaining\nUsers ($percentage%)";
        $time[]  = $other;
      }
    }

    self::renderPieChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'User Utilization',
      'subtitle' => self::getSubtitle($params),
      'labels'   => $users,
      'series'   => $time,
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

    $total = 0;
    $other = 0;
    $count = 0;
    $max   = 11;

    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Job::getActivityList($params) as $group) {
      if ($group['wallt'] == 0) { continue; }

      if ($count < $max) {
        $groups[] = $group['group_name'];
        $time[]   = $group['wallt'];
      } else {
        $other += $group['wallt'];
      }
      $total += $group['wallt'];
      $count++;
    }

    if ($total > 0) {
      while (list($i, $t) = each($time)) {
        $percentage = round($t / $total * 100);

        // Don't include groups with a small percentage. Add them to the
        // remaining groups. This improves the display of the pie chart
        // labels.
        if ($percentage <= 2) {
          unset($groups[$i]);
          unset($time[$i]);
          $other += $t;
        } else {
          $groups[$i] .= " ($percentage%)";
        }
      }

      // Don't include the remaining groups if the percentage is too
      // small. This prevents problems when rendering the pie chart.
      if ($other / $total > 0.0028) {
        $percentage = round($other / $total * 100);
        if ($percentage < 1) { $percentage = '<1'; }
        $groups[] = "Remaining\nGroups ($percentage%)";
        $time[]   = $other;
      }
    }

    self::renderPieChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
      'subtitle' => self::getSubtitle($params),
      'labels'   => $groups,
      'series'   => $time,
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

      $users[] = $user['user'];
      $time[]  = $user['wallt'];
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

      $groups[] = $group['group_name'];
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

    $months = Ubmod_Model_TimeInterval::getMonths($params);

    $maxUsers   = 11;
    $otherUser  = 'Remaining Users';
    $users      = array();
    $monthNames = array();
    $haveOther  = false;

    // array( monthKey => array( user => wallt, ... ), ... )
    $serieForMonth = array();

    foreach ($months as $monthKey => $month) {

      $userCount = 0;
      $userWallt  = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (Ubmod_Model_Job::getActivityList($monthParams) as $user) {
        if ($user['wallt'] == 0) { continue; }

        if ($userCount < $maxUsers) {
          $userWallt[$user['user']] = $user['wallt'];
        } else {
          $otherWallt += $user['wallt'];
        }
        $userCount++;
      }

      // Don't include "other users" here
      $users = array_merge($users, array_keys($userWallt));

      if ($otherWallt > 0) {
        $haveOther = true;
        $userWallt[$otherUser] = $otherWallt;
      }

      $serieForMonth[$monthKey] = $userWallt;
    }

    $users = array_unique($users);

    // The "other users" should be listed last
    if ($haveOther) {
      $users[] = $otherUser;
    }

    // array( user => array( wallt, ... ), ... )
    $serieForUser = array();

    foreach ($users as $user) {
      $serie = array();
      foreach ($months as $monthKey => $month) {
        if (isset($serieForMonth[$monthKey][$user])) {
          $serie[] = $serieForMonth[$monthKey][$user];
        } else {
          $serie[] = 0;
        }
      }
      $serieForUser[$user] = $serie;
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

    $months = Ubmod_Model_TimeInterval::getMonths($params);

    $maxGroups  = 11;
    $otherGroup = 'Remaining Groups';
    $groups     = array();
    $monthNames = array();
    $haveOther  = false;

    // array( monthKey => array( group => wallt, ... ), ... )
    $serieForMonth = array();

    foreach ($months as $monthKey => $month) {

      $groupCount = 0;
      $groupWallt = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = clone $params;
      $monthParams->clearTimeInterval();
      $monthParams->setYear($month['year']);
      $monthParams->setMonth($month['month']);

      foreach (Ubmod_Model_Job::getActivityList($monthParams) as $group) {
        if ($group['wallt'] == 0) { continue; }

        if ($groupCount < $maxGroups) {
          $groupWallt[$group['group_name']] = $group['wallt'];
        } else {
          $otherWallt += $group['wallt'];
        }
        $groupCount++;
      }

      // Don't include "other groups" here
      $groups = array_merge($groups, array_keys($groupWallt));

      if ($otherWallt > 0) {
        $haveOther = true;
        $groupWallt[$otherGroup] = $otherWallt;
      }

      $serieForMonth[$monthKey] = $groupWallt;
    }

    $groups = array_unique($groups);

    // The "other groups" should be listed last
    if ($haveOther) {
      $groups[] = $otherGroup;
    }

    // array( group => array( wallt, ... ), ... )
    $serieForGroup = array();

    foreach ($groups as $group) {
      $serie = array();
      foreach ($months as $monthKey => $month) {
        if (isset($serieForMonth[$monthKey][$group])) {
          $serie[] = $serieForMonth[$monthKey][$group];
        } else {
          $serie[] = 0;
        }
      }
      $serieForGroup[$group] = $serie;
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

    $center = $params['width'] / 2;
    $middle = $params['height'] / 2;
    $radius = 85;

    $data = new pData();

    $data->addPoints($params['series'], 'series');
    $data->addPoints($params['labels'], 'labels');
    $data->setAbscissa('labels');

    $chart = new pImage($params['width'], $params['height'], $data);

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->setShadow(TRUE, array(
      'X'     => 5,
      'Y'     => 5,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 50,
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
}
