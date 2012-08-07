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

    foreach ($users as &$user) {
      $user['tags'] = Ubmod_Model_Tag::getTagsForUserId($user['user_id']);
      if (count($user['tags']) > 0) {
        $user['first_tag'] = $user['tags'][0];
      } else {
        $user['first_tag'] = '';
      }
    }

    if ($params->hasOrderByColumn()) {
      $column = $params->getOrderByColumn();
      $dir    = $params->isOrderByDescending() ? 'DESC' : 'ASC';

      if (!in_array($column, $sortFields)) {
        $column = 'name';
        $dir    = 'ASC';
      }

      if ($column === 'tags') { $column = 'first_tag'; }

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
      unset($user['first_tag']);
    }

    return $users;
  }

  /**
   * Returns an array of users and their tags.
   *
   * Doesn't apply any sorting or limiting.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  private static function getTagsUnlimited(Ubmod_Model_QueryParams $params)
  {
    $sql = "
      SELECT
        dim_user.dim_user_id   AS user_id,
        dim_user.name          AS name,
        dim_user.display_name  AS display_name,
        dim_user.current_group AS `group`
      FROM dim_user
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
      $regex = $params->getRegexFilter();

      $filtered = array();

      foreach ($users as $user) {
        if (   preg_match($regex, $user['name'])
            || preg_match($regex, $user['display_name'])
            || preg_match($regex, $user['group'])
        ) {
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
   * @param string $tag The tag to add to the users.
   * @param array $userIds An array for user keys (dim_user_id).
   *
   * @return bool
   */
  public static function addTag($tag, array $userIds)
  {
    $tagId = Ubmod_Model_Tag::getTagId($tag);

    if ($tagId === null) {
      $tagId = Ubmod_Model_Tag::createTag($tag);
    }

    $sql = "
      INSERT INTO br_user_to_tag SET
        dim_user_id = :dim_user_id,
        dim_tag_id  = :dim_tag_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);

    foreach ($userIds as $userId) {
      $r = $stmt->execute(array(
        ':dim_user_id' => $userId,
        ':dim_tag_id'  => $tagId,
      ));
      if (!$r) {
        $err = $stmt->errorInfo();
        throw new Exception($err[2]);
      }
    }

    return true;
  }

  /**
   * Update the tags for a single user.
   *
   * @param int $userId The id of the user to update.
   * @param array $tags The user's tags.
   *
   * @return bool
   */
  public static function updateTags($userId, array $tags)
  {
    $dbh = Ubmod_DbService::dbh();

    $sql = "DELETE FROM br_user_to_tag WHERE dim_user_id = :dim_user_id";
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':dim_user_id' => $userId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $sql = "
      INSERT INTO br_user_to_tag SET
        dim_user_id = :dim_user_id,
        dim_tag_id  = :dim_tag_id
    ";
    $stmt = $dbh->prepare($sql);

    foreach ($tags as $tag) {
      $tagId = Ubmod_Model_Tag::getTagId($tag);

      if ($tagId === null) {
        $tagId = Ubmod_Model_Tag::createTag($tag);
      }

      $r = $stmt->execute(array(
        ':dim_user_id' => $userId,
        ':dim_tag_id'  => $tagId,
      ));
      if (!$r) {
        $err = $stmt->errorInfo();
        throw new Exception($err[2]);
      }
    }

    return true;
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

  /**
   * Return the tags for the specified user.
   *
   * @param int $userId A dim_user_id
   *
   * @return array
   */
  public static function getTagsForUserId($userId)
  {
    $sql = "
      SELECT tag
      FROM dim_tag
      JOIN br_user_to_tag USING (dim_tag_id)
      JOIN dim_user       USING (dim_user_id)
      WHERE dim_user_id = :dim_user_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':dim_user_id' => $userId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
  }

  /**
   * Return the ID for a given tag.
   *
   * @param string $tag The tag string
   *
   * @return int The dim_tag_id
   */
  private static function getTagId($tag)
  {
    $sql = "SELECT dim_tag_id FROM dim_tag WHERE tag = :tag";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':tag' => $tag));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    if ($tag = $stmt->fetch(PDO::FETCH_ASSOC)) {
      return $tag;
    } else {
      return null;
    }
  }

  /**
   * Create a new tag.
   *
   * @param string $tag The tag string
   *
   * @return int The dim_tag_id
   */
  private static function createTag($tag)
  {
    $sql = "INSERT INTO dim_tag SET tag = :tag";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':tag' => $tag));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $dbh->lastInsertId();
  }
}

