<?php
/**
 * Queue REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Queue REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Queue
{

  public static function factory()
  {
    return new Ubmod_Handler_Queue();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    return RestResponse::factory(TRUE, NULL, array(
      'total'  => Ubmod_Model_Queue::getActivityCount($postData),
      'queues' => Ubmod_Model_Queue::getActivities($postData),
    ));
  }
}
