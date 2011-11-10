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
class UserHandler
{

  public static function factory()
  {
    return new UserHandler();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    return RestResponse::factory(TRUE, NULL, array(
      'total' => Ubmod_Model_User::getActivityCount($postData),
      'users' => Ubmod_Model_User::getActivities($postData),
    ));
  }
}
