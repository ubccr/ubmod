<?php
/**
 * Time interval model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Time interval Model
 *
 * @package UBMoD
 **/
class UBMoD_Model_Interval
{

  /**
   * Returns an array of all time intervals.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT
        interval_id,
        time_interval,
        DATE_FORMAT(start, "%m/%d/%Y") AS start,
        DATE_FORMAT(end, "%m/%d/%Y") AS end
      FROM time_interval';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
