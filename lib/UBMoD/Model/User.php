<?php
/**
 * User model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * User Model
 *
 * @package UBMoD
 **/
class UBMoD_Model_User
{

  /**
   * Returns an array of all users.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT * FROM user ORDER BY user';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Retuns an array of all users joined with their activities.
   *
   * @return array
   */
  public static function getAllActivities()
  {
  }
}
