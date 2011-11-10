<?php
/**
 * Chart generating
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

require_once('pChart/class/pDraw.class.php');
require_once('pChart/class/pImage.class.php');
require_once('pChart/class/pData.class.php');
require_once('pChart/class/pCache.class.php');

/**
 * Chart model.
 *
 * @package UBMoD
 */
class UBMoD_Model_Chart
{

  /**
   * Returns CPU consumption data to be displayed in a chart.
   *
   * @return array
   */
  public static function getCpuConsumption($params)
  {
    $dbh = UBMoD_DBService::dbh();
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
    $dbh = UBMoD_DBService::dbh();
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
    foreach (self::getCpuConsumption($params) as $point) {
      $cpus[] = $point['label'];
      $time[] = (int) $point['cput'];
    }

    $cluster  = UBMoD_Model_Cluster::getById($params['cluster_id']);
    $interval = UBMoD_Model_Interval::getById($params['interval_id']);

    $subTitle = 'Cluster: ' . $cluster['host'] . ' From: ' . $interval['start']
      . ' To: ' . $interval['end'];

    self::renderBarChart(array(
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
    foreach (self::getWaitTime($params) as $point) {
      $cpus[] = $point['label'];
      $time[] = $point['avg_wait'];
    }

    $cluster  = UBMoD_Model_Cluster::getById($params['cluster_id']);
    $interval = UBMoD_Model_Interval::getById($params['interval_id']);

    $subTitle = 'Cluster: ' . $cluster['host'] . ' From: ' . $interval['start']
      . ' To: ' . $interval['end'];

    self::renderBarChart(array(
      'title'    => 'Job Wait vs. Job Size',
      'subTitle' => $subTitle,
      'yLabel'   => 'Avg. Wait Time [hours]',
      'xLabel'   => 'Number of CPUs/Job',
      'labels'   => $cpus,
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
    $data = new pData();

    $data->addPoints($params['series'], 'series');
    $data->setAxisName(0, $params['yLabel']);

    $data->addPoints($params['labels'], 'labels');
    $data->setAbscissa('labels');
    $data->setAbscissaName($params['xLabel']);

    $chart = new pImage(700, 400, $data);

    $chart->setFontProperties(array(
      'FontName' => FONT_DIR . '/verdana.ttf',
      'FontSize' => 8,
    ));

    $chart->drawText(350, 0, $params['title'], array(
      'FontSize' => 12,
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
    ));

    $chart->drawText(350, 16, $params['subTitle'], array(
      'Align'    => TEXT_ALIGN_TOPMIDDLE,
      'FontSize' => 8,
    ));

    $chart->setGraphArea(60, 30, 660, 330);

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
      'DisplayValues' => TRUE,
      'DisplayR'      => 0,
      'DisplayG'      => 0,
      'DisplayB'      => 0,
      'DisplayShadow' => TRUE,
    ));

    $chart->stroke();
  }
}
