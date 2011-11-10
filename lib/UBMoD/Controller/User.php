<?php
/**
 * User controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * User controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_User extends UBMoD_Controller_Base
{

  /**
   * Execute the index action.
   *
   * @return void
   */
  public function executeIndex()
  {

  }

  /**
   * Execute the details action.
   *
   * @return void
   */
  public function executeDetails()
  {
    $this->user = UBMoD_Model_User::getActivityById($this->getPostData());
  }
}
