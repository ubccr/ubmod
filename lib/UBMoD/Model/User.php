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
   * Return the number of users with activities.
   *
   * @param array params The parameters for the query
   * @return int
   */
  public static function getActivityCount($params)
  {
    $dbh = UBMoD_DBService::dbh();
    $sql = 'SELECT count(*)
      FROM user u
      JOIN user_activity ua
        ON u.user_id = ua.user_id
        AND ua.interval_id = :interval_id
        AND ua.cluster_id = :cluster_id
      JOIN activity a
        ON ua.activity_id = a.activity_id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    ));
    $result = $stmt->fetch();
    return $result[0];
  }

  /**
   * Retuns an array of users joined with their activities.
   *
   * @param array params The parameters for the query
   * @return array
   */
  public static function getActivities($params)
  {
    $dbh = UBMoD_DBService::dbh();

    $sql = 'SELECT *
      FROM user u
      JOIN user_activity ua
        ON u.user_id = ua.user_id
        AND ua.interval_id = :interval_id
        AND ua.cluster_id = :cluster_id
      JOIN activity a
        ON ua.activity_id = a.activity_id';

    $dbParams = array(
      ':interval_id' => $params['interval_id'],
      ':cluster_id'  => $params['cluster_id'],
    );

    if ($params['filter'] != '') {
      $sql .= ' WHERE u.user LIKE :filter';
      $dbParams[':filter'] = '%' . $params['filter'] . '%';
    }

    if (isset($params['sort'])) {
      $sql .= sprintf(' ORDER BY %s %s', $params['sort'], $params['dir']);
    }

    $sql .= sprintf(' LIMIT %d, %d', $params['start'], $params['limit']);

    $stmt = $dbh->prepare($sql);
    $stmt->execute($dbParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
