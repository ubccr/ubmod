<?php
/**
 * Group controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Group controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Group extends Ubmod_BaseController
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
    $this->group = Ubmod_Model_Group::getActivityById($this->getPostData());
  }
}
