<?php
/**
 * Cluster REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Cluster REST Handler.
 *
 * @package UBMoD
 */
class ClusterHandler
{

  /**
   * Factory method.
   *
   * @return ClusterHandler
   */
  public static function factory()
  {
    return new ClusterHandler();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    $clusters = UBMoD_Model_Cluster::getAll();
    return RestResponse::factory(TRUE, NULL, array(
      'data'  => $clusters,
      'total' => count($clusters),
    ));
  }
}
