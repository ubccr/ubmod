<?php
/**
 * Queue controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Queue controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Queue extends UBMoD_Controller_Base
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
    $this->queue = UBMoD_Model_Queue::getActivityById($this->getPostData());
  }
}
