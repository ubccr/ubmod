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
 * Job REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Job REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Job extends Ubmod_BaseHandler
{

  /**
   * Help for the "activity" action.
   *
   * @return Ubmod_RestResponse
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
    return Ubmod_RestResponse::factory(array(
      'message' => $desc,
      'results' => $options,
    ));
  }

  /**
   * Returns user activity.
   *
   * @param array $arguments Request GET data.
   * @param array $postData Request POST data.
   *
   * @return Ubmod_RestResponse
   */
  public function activityAction(array $arguments, array $postData = null)
  {
    $params = Ubmod_Model_QueryParams::factory($arguments);

    // Limit queries according to authorization ACL.
    $request = $this->getRequest();
    switch ($params->getModel()) {
    case 'user':
      if (!$request->isAllowed('user', 'query-all')) {
        $user = $request->getUser();
        $userId = Ubmod_Model_User::getUserId($user);
        $params->setUserId($userId);
      }
      break;
    case 'group':
      if (!$request->isAllowed('group', 'query-all')) {
        $group = $request->getGroup();
        $groupId = Ubmod_Model_Group::getGroupId($group);
        $params->setGroupId($groupId);
      }
      break;
    }

    return Ubmod_RestResponse::factory(array(
      'results'  => Ubmod_Model_Job::getActivityList($params),
      'total'    => Ubmod_Model_Job::getActivityCount($params),
      'columns'  => Ubmod_Model_Job::getColumns($params),
      'filename' => Ubmod_Model_Job::getFilename($params),
    ));
  }
}

