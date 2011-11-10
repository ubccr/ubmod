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
 * User REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * User REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_User
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_User
   */
  public static function factory()
  {
    return new Ubmod_Handler_User();
  }

  /**
   * Help for the "activity" action.
   *
   * @return void
   */
  public function activityHelp()
  {
    $desc = 'Returns user activity. Results will be an array where individual'
      . ' records will consist of (user_id, user, display_name, jobs, cput,'
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
    return Ubmod_RestResponse::factory(TRUE, $desc, $options);
  }

  /**
   * Returns user activity.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function activityAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total' => Ubmod_Model_User::getActivityCount($arguments),
      'users' => Ubmod_Model_User::getActivity($arguments),
    ));
  }

  /**
   * Help for the "tags" action.
   *
   * @return void
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
    return Ubmod_RestResponse::factory(TRUE, $desc, $options);
  }

  /**
   * Returns users and their tags.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function tagsAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total' => Ubmod_Model_User::getTagsCount($arguments),
      'users' => Ubmod_Model_User::getTags($arguments),
    ));
  }

  /**
   * Help for the "addTag" action.
   *
   * @return void
   */
  public function addTagHelp()
  {
    $desc = 'Adds a tag to one or more users.';
    $options = array(
      'tag'     => 'The tag to add.',
      'userIds' => 'An array of user ids.',
    );
    return Ubmod_RestResponse::factory(TRUE, $desc, $options);
  }

  /**
   * Add a tag to one or more users.
   *
   * @param array $arguments
   * @param array $postData
   *
   * @return Ubmod_RestResponse
   */
  public function addTagAction(array $arguments, array $postData = NULL)
  {
    $tag     = $postData['tag'];
    $userIds = $postData['userIds'];

    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'success' => Ubmod_Model_User::addTag($tag, $userIds),
    ));
  }
}
