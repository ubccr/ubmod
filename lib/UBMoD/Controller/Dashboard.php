<?php
/**
 * Dashboard controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Dashboard controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Dashboard extends UBMoD_Controller_Base
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
   * Execute the utilization action.
   *
   * @return void
   */
  public function executeUtilization()
  {
    $post = $this->getPostData();

    $this->interval = UBMoD_Model_Interval::getById($post['interval_id']);
    $this->cluster = UBMoD_Model_Cluster::getActivity($post);

    $this->userPieChart
      = '/chart/user-pie?interval_id=' . $post['interval_id']
      . '&amp;cluster_id=' . $post['cluster_id'] . '&amp;t=' . time();

    $this->userBarChart
      = '/chart/user-bar?interval_id=' . $post['interval_id']
      . '&amp;cluster_id=' . $post['cluster_id'] . '&amp;t=' . time();

    $this->groupPieChart
      = '/chart/group-pie?interval_id=' . $post['interval_id']
      . '&amp;cluster_id=' . $post['cluster_id'] . '&amp;t=' . time();

    $this->groupBarChart
      = '/chart/group-bar?interval_id=' . $post['interval_id']
      . '&amp;cluster_id=' . $post['cluster_id'] . '&amp;t=' . time();
  }
}
