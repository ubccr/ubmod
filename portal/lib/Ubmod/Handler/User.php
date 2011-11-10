<?php
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
   * List help for the "list" action.
   *
   * @return void
   */
  public function listHelp()
  {
    $desc = 'List user activity.  Results will be an array where individual'
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
   * List user activity.
   *
   * @param array arguments
   * @param array postData
   * @return Ubmod_RestRespons
   */
  public function listAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total' => Ubmod_Model_User::getActivityCount($postData),
      'users' => Ubmod_Model_User::getActivities($postData),
    ));
  }
}
