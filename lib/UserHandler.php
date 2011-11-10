<?php
/**
 * User REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * User REST Handler.
 *
 * @package UBMoD
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
    error_log(print_r($postData));
    return RestResponse::factory(TRUE, NULL, array(
      'total' => 100,
      'users' => UBMoD_Model_User::getAll(),
    ));
  }
}
