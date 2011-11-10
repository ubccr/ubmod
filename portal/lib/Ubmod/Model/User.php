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
 * User model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * User Model.
 *
 * @package Ubmod
 */
class Ubmod_Model_User
{

  /**
   * Returns the number of users for the given parameters.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getTagsCount(Ubmod_Model_QueryParams $params)
  {
    $sql = 'SELECT COUNT(*) FROM dim_user';

    $dbParams = array();
    if ($params->hasFilter()) {
      $sql .= ' WHERE name LIKE :filter';
      $dbParams[':filter'] = '%' . $params->getFilter() . '%';
    }

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $result = $stmt->fetch();

    return $result[0];
  }

  /**
   * Returns an array of users and their tags.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getTags(Ubmod_Model_QueryParams $params)
  {
    $sql = "
      SELECT
        dim_user_id          AS user_id,
        name                 AS user,
        COALESCE(tags, '[]') AS tags
      FROM dim_user
    ";

    $dbParams = array();
    if ($params->hasFilter()) {
      $sql .= ' WHERE name LIKE :filter';
      $dbParams[':filter'] = '%' . $params->getFilter() . '%';
    }

    $sortFields = array('user', 'tags');

    if ($params->hasOrderByColumn()) {
      $column = $params->getOrderByColumn();
      if (!in_array($column, $sortFields)) { $column = 'user'; }
      $dir = $params->isOrderByDescending() ? 'DESC' : 'ASC';
      $sql .= sprintf(' ORDER BY %s %s', $column, $dir);
    }

    if ($params->hasLimitRowCount()) {
      $sql .= sprintf(' LIMIT %d', $params->getLimitRowCount());
      if ($params->hasLimitOffset()) {
        $sql .= sprintf(' OFFSET %d', $params->getLimitOffset());
      }
    }

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $users = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $row['tags'] = json_decode($row['tags'], 1);
      $users[] = $row;
    }

    return $users;
  }

  /**
   * Add a tag to a list of users.
   *
   * @param string $tag     The tag to add to the users.
   * @param array  $userIds An array for user keys (dim_user_id).
   *
   * @return bool
   */
  public static function addTag($tag, array $userIds)
  {
    $selectSql = "
      SELECT COALESCE(tags, '[]') AS tags
      FROM dim_user
      WHERE dim_user_id = :dim_user_id
    ";

    $updateSql = "
      UPDATE dim_user
      SET tags = :tags
      WHERE dim_user_id = :dim_user_id
    ";

    $dbh = Ubmod_DbService::dbh();

    $selectStmt = $dbh->prepare($selectSql);
    $updateStmt = $dbh->prepare($updateSql);

    foreach ($userIds as $userId) {
      $r = $selectStmt->execute(array(':dim_user_id' => $userId));
      if (!$r) {
        $err = $selectStmt->errorInfo();
        throw new Exception($err[2]);
      }
      $user = $selectStmt->fetch();

      $tags = json_decode($user['tags'], 1);

      if (!in_array($tag, $tags)) {
        $tags[] = $tag;
      } else {
        continue;
      }

      natcasesort($tags);
      $tags = array_values($tags);

      $r = $updateStmt->execute(array(
        ':tags'        => json_encode($tags),
        ':dim_user_id' => $userId,
      ));
      if (!$r) {
        $err = $updateStmt->errorInfo();
        throw new Exception($err[2]);
      }
    }

    return true;
  }

  /**
   * Update the tags for a single user.
   *
   * @param int   $userId The id of the user to update.
   * @param array $tags   The user's tags.
   *
   * @return bool
   */
  public static function updateTags($userId, array $tags)
  {
    $tags = array_unique($tags);
    natcasesort($tags);
    $tags = array_values($tags);

    $sql = "
      UPDATE dim_user
      SET tags = :tags
      WHERE dim_user_id = :dim_user_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(
      ':tags'        => json_encode($tags),
      ':dim_user_id' => $userId,
    ));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $tags;
  }
}
