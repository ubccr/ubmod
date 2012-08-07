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
 * Tag REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Tag REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Tag
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_Tag
   */
  public static function factory()
  {
    return new Ubmod_Handler_Tag();
  }

  /**
   * Help for the "list" action.
   *
   * @return void
   */
  public function listHelp()
  {
    $desc = 'Returns a list of tags. Results will be an array where'
      . ' individual records will consist of (name).';
    $options = array(
      'query' => 'Return tags that begin with this string.',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Returns a list of tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function listAction(array $arguments, array $postData = null)
  {
    if (isset($arguments['query']) && $arguments['query'] !== '') {
      $tags = Ubmod_Model_Tag::getMatching($arguments['query']);
    } else {
      $tags = Ubmod_Model_Tag::getAll();
    }

    return Ubmod_RestResponse::factory(array('results' => $tags));
  }

  /**
   * Help for the "tree" action.
   *
   * @return void
   */
  public function treeHelp()
  {
    $desc = 'Returns a tree of tags.';
    return Ubmod_RestResponse::factory(array('message' => $desc));
  }

  /**
   * Returns a tree of tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function treeAction(array $arguments, array $postData = null)
  {
    return Ubmod_RestResponse::factory(array(
      'results' => Ubmod_Model_Tag::getTree($arguments['node']),
    ));
  }

  /**
   * Help for the "createTree" action.
   *
   * @return void
   */
  public function createTreeHelp()
  {
    $desc = 'Create nodes in a tree of tags.';
    return Ubmod_RestResponse::factory(array('message' => $desc));
  }

  /**
   * Create nodes in a tree of tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function createTreeAction(array $arguments, array $postData = null)
  {

    // Ext.data.TreeStore.sync posts raw data
    $rawPostData = file_get_contents('php://input');

    try {
      $data = json_decode($rawPostData, true);
    } catch (Exception $e) {
      $msg = "Failed to decode post data: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    // Check if the data is an array or a single object
    if (substr($rawPostData, 0, 1) === '[') {
      $nodes = $data;
    } else {
      $nodes = array($data);
    }

    try {
      $results = Ubmod_Model_Tag::createTreeNodes($nodes);
    } catch (Exception $e) {
      $msg = "Failed to create tree nodes: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    return Ubmod_RestResponse::factory(array('results' => $results));
  }

  /**
   * Help for the "updateTree" action.
   *
   * @return void
   */
  public function updateTreeHelp()
  {
    $desc = 'Updates a tree of tags.';
    return Ubmod_RestResponse::factory(array('message' => $desc));
  }

  /**
   * Updates a tree of tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function updateTreeAction(array $arguments, array $postData = null)
  {

    // Ext.data.TreeStore.sync posts raw data
    $rawPostData = file_get_contents('php://input');

    try {
      $data = json_decode($rawPostData, true);
    } catch (Exception $e) {
      $msg = "Failed to decode post data: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    // Check if the data is an array or a single object
    if (substr($rawPostData, 0, 1) === '[') {
      $nodes = $data;
    } else {
      $nodes = array($data);
    }

    try {
      $results = Ubmod_Model_Tag::updateTreeNodes($nodes);
    } catch (Exception $e) {
      $msg = "Failed to update tree: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    return Ubmod_RestResponse::factory(array('results' => $results));
  }

  /**
   * Help for the "deleteTree" action.
   *
   * @return void
   */
  public function deleteTreeHelp()
  {
    $desc = 'Delete nodes from a tree of tags.';
    return Ubmod_RestResponse::factory(array('message' => $desc));
  }

  /**
   * Delete nodes from a tree of tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function deleteTreeAction(array $arguments, array $postData = null)
  {

    // Ext.data.TreeStore.sync posts raw data
    $rawPostData = file_get_contents('php://input');

    try {
      $data = json_decode($rawPostData, true);
    } catch (Exception $e) {
      $msg = "Failed to decode post data: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    // Check if the data is an array or a single object
    if (substr($rawPostData, 0, 1) === '[') {
      $nodes = $data;
    } else {
      $nodes = array($data);
    }

    try {
      Ubmod_Model_Tag::deleteTreeNodes($nodes);
    } catch (Exception $e) {
      $msg = "Failed to delete nodes: " . $e->getMessage();
      return Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $msg,
      ));
    }

    return Ubmod_RestResponse::factory();
  }

  /**
   * Help for the "keyList" action.
   *
   * @return void
   */
  public function keyListHelp()
  {
    $desc = 'Returns a list of tag keys. Results will be an array where'
      . ' individual records will consist of (name).';
    $options = array(
      'query' => 'Return tag keys that begin with this string.',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Returns a list of tag keys.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function keyListAction(array $arguments, array $postData = null)
  {
    $tagKeys = Ubmod_Model_Tag::getKeysMatching($arguments['query']);

    $keys = array();
    foreach ($tagKeys as $key) {
      $keys[] = array('name' => $key);
    }

    return Ubmod_RestResponse::factory(array('results' => $keys));
  }

  /**
   * Help for the "activity" action.
   *
   * @return void
   */
  public function activityHelp()
  {
    $desc = 'Returns tag activity. Results will be an array where'
      . ' individual records will consist of (tag, display_name, jobs, cput,'
      . ' wallt, avg_wait, avg_cpus, avg_mem).';
    $options = array(
      'interval_id' => 'Return user activity in this interval. (required)',
      'cluster_id'  => 'Return user activity in this cluster. (required)',
      'filter'      => 'Filter criteria.  Substring match against user field.',
      'sort'        => 'Sort field.  Valid options: user, jobs, avg_cpus,'
                     . ' avg_wait, wallt, avg_mem',
      'dir'         => 'Sort direction.  Valid options: ASC, DESC',
      'start'       => 'Limit offset. (requires limit)',
      'limit'       => 'Maximum number of entities to return. (requires start)',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Returns tag activity.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function activityAction(array $arguments, array $postData = null)
  {
    $params = Ubmod_Model_QueryParams::factory($arguments);

    $columns = array(
      'tag'      => 'Tag',
      'jobs'     => '# Jobs',
      'avg_cpus' => 'Avg. Job Size (cpus)',
      'avg_wait' => 'Avg. Wait Time (h)',
      'wallt'    => 'Wall Time (d)',
      'avg_mem'  => 'Avg. Mem (MB)',
    );

    return Ubmod_RestResponse::factory(array(
      'results'  => Ubmod_Model_Tag::getActivityList($params),
      'total'    => Ubmod_Model_Tag::getActivityCount($params),
      'columns'  => $columns,
      'filename' => 'tags',
    ));
  }
}
