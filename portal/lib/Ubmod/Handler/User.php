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

  public static function factory()
  {
    return new Ubmod_Handler_User();
  }

  public function listHelp()
  {
    $desc = '';
    return Ubmod_RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total' => Ubmod_Model_User::getActivityCount($postData),
      'users' => Ubmod_Model_User::getActivities($postData),
    ));
  }
}
