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
   * Return time interval data given a interval id.
   *
   * @param int id The interval id
   * @return array
   */
  public static function getById($id)
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT
        interval_id,
        time_interval,
        DATE_FORMAT(start, "%m/%d/%Y") as start,
        DATE_FORMAT(end, "%m/%d/%Y") as end
      FROM time_interval
      WHERE interval_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

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
