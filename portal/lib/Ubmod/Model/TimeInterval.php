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
 * Time interval model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id: Interval.php 3125 2011-09-14 19:33:14Z jtpalmer@K5.CCR.BUFFALO.EDU $
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Time interval Model
 *
 * @package Ubmod
 */
class Ubmod_Model_TimeInterval
{

  /**
   * Returns time interval data.
   *
   * @param int $intervalId Time interval primary key.
   *
   * @return array
   */
  public static function getById($intervalId)
  {
    $sql = '
      SELECT
        time_interval_id               AS interval_id,
        display_name                   AS name,
        DATE_FORMAT(start, "%m/%d/%Y") AS start,
        DATE_FORMAT(end,   "%m/%d/%Y") AS end,
        custom                         AS is_custom,
        query_params                   AS params
      FROM time_interval
      WHERE time_interval_id = :time_interval_id
    ';

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':time_interval_id' => $intervalId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $interval = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$interval['is_custom']) {
      $interval['params'] = json_decode($interval['params'], 1);
    }

    return $interval;
  }

  /**
   * Returns time interval data.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getByParams(Ubmod_Model_QueryParams $params)
  {
    $sql = '
      SELECT
        time_interval_id               AS interval_id,
        display_name                   AS name,
        DATE_FORMAT(start, "%m/%d/%Y") AS start,
        DATE_FORMAT(end,   "%m/%d/%Y") AS end,
        custom                         AS is_custom
      FROM time_interval
      WHERE time_interval_id = ?
    ';

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array($params->getTimeIntervalId()));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $timeInterval = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($timeInterval['is_custom']) {
      $timeInterval['start'] = self::formatDate($params->getStartDate());
      $timeInterval['end']   = self::formatDate($params->getEndDate());
    }

    // Check if the interval contains data for multiple months
    $timeInterval['multi_month'] = count(self::getMonths($params)) > 1;

    return $timeInterval;
  }

  /**
   * Returns an array of all time intervals.
   *
   * @return array
   */
  public static function getAll()
  {
    $sql = '
      SELECT
        time_interval_id               AS interval_id,
        display_name                   AS name,
        DATE_FORMAT(start, "%m/%d/%Y") AS start,
        DATE_FORMAT(end,   "%m/%d/%Y") AS end,
        custom                         AS is_custom,
        query_params                   AS params
      FROM time_interval
    ';

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $intervals = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      if (!$row['is_custom']) {
        $row['params'] = json_decode($row['params'], 1);
      }
      $intervals[] = $row;
    }

    return $intervals;
  }

  /**
   * Return an array of months that are included in the query parameter
   * date range.
   *
   * Months that are only partially included in the date range are
   * included in the returned list of months.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getMonths(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_date');
    $qb->addSelectExpressions(array(
      'min_date' => "DATE_FORMAT(MIN(date), '%Y-%m')",
      'max_date' => "DATE_FORMAT(MAX(date), '%Y-%m')",
    ));
    $qb->setQueryParams($params);
    $qb->clearLimit();
    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check for empty date range
    if (!$row['min_date'] || !$row['max_date']) {
      return array();
    }

    list($year,    $month)    = explode('-', $row['min_date']);
    list($maxYear, $maxMonth) = explode('-', $row['max_date']);

    $months = array();

    while ($year < $maxYear || $month <= $maxMonth) {
      $months[] = array(
        'year'  => $year,
        'month' => $month,
      );

      $month++;
      if ($month === 13) {
        $month = 1;
        $year++;
      }
    }

    return $months;
  }

  /**
   * Convert a date to MM/DD/YYYY.
   *
   * @param string $date A date tin YYYY-MM-DD format.
   *
   * @return string
   */
  public static function formatDate($date)
  {
    if (preg_match('# ^ (\d{4}) - (\d\d) - (\d\d) $ #x', $date, $matches)) {
      return sprintf('%02d/%02d/%04d', $matches[2], $matches[3], $matches[1]);
    } else {
      throw new Exception("Invalid date format: '$date'");
    }
  }
}
