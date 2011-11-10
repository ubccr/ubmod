<?php
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
   * Returns CPU consumption data to be displayed in a chart.
   *
   * @return array
   */
  public static function getCpuConsumption($params)
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT
        cput / 86400 AS cput,
        label,
        view_order
      FROM cpu_consumption
      WHERE
        cluster_id = :cluster_id
        AND interval_id = :interval_id
      ORDER BY view_order';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':cluster_id'  => $params['cluster_id'],
      ':interval_id' => $params['interval_id'],
    ));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns wait time data to be displayed in a chart.
   *
   * @return array
   */
  public static function getWaitTime($params)
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT
        round(avg_wait/3600) as avg_wait,
        label,
        view_order
      FROM actual_wait_time
      WHERE
        cluster_id = :cluster_id
        AND interval_id = :interval_id
      ORDER BY view_order';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':cluster_id'  => $params['cluster_id'],
      ':interval_id' => $params['interval_id'],
    ));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Create a CPU consumption chart and send it to the browser.
   *
   * @return void
   */
  public static function renderCpuConsumption($params)
  {
    $cpus = array();
    $time = array();
    foreach (self::getCpuConsumption($params) as $cpu) {
      $cpus[] = $cpu['label'];
      $time[] = (int) $cpu['cput'];
    }

    $cluster  = Ubmod_Model_Cluster::getById($params['cluster_id']);
    $interval = Ubmod_Model_Interval::getById($params['interval_id']);

    $subTitle = 'Cluster: ' . $cluster['host'] . ' From: ' . $interval['start']
      . ' To: ' . $interval['end'];

    self::renderBarChart(array(
      'width'    => 700,
      'height'   => 400,
      'title'    => 'CPU Consumption vs. Job Size',
      'subTitle' => $subTitle,
      'yLabel'   => 'Delivered CPU time [cpu days]',
      'xLabel'   => 'Number of CPUs/Job',
      'labels'   => $cpus,
      'series'   => $time,
    ));
  }

  /**
   * Create a wait time chart and send it to the browser.
   *
   * @return void
   */
  public static function renderWaitTime($params)
  {
    $cpus = array();
    $time = array();
    foreach (self::getWaitTime($params) as $cpu) {
      $cpus[] = $cpu['label'];
      $time[] = $cpu['avg_wait'];
    }

    $cluster  = Ubmod_Model_Cluster::getById($params['cluster_id']);
    $interval = Ubmod_Model_Interval::getById($params['interval_id']);

    $subTitle = 'Cluster: ' . $cluster['host'] . ' From: ' . $interval['start']
      . ' To: ' . $interval['end'];

    self::renderBarChart(array(
      'width'    => 700,
      'height'   => 400,
      'title'    => 'Job Wait vs. Job Size',
      'subTitle' => $subTitle,
      'yLabel'   => 'Avg. Wait Time [hours]',
      'xLabel'   => 'Number of CPUs/Job',
      'labels'   => $cpus,
      'series'   => $time,
    ));
  }

  /**
   * Create a user utilization pie chart and send it to the browser.
   *
   * @return void
   */
  public static function renderUserPie($params)
  {
    $dbParams = array(
      'interval_id' => $params['interval_id'],
      'cluster_id'  => $params['cluster_id'],
      'sort'        => 'wallt',
      'dir'         => 'DESC',
    );

    $total = 0;
    $other = 0;
    $count = 0;
    $max = 11;
    $users = array();
    $time  = array();
    foreach (Ubmod_Model_User::getActivities($dbParams) as $user) {
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
    $dbParams = array(
      'interval_id' => $params['interval_id'],
      'cluster_id'  => $params['cluster_id'],
      'start'       => 0,
      'limit'       => 21,
      'sort'        => 'wallt',
      'dir'         => 'DESC',
    );
    $users = array();
    $time  = array();
    foreach (Ubmod_Model_User::getActivities($dbParams) as $user) {
      $users[] = $user['user'];
      $time[]  = $user['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'User Utilization',
      'yLabel'   => 'Wall time [days]',
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
    $dbParams = array(
      'interval_id' => $params['interval_id'],
      'cluster_id'  => $params['cluster_id'],
      'sort'        => 'wallt',
      'dir'         => 'DESC',
    );

    $total = 0;
    $other = 0;
    $count = 0;
    $max = 11;
    $groups = array();
    $time  = array();
    foreach (Ubmod_Model_Group::getActivities($dbParams) as $group) {
      if ($count < $max) {
        $groups[] = $group['group_name'];
        $time[]  = $group['wallt'];
      } else {
        $other += $group['wallt'];
      }
      $total += $group['wallt'];
      $count++;
    }

    if ($other > 0) {
      $groups[] = "Remaining\nGroups";
      $time[]  = $other;
    }

    while (list($i, $t) = each($time)) {
      $groups[$i] .= sprintf(' (%d%%)', round($t / $total * 100));
    }

    self::renderPieChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
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
    $dbParams = array(
      'interval_id' => $params['interval_id'],
      'cluster_id'  => $params['cluster_id'],
      'start'       => 0,
      'limit'       => 21,
      'sort'        => 'wallt',
      'dir'         => 'DESC',
    );
    $groups = array();
    $time   = array();
    foreach (Ubmod_Model_Group::getActivities($dbParams) as $group) {
      $groups[] = $group['group_name'];
      $time[]   = $group['wallt'];
    }

    self::renderBarChart(array(
      'width'    => 400,
      'height'   => 350,
      'title'    => 'Group Utilization',
      'yLabel'   => 'Wall time [days]',
      'labels'   => $groups,
      'series'   => $time,
    ));
  }

  /**
   * Render a bar chart.
   *
   * @return void
   */
  private static function renderBarChart($params)
  {
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
      // TODO
    }

    $chart = new pImage($params['width'], $params['height'], $data);

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
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
      // TODO
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

    $chart->setShadow(TRUE, array(
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $chart->drawBarChart(array(
      #'DisplayValues' => TRUE,
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
      'X'     => 1,
      'Y'     => 1,
      'R'     => 0,
      'G'     => 0,
      'B'     => 0,
      'Alpha' => 20,
    ));

    $pie = new pPie($chart, $data);

    $pie->draw2DPie($center, $radius + 60, array(
      'Radius'        => 80,
      'DrawLabels'    => TRUE,
      'LabelStacked'  => TRUE,
      'Border'        => TRUE,
      'SecondPass'    => TRUE,
    ));

    $chart->drawText($center, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    $chart->stroke();
    exit(0);
  }
}
