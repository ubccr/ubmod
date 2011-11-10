<?php
/**
 * Group controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Group controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Group extends UBMoD_Controller_Base
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
    $this->group = UBMoD_Model_Group::getActivityById($this->getPostData());
  }
}
