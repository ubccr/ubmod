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

    self::renderBarChart(array(
      'labels' => $cpus,
      'series' => $time,
      'title'  => 'CPU Consumption vs. Job Size',
      'yLabel' => 'Delivered CPU time [cpu days]',
      'xLabel' => 'Number of CPUs/Job',
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

    self::renderBarChart(array(
      'labels' => $cpus,
      'series' => $time,
      'title'  => '',
      'yLabel' => '',
      'xLabel' => '',
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
      'FontName' => FONT_DIR . '/Forgotte.ttf',
    ));

    $chart->drawText(350, 25, $params['title'], array(
      'FontSize' => 20,
      'Align'    => TEXT_ALIGN_BOTTOMMIDDLE,
    ));

    $chart->setGraphArea(60, 30, 660, 330);

    $chart->drawScale(array(
      'DrawSubTicks' => TRUE,
      'GridR'        => 0,
      'GridG'        => 0,
      'GridB'        => 0,
      'GridAlpha'    => 10,
      'Mode'         => SCALE_MODE_START0,
    ));

    $chart->drawBarChart(array(
      'DisplayValues' => TRUE,
      'DisplayR'      => 0,
      'DisplayG'      => 0,
      'DisplayB'      => 0,
    ));

    $chart->stroke();
  }
}
