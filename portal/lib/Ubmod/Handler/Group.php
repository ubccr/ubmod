<?php
/**
 * Group REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Group REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Group
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_Group
   */
  public static function factory()
  {
    return new Ubmod_Handler_Group();
  }

  /**
   * Return help for the "list" action.
   *
   * @return void
   */
  public function listHelp()
  {
    $desc = 'List group activity.  Results will be an array where individual'
      . ' records will consist of (group_id, group_name, pi_name, jobs, cput,'
      . ' wallt, avg_wait, avg_cpus, avg_mem).';
    $options = array(
      'interval_id' => 'Return group activity in this interval. (required)',
      'cluster_id'  => 'Return group activity in this cluster. (required)',
      'filter'      => 'Filter criteria.  Substring match against group_name.',
      'sort'        => 'Sort field.  Valid options: group_name, jobs,'
                     . ' avg_cpus, avg_wait, wallt, avg_mem',
      'dir'         => 'Sort direction.  Valid options: ASC, DESC',
      'start'       => 'Limit offset. (requires limit)',
      'limit'       => 'Maximum number of entities to return. (requires start)',
    );
    return Ubmod_RestResponse::factory(TRUE, $desc, $options);
  }

  /**
   * List group activity.
   *
   * @param array arguments
   * @param array postData
   * @return Ubmod_RestResponse
   */
  public function listAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total'  => Ubmod_Model_Group::getActivityCount($postData),
      'groups' => Ubmod_Model_Group::getActivities($postData),
    ));
  }
}
