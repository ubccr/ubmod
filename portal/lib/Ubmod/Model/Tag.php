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
 * Tag model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Tag model.
 *
 * @package Ubmod
 */
class Ubmod_Model_Tag
{

  /**
   * Return the ID for a given tag.
   *
   * @param string $name The tag name
   *
   * @return int The dim_tag_id
   */
  public static function getTagId($name)
  {
    $sql = "SELECT dim_tag_id FROM dim_tag WHERE name = :name";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':name' => $name));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    if ($tagId = $stmt->fetch(PDO::FETCH_COLUMN, 0)) {
      return $tagId;
    } else {
      return null;
    }
  }

  /**
   * Create a new tag.
   *
   * @param string $name The tag name
   *
   * @return int The dim_tag_id
   */
  public static function createTag($name)
  {
    $sql = "
      INSERT INTO dim_tag SET
        name  = :name,
        `key` = :key,
        value = :value
    ";

    list($key, $value) = self::splitTag($name);

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(
      ':name'  => $name,
      ':key'   => $key,
      ':value' => $value,
    ));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $dbh->lastInsertId();
  }

  /**
   * Returns all the tags in the database.
   *
   * @return array All the tag names.
   */
  public static function getAll()
  {
    $sql = "
      SELECT
        dim_tag_id AS tag_id,
        parent_id,
        name,
        `key`,
        value
      FROM dim_tag
      ORDER BY name
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns all the tag keys in the database.
   *
   * @return array
   */
  public static function getKeys()
  {
    $sql = "SELECT DISTINCT `key` FROM dim_tag ORDER BY `key`";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Return all the tags in tree form.
   *
   * @param integer $parentId The id of the tree parent node (optional).
   *
   * @return array
   */
  public static function getTree($parentId = null)
  {

    // Normalize parent_id.  Must be null or a positive integer.
    if ($parentId == 0) {
      $parentId = null;
    } else {
      $parentId = (int)$parentId;
    }

    // If the parent_id is not null or zero limit the query to those
    // that are descendants of the parent node.
    $whereClause = '';
    $params = array();
    if ($parentId !== null) {
      $whereClause
        = "WHERE parent_id = :parent_id OR path LIKE :path_pattern";
      $params = array(
        ':parent_id'    => $parentId,
        ':path_pattern' => '%/' . $parentId . '/%',
      );
    }

    // Ordering by path length guarantees that children will be after
    // their parent.
    $sql = "
      SELECT
        dim_tag_id AS tag_id,
        name,
        `key`,
        value,
        parent_id
      FROM dim_tag
      $whereClause
      ORDER BY LENGTH(path), name
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($params);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $tree = array();

    // Holds references to tag nodes for easy lookup.
    $tagList = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

      // "results" are children
      $row['results'] = array();

      if ($row['parent_id'] == $parentId) {

        // The tag doesn't have a parent, so add the node to the root of
        // the tree.
        $tree[] = $row;

        // Create a reference to the tag node so it can be found easily
        // by it's children.
        $tagList[$row['tag_id']] =& $tree[count($tree) - 1];

      } else {

        // The tag has a parent, so add it as a child.
        $parent =& $tagList[$row['parent_id']];
        $parent['results'][] = $row;

        // Create a reference to the tag node so it can be found easily
        // by it's children.
        $childIdx = count($parent['results']) - 1;
        $tagList[$row['tag_id']] =& $parent['results'][$childIdx];

      }
    }

    return $tree;
  }

  /**
   * Create nodes in a tag tree.
   *
   * @param array $nodes Tag tree nodes.
   *
   * @return array The new tag tree nodes.
   */
  public static function createTreeNodes(array $nodes)
  {
    $results = array();

    foreach ($nodes as $node) {
      $results[] = self::createTreeNode($node);
    }

    return $results;
  }

  /**
   * Create a new tag.
   *
   * @param array $node Tag node data.
   *
   * @return array Tag tree node.
   */
  public static function createTreeNode(array $node)
  {
    $parentId = null;
    $path = '/';

    if (is_numeric($node['parentId'])) {
      $parentId = $node['parentId'];

      $parent = self::getTagById($parentId);
      $path = $parent['path'] . $parentId . '/';
    }

    $sql = "
      INSERT INTO dim_tag SET
        parent_id = :parent_id,
        path      = :path,
        name      = :name,
        `key`     = :key,
        value     = :value
    ";

    list($node['key'], $node['value']) = self::splitTag($node['name']);

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(
      ':parent_id'  => $parentId,
      ':path'       => $path,
      ':name'       => $node['name'],
      ':key'        => $node['key'],
      ':value'      => $node['value'],
    ));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $node['tag_id']    = $dbh->lastInsertId();
    $node['parent_id'] = $parentId;
    $node['path']      = $path;

    // No children.
    $node['results']    = array();
    $node['expandable'] = false;

    return $node;
  }

  /**
   * Update nodes in a tag tree.
   *
   * @param array $nodes Tag tree nodes.
   *
   * @return array The updated tag tree nodes.
   */
  public static function updateTreeNodes(array $nodes)
  {
    $results = array();

    foreach ($nodes as $node) {
      $results[] = self::updateTreeNode($node);
    }

    return $results;
  }

  /**
   * Updates the tree structure of the tags in the database.
   *
   * @param array $tree A flat array of tree nodes.
   *
   * @return array The updated node.
   */
  public static function updateTreeNode(array $node)
  {
    $parentId = null;
    $path = '/';

    if (is_numeric($node['parentId'])) {
      $parentId = $node['parentId'];

      $parent = self::getTagById($parentId);
      $path = $parent['path'] . $parentId . '/';
    }

    $sql = "
      UPDATE dim_tag SET
        parent_id = :parent_id,
        path      = :path
      WHERE dim_tag_id = :dim_tag_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(
      ':parent_id'  => $parentId,
      ':path'       => $path,
      ':dim_tag_id' => $node['tag_id'],
    ));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    # TODO: Need to update child paths
    # self::updateChildPath($node['id']);
    # NOTE: This may not be necessary, Ext JS appears to supply the
    # nodes ordered such that the paths are always updated properly.

    return $node;
  }

  /**
   * Delete tags.
   *
   * @param array $nodes An array of tags.
   */
  public static function deleteTreeNodes(array $nodes)
  {
    $sql = "
      DELETE dim_tag, br_user_to_tag, br_tags_to_tag
      FROM dim_tag
      LEFT JOIN br_user_to_tag
        ON dim_tag.dim_tag_id = br_user_to_tag.dim_tag_id
      LEFT JOIN br_tags_to_tag
        ON dim_tag.dim_tag_id = br_tags_to_tag.dim_tag_id
      WHERE dim_tag.dim_tag_id = :dim_tag_id
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);

    foreach ($nodes as $node) {
      $r = $stmt->execute(array(':dim_tag_id' => $node['tag_id']));
      if (!$r) {
        $err = $stmt->errorInfo();
        throw new Exception($err[2]);
      }
    }
  }

  /**
   * Returns all the tag values associated with a tag key.
   *
   * @param string $key A tag key.
   *
   * @return array
   */
  public static function getValuesForKey($key)
  {
    $sql = "
      SELECT DISTINCT value
      FROM dim_tag
      WHERE `key` = :key
      ORDER BY value
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':key' => $key));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns all the tags in the database that match the given string.
   *
   * Matching is case-insensitive. A tag is considered a match if the
   * tag begins with the given string.
   *
   * @param string $query The string to match against.
   *
   * @return array All the tag names that match.
   */
  public static function getMatching($query)
  {

    // Use lowercase for case-insensitive matching
    $query = strtolower($query);

    $sql = "
      SELECT
        dim_tag_id AS tag_id,
        parent_id,
        name,
        `key`,
        value
      FROM dim_tag
      WHERE name LIKE :name
      ORDER BY name
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':name' => $query . '%'));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns all the tag keys in the database that match the given
   * string.
   *
   * Matching is case-insensitive. A tag key is considered a match if
   * tag key begins with the given string.
   *
   * @param string $query The string to match against.
   *
   * @return array All the tag keys that match.
   */
  public static function getKeysMatching($query)
  {

    // Use lowercase for case-insensitive matching
    $query = strtolower($query);

    $sql = "SELECT DISTINCT `key` FROM dim_tag WHERE `key` LIKE :key";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':key' => $query . '%'));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
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
      SELECT dim_tag.name
      FROM dim_tag
      JOIN br_user_to_tag USING (dim_tag_id)
      JOIN dim_user       USING (dim_user_id)
      WHERE dim_user_id = :dim_user_id
      ORDER BY dim_tag.name
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
   * Returns an array of user tags that have activity for the given
   * parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  private static function getUserTagsWithActivity(
    Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_user');
    $qb->addSelectExpression('DISTINCT fact_job.dim_user_id', 'user_id');
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
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (count($userIds) === 0) {
      return array();
    }

    $sql = "
      SELECT DISTINCT dim_tag.dim_tag_id, parent_id, path, name, `key`, value
      FROM dim_tag
      JOIN br_user_to_tag USING (dim_tag_id)
      WHERE dim_user_id IN (" . implode(', ', $userIds) . ")
    ";
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $tags = array();

    // Prevent redundant queries by storing tag id numbers that are part
    // of the tag path.
    $seenIds = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tag) {
      $tags[] = $tag;
      $seenIds[$tag['dim_tag_id']] = true;

      $pathTagIds = explode('/', trim($tag['path'], '/'));
      foreach ($pathTagIds as $tagId) {
        if ($tagId === '') { continue; }
        if (isset($seenIds[$tagId])) { continue; }
        $tags[] = self::getTagById($tagId);
        $seenIds[$tagId] = true;
      }
    }

    return $tags;
  }

  /**
   * Returns an array of tags (from the tags dimension) that have
   * activity for the given parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  private static function getTagsTagsWithActivity(
    Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_tags');
    $qb->addSelectExpression('DISTINCT fact_job.dim_tags_id', 'tags_id');
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
    $tagsIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (count($tagsIds) == 0) {
      return array();
    }

    $sql = "
      SELECT DISTINCT dim_tag.dim_tag_id, parent_id, path, name, `key`, value
      FROM dim_tag
      JOIN br_tags_to_tag USING (dim_tag_id)
      WHERE dim_tags_id IN (" . implode(', ', $tagsIds) . ")
    ";
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $tags = array();

    // Prevent redundant queries by storing tag id numbers that are part
    // of the tag path.
    $seenIds = array();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tag) {
      $tags[] = $tag;
      $seenIds[$tag['dim_tag_id']] = true;

      $pathTagIds = explode('/', trim($tag['path'], '/'));
      foreach ($pathTagIds as $tagId) {
        if ($tagId === '') { continue; }
        if (isset($seenIds[$tagId])) { continue; }
        $tags[] = self::getTagById($tagId);
        $seenIds[$tagId] = true;
      }
    }

    return $tags;
  }

  /**
   * Returns an array of tags that have activity for the given
   * parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getTagsWithActivity(Ubmod_Model_QueryParams $params)
  {
    $tags = array_merge(
      self::getUserTagsWithActivity($params),
      self::getTagsTagsWithActivity($params)
    );

    // Filter tags by keyword
    if ($params->hasFilter()) {
      $regex = $params->getRegexFilter();

      $filtered = array();

      foreach ($tags as $tag) {
        if (preg_match($regex, $tag['name'])) {
          $filtered[] = $tag;
        }
      }

      $tags = $filtered;
    }

    // Filter tags by tag key
    if ($params->hasTagKey()) {
      $key = $params->getTagKey();

      $filtered = array();

      foreach ($tags as $tag) {
        if ($tag['key'] === $key) {
          $filtered[] = $tag;
        }
      }

      $tags = $filtered;
    }

    // Filter tags by parent id
    if ($params->hasTagParentId()) {
      $parentId = $params->getTagParentId();

      $filtered = array();

      foreach ($tags as $tag) {
        if ($tag['parent_id'] === $parentId) {
          $filtered[] = $tag;
        }
      }

      $tags = $filtered;
    }

    return $tags;
  }

  /**
   * Returns the number of tags that have activity for the given
   * parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return int
   */
  public static function getActivityCount(Ubmod_Model_QueryParams $params)
  {
    return count(self::getTagsWithActivity($params));
  }

  /**
   * Returns activity data for all tags that have activity for the given
   * parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getActivityList(Ubmod_Model_QueryParams $params)
  {
    $activity = array();

    // Copy the params since they are changed by this method.
    $tagParams = clone $params;

    // It isn't possible to GROUP BY a tag, so a separate query is
    // performed for each tag.
    foreach (self::getTagsWithActivity($tagParams) as $tag) {
      $tagParams->setTag($tag['name']);
      $tagActivity = Ubmod_Model_Job::getActivity($tagParams);

      $tagActivity['name']      = $tag['name'];
      $tagActivity['tag_id']    = $tag['dim_tag_id'];
      $tagActivity['tag_key']   = $tag['key'];
      $tagActivity['tag_value'] = $tag['value'];

      $activity[] = $tagActivity;
    }

    $sortFields
      = array('name', 'jobs', 'avg_cpus', 'avg_wait', 'wallt', 'avg_mem');

    if ($tagParams->hasOrderByColumn()) {
      $column = $tagParams->getOrderByColumn();

      if (!in_array($column, $sortFields)) {
        $column  = 'wallt';
      }
      $dir = $tagParams->isOrderByDescending() ? 'DESC' : 'ASC';

      usort($activity, function($a, $b) use($column, $dir) {
        if ($column === 'name') {
          if ($dir === 'ASC') {
            return strcasecmp($a[$column], $b[$column]);
          } else {
            return strcasecmp($b[$column], $a[$column]);
          }
        } else {
          if ($dir === 'ASC') {
            return $a[$column] > $b[$column];
          } else {
            return $b[$column] > $a[$column];
          }
        }
      });
    }

    if ($tagParams->hasLimitRowCount() && $tagParams->hasLimitOffset()) {
      $activity = array_slice($activity, $tagParams->getLimitOffset(),
        $tagParams->getLimitRowCount());
    }

    return $activity;
  }

  /**
   * Normalize a tag.
   *
   * @param string $tag A tag.
   *
   * @return string
   */
  public static function normalize($tag)
  {
    return trim($tag);
  }

  /**
   * Split a tag into a key value pair.
   *
   * @param string $tag The tag to split.
   *
   * @return array Array containing the key and value.
   */
  public static function splitTag($tag)
  {
    // If the tag isn't a key value pair, return null for both the key
    // and value.
    if (strpos($tag, '=') === false) {
      return array(null, null);
    }

    return explode('=', $tag, 2);
  }

  /**
   * Retrieve tag data.
   *
   * @param integer $tagId The tag primary key.
   *
   * @return array
   */
  public static function getTagById($tagId)
  {
    $sql = "SELECT * FROM dim_tag WHERE dim_tag_id = :dim_tag_id";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':dim_tag_id' => $tagId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Retrieve tag data.
   *
   * @param string $name The tag name.
   *
   * @return array
   */
  public static function getTagByName($name)
  {
    $sql = "SELECT * FROM dim_tag WHERE name = :name";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':name' => $name));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Check if a tag has children.
   *
   * @param integer $tagId The tag primary key.
   *
   * @return bool
   */
  public static function hasChildren($tagId)
  {
    $sql = "SELECT COUNT(*) FROM dim_tag WHERE parent_id = :tag_id";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array(':tag_id' => $tagId));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $row = $stmt->fetch(PDO::FETCH_NUM);

    return $row[0] > 0;
  }
}

