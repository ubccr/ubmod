<?php
/**
 * Time interval REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Time interval REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Interval
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_Interval
   */
  public static function factory()
  {
    return new Ubmod_Handler_Interval();
  }

  public function listHelp()
  {
    $desc = '';
    return Ubmod_RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    $intervals = Ubmod_Model_Interval::getAll();
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'data'  => $intervals,
      'total' => count($intervals),
    ));
  }
}
