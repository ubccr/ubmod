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
 * Tag model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
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
   * Returns all the tags in the database.
   *
   * @return array All the tag names.
   */
  public static function getAll()
  {
    $sql = "
      SELECT DISTINCT tags
      FROM dim_user
      WHERE tags IS NOT NULL
    ";

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $tags = array();

    while ($row = $stmt->fetch()) {
      $tags = array_merge($tags, json_decode($row['tags']));
    }

    $tags = array_unique($tags);

    natcasesort($tags);

    return array_values($tags);
  }

  /**
   * Returns all the tags in the database that match the given string.
   *
   * Matching is case-insensitive. A tag is considered a match if the
   * given string prefix of the tag.
   *
   * @param string $query The string to match against.
   *
   * @return array All the tag names that match.
   */
  public static function getMatching($query)
  {
    // Use lowercase for case-insensitive matching
    $query = strtolower($query);

    // Substring length
    $length = strlen($query);

    $tags = array();

    foreach (self::getAll() as $tag) {
      if (strtolower(substr($tag, 0, $length)) === $query) {
        $tags[] = $tag;
      }
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
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_user');
    $qb->addSelectExpressions(array(
      'user_id' => 'dim_user_id',
      'tags'    => "COALESCE(tags, '[]')",
    ));
    $qb->setQueryParams($params);
    $qb->setGroupBy('user_id');
    $qb->clearLimit();
    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $tags = array();

    while ($row = $stmt->fetch()) {
      $tags = array_merge($tags, json_decode($row['tags']));
    }

    $tags = array_unique($tags);

    $filtered = array();

    if ($params->hasFilter()) {
      $filter = $params->getFilter();

      foreach ($tags as $tag) {
        if (stripos($tag, $filter) !== false) {
          $filtered[] = $tag;
        }
      }
    } else {
      $filtered = $tags;
    }

    return count($filtered);
  }

  /**
   * Returns activity data for all tags that have activity for the given
   * parameters.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getActivity(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addDimensionTable('dim_user');
    $qb->addSelectExpressions(array(
      'user_id'      => 'dim_user_id',
      'jobs'         => 'COUNT(*)',
      'wallt'        => 'ROUND(SUM(wallt) / 86400, 1)',
      'avg_wallt'    => 'ROUND(AVG(wallt) / 86400, 1)',
      'max_wallt'    => 'ROUND(MAX(wallt) / 86400, 1)',
      'cput'         => 'ROUND(SUM(cput)  / 86400, 1)',
      'avg_cput'     => 'ROUND(AVG(cput)  / 86400, 1)',
      'max_cput'     => 'ROUND(MAX(cput)  / 86400, 1)',
      'avg_mem'      => 'ROUND(AVG(mem)   / 1024,  1)',
      'max_mem'      => 'ROUND(MAX(mem)   / 1024,  1)',
      'avg_vmem'     => 'ROUND(AVG(vmem)  / 1024,  1)',
      'max_vmem'     => 'ROUND(MAX(vmem)  / 1024,  1)',
      'avg_wait'     => 'ROUND(AVG(wait)  / 3600,  1)',
      'avg_exect'    => 'ROUND(AVG(exect) / 3600,  1)',
      'max_nodes'    => 'ROUND(MAX(nodes),         1)',
      'avg_nodes'    => 'ROUND(AVG(nodes),         1)',
      'max_cpus'     => 'ROUND(MAX(cpus),          1)',
      'avg_cpus'     => 'ROUND(AVG(cpus),          1)',
    ));
    $qb->setQueryParams($params);
    $qb->setGroupBy('user_id');
    $qb->addWhereClause('tags', 'LIKE');
    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);

    if ($params->hasFilter()) {
      $filter = $params->getFilter();
    }

    $activity = array();
    foreach (self::getAll() as $tag) {

      if (isset($filter)) {
        // Skip tags that don't match
        if (stripos($tag, $filter) === false) {
          continue;
        }
      }

      $dbParams[':tags'] = '%' . json_encode($tag) . '%';
      $r = $stmt->execute($dbParams);
      if (!$r) {
        $err = $stmt->errorInfo();
        throw new Exception($err[2]);
      }
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      $row['tag'] = $tag;

      $activity[] = $row;
    }

    $sortFields
      = array('tag', 'jobs', 'avg_cpus', 'avg_wait', 'wallt', 'avg_mem');

    if ($params->hasOrderByColumn()) {
      $column = $params->getOrderByColumn();

      if (!in_array($column, $sortFields)) {
        $column  = 'wallt';
      }
      $dir = $params->isOrderByDescending() ? 'DESC' : 'ASC';

      usort($activity, function($a, $b) use($column, $dir) {
        if ($column === 'tag') {
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

    if ($params->hasLimitRowCount() && $params->hasLimitOffset()) {
      $activity = array_slice($activity, $params->getLimitOffset(),
        $params->getLimitRowCount());
    }

    return $activity;
  }

  /**
   * Returns activity data for a given tag.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return array
   */
  public static function getActivityByName(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->addSelectExpressions(array(
      'jobs'      => 'COUNT(*)',
      'wallt'     => 'ROUND(SUM(wallt) / 86400, 1)',
      'avg_wallt' => 'ROUND(AVG(wallt) / 86400, 1)',
      'max_wallt' => 'ROUND(MAX(wallt) / 86400, 1)',
      'cput'      => 'ROUND(SUM(cput)  / 86400, 1)',
      'avg_cput'  => 'ROUND(AVG(cput)  / 86400, 1)',
      'max_cput'  => 'ROUND(MAX(cput)  / 86400, 1)',
      'avg_mem'   => 'ROUND(AVG(mem)   / 1024,  1)',
      'max_mem'   => 'ROUND(MAX(mem)   / 1024,  1)',
      'avg_vmem'  => 'ROUND(AVG(vmem)  / 1024,  1)',
      'max_vmem'  => 'ROUND(MAX(vmem)  / 1024,  1)',
      'avg_wait'  => 'ROUND(AVG(wait)  / 3600,  1)',
      'avg_exect' => 'ROUND(AVG(exect) / 3600,  1)',
      'max_nodes' => 'ROUND(MAX(nodes),         1)',
      'avg_nodes' => 'ROUND(AVG(nodes),         1)',
      'max_cpus'  => 'ROUND(MAX(cpus),          1)',
      'avg_cpus'  => 'ROUND(AVG(cpus),          1)',
    ));
    $qb->setQueryParams($params);
    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $sql = Ubmod_DataWarehouse::optimize($sql);
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

    $tag['name'] = $params->getTag();

    return $tag;
  }
}
