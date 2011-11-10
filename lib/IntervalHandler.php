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
class IntervalHandler
{

  /**
   * Factory method.
   *
   * @return IntervalHandler
   */
  public static function factory()
  {
    return new IntervalHandler();
  }

  public function listHelp()
  {
    $desc = '';
    return RestResponse::factory(TRUE, $desc);
  }

  public function listAction(array $arguments, array $postData = NULL)
  {
    $intervals = Ubmod_Model_Interval::getAll();
    return RestResponse::factory(TRUE, NULL, array(
      'data'  => $intervals,
      'total' => count($intervals),
    ));
  }
}
