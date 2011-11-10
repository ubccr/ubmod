<?php
/**
 * Queue REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Queue REST Handler.
 *
 * @package UBMoD
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
      'total'  => UBMoD_Model_Queue::getActivityCount($postData),
      'queues' => UBMoD_Model_Queue::getActivities($postData),
    ));
  }
}
