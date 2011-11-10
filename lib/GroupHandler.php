<?php
/**
 * Group REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Group REST Handler.
 *
 * @package UBMoD
 */
class GroupHandler
{

  public static function factory()
  {
    return new GroupHandler();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    return RestResponse::factory(TRUE, NULL, array(
      'total'  => UBMoD_Model_Group::getActivityCount($postData),
      'groups' => UBMoD_Model_Group::getActivities($postData),
    ));
  }
}
