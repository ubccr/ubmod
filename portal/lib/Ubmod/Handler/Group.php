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

  public static function factory()
  {
    return new Ubmod_Handler_Group();
  }

  public function listHelp()
  {
    $desc = '';
    return Ubmod_RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'total'  => Ubmod_Model_Group::getActivityCount($postData),
      'groups' => Ubmod_Model_Group::getActivities($postData),
    ));
  }
}
