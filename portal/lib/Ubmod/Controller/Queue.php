<?php
/**
 * Queue controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Queue controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Queue extends Ubmod_Controller_Base
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
    $this->queue = Ubmod_Model_Queue::getActivityById($this->getPostData());
  }
}
