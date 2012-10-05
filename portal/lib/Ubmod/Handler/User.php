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
 * User REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * User REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_User extends Ubmod_BaseHandler
{

  /**
   * Help for the "tags" action.
   *
   * @return Ubmod_RestResponse
   */
  public function tagsHelp()
  {
    $desc = 'Returns users and their tags.';
    $options = array(
      'filter' => 'Filter criteria.  Substring match against user field.',
      'sort'   => 'Sort field.  Valid options: user, jobs, avg_cpus,'
               . ' avg_wait, wallt, avg_mem',
      'dir'    => 'Sort direction.  Valid options: ASC, DESC',
      'start'  => 'Limit offset. (requires limit)',
      'limit'  => 'Maximum number of entities to return. (requires start)',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Returns users and their tags.
   *
   * @param array $arguments Request GET data.
   * @param array $postData Request POST data.
   *
   * @return Ubmod_RestResponse
   */
  public function tagsAction(array $arguments, array $postData = null)
  {
    $params = Ubmod_Model_QueryParams::factory($arguments);

    return Ubmod_RestResponse::factory(array(
      'results' => Ubmod_Model_User::getTags($params),
      'total'   => Ubmod_Model_User::getTagsCount($params),
    ));
  }

  /**
   * Help for the "addTag" action.
   *
   * @return Ubmod_RestResponse
   */
  public function addTagHelp()
  {
    $desc = 'Adds a tag to one or more users.';
    $options = array(
      'tag'     => 'The tag to add.',
      'userIds' => 'An array of user ids.',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Add a tag to one or more users.
   *
   * @param array $arguments Request GET data.
   * @param array $postData Request POST data.
   *
   * @return Ubmod_RestResponse
   */
  public function addTagAction(array $arguments, array $postData = null)
  {
    $tag     = $postData['tag'];
    $userIds = $postData['userIds'];

    return Ubmod_RestResponse::factory(array(
      'results' => array(
        'success' => Ubmod_Model_User::addTag($tag, $userIds),
      )
    ));
  }

  /**
   * Help for the "updateTags" action.
   *
   * @return Ubmod_RestResponse
   */
  public function updateTagsHelp()
  {
    $desc = 'Updates the tags for a single users. Returns the users tags';
    $options = array(
      'userId' => 'The id of the user to update.',
      'tags'   => 'An array of tags.',
    );
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Update the tags for a given user.
   *
   * @param array $arguments Request GET data.
   * @param array $postData Request POST data.
   *
   * @return Ubmod_RestResponse
   */
  public function updateTagsAction(array $arguments, array $postData = null)
  {
    $tags   = isset($postData['tags']) ? $postData['tags'] : array();
    $userId = $postData['userId'];

    return Ubmod_RestResponse::factory(array(
      'results' => array(
        'tags' => Ubmod_Model_User::updateTags($userId, $tags),
      )
    ));
  }
}

