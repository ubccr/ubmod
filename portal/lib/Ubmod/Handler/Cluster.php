<?php
/**
 * Cluster REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Cluster REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Cluster
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_Cluster
   */
  public static function factory()
  {
    return new Ubmod_Handler_Cluster();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    $clusters = Ubmod_Model_Cluster::getAll();
    return RestResponse::factory(TRUE, NULL, array(
      'data'  => $clusters,
      'total' => count($clusters),
    ));
  }
}
