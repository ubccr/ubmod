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
class QueueHandler
{

  public static function factory()
  {
    return new QueueHandler();
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
