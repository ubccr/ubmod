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
   * Returns a query string for use with a chart URL
   *
   * @return array $params The necessary parameters
   */
  public static function getQueryString($params)
  {
    $interval = Ubmod_Model_Interval::getByParams($params);
    $cluster  = Ubmod_Model_Cluster::getById($params['cluster_id']);

    $query['interval_id'] = $interval['interval_id'];
    $query['cluster_id']  = $cluster['cluster_id'];

    if ($interval['custom']) {
      $query['start_date'] = $interval['start'];
      $query['end_date']   = $interval['end'];
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
   * Returns the subtitle used on various charts
   *
   * @param params array The needed parameters
   * @return string
   */
  private static function getSubTitle($params)
  {
    $cluster  = Ubmod_Model_Cluster::getById($params['cluster_id']);
    $interval = Ubmod_Model_Interval::getByParams($params);

    $host  = $cluster['host'];
    $start = $interval['start'];
    $end   = $interval['end'];

    return "Cluster: $host From: $start To: $end";
  }

  /**
   * Returns CPU consumption data to be displayed in a chart.
   *
   * Note: if no data is found for a given cpu interval, that interval
   * is ommitted from the returned array.
   *
   * @param params array The needed parameters
   * @return array
   */
  public static function getCpuConsumption($params)
  {
    $timeClause = Ubmod_Model_Interval::whereClause($params);

    $sql = "
      SELECT
        ROUND(COALESCE(SUM(cput), 0) / 86400, 1) AS cput,
        dim_cpus.display_name                    AS label
      FROM fact_job
      JOIN dim_date USING (dim_date_id)
      JOIN dim_cpus USING (dim_cpus_id)
      WHERE
            dim_cluster_id = :cluster_id
        AND $timeClause
      GROUP BY dim_cpus.display_name
      ORDER BY dim_cpus.view_order
    ";

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':cluster_id' => $params['cluster_id']));
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
   * @param params array The needed parameters
   * @return array
   */
  public static function getWaitTime($params)
  {
    $timeClause = Ubmod_Model_Interval::whereClause($params);

    $sql = "
      SELECT
        ROUND(COALESCE(AVG(wait), 0) / 3600, 1) AS avg_wait,
        dim_cpus.display_name                   AS label
      FROM fact_job
      JOIN dim_date USING (dim_date_id)
      JOIN dim_cpus USING (dim_cpus_id)
      WHERE
            dim_cluster_id = :cluster_id
        AND $timeClause
      GROUP BY dim_cpus.display_name
      ORDER BY dim_cpus.view_order
    ";

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':cluster_id' => $params['cluster_id']));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns the CPU interval labels
   *
   * These are used as labels on the x-axis of some charts
   *
   * @return array
   */
  private static function getCpuIntervalLabels()
  {
    $sql = '
      SELECT display_name AS label
      FROM dim_cpus_interval
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
   * @return void
   */
  public static function renderCpuConsumptionPeriod($params)
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
      'subTitle'      => self::getSubTitle($params),
      'yLabel'        => 'Delivered CPU Time (CPU Days)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => TRUE,
    ));
  }

  /**
   * Create a CPU consumption chart monthly and send it to the browser.
   *
   * @return void
   */
  public static function renderCpuConsumptionMonthly($params)
  {
    $cpuLabels  = self::getCpuIntervalLabels();
    $months     = Ubmod_Model_Interval::getMonths($params);
    $monthNames = array();

    // array( cpuLabel => array( month => cput, ... ), ... )
    $serieForCpus = array();

    foreach ($months as $monthKey => $month) {

      // array( cpuLabel => avg_cput, ... )
      $cputForLabel = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = array_merge($params, $month);
      unset($monthParams['interval_id']);

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
      'subTitle'   => self::getSubTitle($params),
      'yLabel'     => 'Delivered CPU Time (CPU Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForCpus,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a wait time period chart and send it to the browser.
   *
   * @return void
   */
  public static function renderWaitTimePeriod($params)
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
      'subTitle'      => self::getSubTitle($params),
      'yLabel'        => 'Average Wait Time (Hours)',
      'xLabel'        => 'Number of CPUs/Job',
      'labels'        => $cpus,
      'series'        => $time,
      'displayValues' => TRUE,
    ));
  }

  /**
   * Create a wait time monthly chart and send it to the browser.
   *
   * @return void
   */
  public static function renderWaitTimeMonthly($params)
  {
    $cpuLabels  = self::getCpuIntervalLabels();
    $months     = Ubmod_Model_Interval::getMonths($params);
    $monthNames = array();

    // array( cpuLabel => array( month => avg_wait, ... ), ... )
    $serieForCpus = array();

    foreach ($months as $monthKey => $month) {

      // array( cpuLabel => avg_wait, ... )
      $waitForLabel = array();

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = array_merge($params, $month);
      unset($monthParams['interval_id']);

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
      'subTitle'   => self::getSubTitle($params),
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
   * @return void
   */
  public static function renderUserPie($params)
  {
    $params['sort'] = 'wallt';
    $params['dir']  = 'DESC';

    $total = 0;
    $other = 0;
    $count = 0;
    $max   = 11;

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_User::getActivities($params) as $user) {
      if ($count < $max) {
        $users[] = $user['user'];
        $time[]  = $user['wallt'];
      } else {
        $other += $user['wallt'];
      }
      $total += $user['wallt'];
      $count++;
    }

    if ($other > 0) {
      $users[] = "Remaining\nUsers";
      $time[]  = $other;
    }

    while (list($i, $t) = each($time)) {
      $users[$i] .= sprintf(' (%d%%)', round($t / $total * 100));
    }

    self::renderPieChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'User Utilization',
      'subTitle' => self::getSubTitle($params),
      'labels'   => $users,
      'series'   => $time,
    ));
  }

  /**
   * Create a user utilization bar chart and send it to the browser.
   *
   * @return void
   */
  public static function renderUserBar($params)
  {
    $params['start'] = 0;
    $params['limit'] = 21;
    $params['sort']  = 'wallt';
    $params['dir']   = 'DESC';

    $users = array();
    $time  = array();
    foreach (Ubmod_Model_User::getActivities($params) as $user) {
      $users[] = $user['user'];
      $time[]  = $user['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'User Utilization',
      'subTitle' => self::getSubTitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $users,
      'series'   => $time,
    ));
  }

  /**
   * Create a group utilization pie chart and send it to the browser.
   *
   * @return void
   */
  public static function renderGroupPie($params)
  {
    $params['sort'] = 'wallt';
    $params['dir']  = 'DESC';

    $total = 0;
    $other = 0;
    $count = 0;
    $max   = 11;

    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Group::getActivities($params) as $group) {
      if ($count < $max) {
        $groups[] = $group['group_name'];
        $time[]   = $group['wallt'];
      } else {
        $other += $group['wallt'];
      }
      $total += $group['wallt'];
      $count++;
    }

    if ($other > 0) {
      $groups[] = "Remaining\nGroups";
      $time[]   = $other;
    }

    while (list($i, $t) = each($time)) {
      $groups[$i] .= sprintf(' (%d%%)', round($t / $total * 100));
    }

    self::renderPieChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
      'subTitle' => self::getSubTitle($params),
      'labels'   => $groups,
      'series'   => $time,
    ));
  }

  /**
   * Create a group utilization bar chart and send it to the browser.
   *
   * @return void
   */
  public static function renderGroupBar($params)
  {
    $params['start'] = 0;
    $params['limit'] = 21;
    $params['sort']  = 'wallt';
    $params['dir']   = 'DESC';

    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Group::getActivities($params) as $group) {
      $groups[] = $group['group_name'];
      $time[]   = $group['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
      'subTitle' => self::getSubTitle($params),
      'yLabel'   => 'Wall Time (Days)',
      'labels'   => $groups,
      'series'   => $time,
    ));
  }

  /**
   * Create a group utilization stacked area chart and send it to the browser.
   *
   * @return void
   */
  public static function renderGroupStackedArea($params)
  {
    $params['sort'] = 'wallt';
    $params['dir']  = 'DESC';

    $months = Ubmod_Model_Interval::getMonths($params);

    $maxGroups  = 11;
    $otherGroup = 'Remaining Groups';
    $groups     = array();
    $monthNames = array();

    // array( monthKey => array( group => wallt, ... ), ... )
    $serieForMonth = array();

    foreach ($months as $monthKey => $month) {

      $groupCount = 0;
      $groupWallt = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = array_merge($params, $month);
      unset($monthParams['interval_id']);

      foreach (Ubmod_Model_Group::getActivities($monthParams) as $group) {
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
        $groupWallt[$otherGroup] = $otherWallt;
      }

      $serieForMonth[$monthKey] = $groupWallt;
    }

    $groups = array_unique($groups);

    // The "other groups" should be listed last
    $groups[] = $otherGroup;

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
      'subTitle'   => self::getSubTitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForGroup,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Create a user utilization stacked area chart and send it to the browser.
   *
   * @return void
   */
  public static function renderUserStackedArea($params)
  {
    $params['sort'] = 'wallt';
    $params['dir']  = 'DESC';

    $months = Ubmod_Model_Interval::getMonths($params);

    $maxUsers   = 11;
    $otherUser  = 'Remaining Users';
    $users      = array();
    $monthNames = array();

    // array( monthKey => array( user => wallt, ... ), ... )
    $serieForMonth = array();

    foreach ($months as $monthKey => $month) {

      $userCount = 0;
      $userWallt  = array();
      $otherWallt = 0;

      $time = mktime(0, 0, 0, $month['month'], 1, $month['year']);
      $monthNames[] = date("M 'y", $time);

      $monthParams = array_merge($params, $month);
      unset($monthParams['interval_id']);

      foreach (Ubmod_Model_User::getActivities($monthParams) as $user) {
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
        $userWallt[$otherUser] = $otherWallt;
      }

      $serieForMonth[$monthKey] = $userWallt;
    }

    $users = array_unique($users);

    // The "other users" should be listed last
    $users[] = $otherUser;

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
      'subTitle'   => self::getSubTitle($params),
      'yLabel'     => 'Wall Time (Days)',
      'xLabel'     => 'Month',
      'labels'     => $monthNames,
      'series'     => $serieForUser,
      'legendMode' => LEGEND_VERTICAL,
    ));
  }

  /**
   * Render a bar chart.
   *
   * @return void
   */
  private static function renderBarChart($params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $areaX1 = 60;
    $areaY1 = 30;
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

    if (isset($params['subTitle'])) {
      $chart->drawText($center, 16, $params['subTitle'], array(
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
   * Render a pie chart.
   *
   * @return void
   */
  private static function renderPieChart($params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $center = $params['width'] / 2;
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

    $pie->draw2DPie($center, $radius + 60, array(
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

    if (isset($params['subTitle'])) {
      $chart->drawText($center, 16, $params['subTitle'], array(
        'Align'    => TEXT_ALIGN_TOPMIDDLE,
        'FontSize' => 8,
      ));
    }

    $chart->stroke();
    exit(0);
  }

  /**
   * Render a stacked area chart chart.
   *
   * @return void
   */
  private static function renderStackedAreaChart($params)
  {
    if (count($params['series']) == 0) {
      self::renderNoDataImage($params);
    }

    $areaX1 = 60;
    $areaY1 = 30;
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

    if (isset($params['subTitle'])) {
      $chart->drawText($center, 16, $params['subTitle'], array(
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
   * @return void
   */
  private static function renderNoDataImage($params)
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

    $chart->drawText($center, $middle, 'No data found', array(
      'FontSize' => 10,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    $chart->stroke();
    exit(0);
  }
}
