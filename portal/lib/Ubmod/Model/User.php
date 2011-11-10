<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
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
    return count(self::getTagsUnlimited($params));
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
    $users = self::getTagsUnlimited($params);

    $sortFields = array('name', 'display_name', 'group', 'tags');

    if ($params->hasOrderByColumn()) {
      $column = $params->getOrderByColumn();
      $dir    = $params->isOrderByDescending() ? 'DESC' : 'ASC';

      if (!in_array($column, $sortFields)) {
        $column = 'name';
        $dir    = 'ASC';
      }

      if ($column === 'tags') { $column = 'tags_json'; }

      usort($users, function($a, $b) use($column, $dir) {
        if ($dir === 'ASC') {
          return strcasecmp($a[$column], $b[$column]);
        } else {
          return strcasecmp($b[$column], $a[$column]);
        }
      });
    }

    if ($params->hasLimitRowCount()) {
      $users = array_slice($users, $params->getLimitOffset(),
        $params->getLimitRowCount());
    }

    foreach ($users as &$user) {
      $user['tags'] = json_decode($user['tags_json'], 1);
      unset($user['tags_json']);
    }

    return $users;
  }

  /**
   * Returns an array of users and their tags.
   *
   * Doesn't apply any sorting or limiting.
   *
   * @return array
   */
  private static function getTagsUnlimited(Ubmod_Model_QueryParams $params)
  {
    // The combination of SUBSTRING_INDEX and GROUP_CONCAT used below
    // selects the last group for each user when the groups are order
    // by date.
    $sql = "
      SELECT
        dim_user_id           AS user_id,
        COALESCE(tags, '[]')  AS tags_json,
        dim_user.name         AS name,
        dim_user.display_name AS display_name,
        SUBSTRING_INDEX(
          GROUP_CONCAT(dim_group.name ORDER BY dim_date.date DESC),
          ',',
          1
        ) AS `group`
      FROM fact_job
      JOIN dim_user  USING (dim_user_id)
      JOIN dim_group USING (dim_group_id)
      JOIN dim_date  USING (dim_date_id)
      GROUP BY dim_user_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $users = $stmt->fetchAll();

    if ($params->hasFilter()) {
      $filter   = $params->getFilter();
      $filtered = array();

      foreach ($users as $user) {
        if (   strpos($user['name'],         $filter) !== false
            || strpos($user['display_name'], $filter) !== false
            || strpos($user['group'],        $filter) !== false) {
          $filtered[] = $user;
        }
      }

      $users = $filtered;
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
    $tag = Ubmod_Model_Tag::normalize($tag);

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

    foreach ($tags as &$tag) {
      $tag = Ubmod_Model_Tag::normalize($tag);
    }

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

  /**
   * Find a user's current group.
   *
   * Returns group information for the group that the specified user has
   * most recently submitted a job.
   *
   * @param int $userId The user dimension primary key.
   *
   * @return array Array containing the group name and display name.
   */
  public static function getGroup($userId)
  {
    $groups = self::getGroups($userId);
    return $groups[0];
  }

  /**
   * Find all of a user's groups.
   *
   * Return group information for all groups that for which the specified
   * user has most recently submitted a job.
   *
   * Ordered from most recent to least recent.
   *
   * @param int $userId The user dimension primary key.
   *
   * @return array
   */
  public static function getGroups($userId)
  {
    $sql = '
      SELECT
        dim_group_id       AS group_id,
        MAX(dim_date.date) AS last_date,
        name,
        display_name
      FROM fact_job
      JOIN dim_group USING (dim_group_id)
      JOIN dim_date  USING (dim_date_id)
      WHERE dim_user_id = :dim_user_id
      GROUP BY dim_group_id
      ORDER BY last_date DESC
    ';

    $sql = Ubmod_DataWarehouse::optimize($sql);

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':dim_user_id' => $userId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
