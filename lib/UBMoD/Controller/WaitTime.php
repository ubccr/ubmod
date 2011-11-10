<?php
/**
 * Wait time controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Wait time controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_WaitTime extends UBMoD_Controller_Base
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
   * Execute the chart action.
   *
   * @return void
   */
  public function executeChart()
  {
    $post = $this->getPostData();
    $this->chart
      = '/chart/wait-time?interval_id=' . $post['interval_id']
      . '&amp;cluster_id=' . $post['cluster_id'] . '&amp;t=' . time();
  }
}
