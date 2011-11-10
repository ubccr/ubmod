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

  /**
   * Return help for the "list" action.
   *
   * @return Ubmod_RestResponse
   */
  public function listHelp()
  {
    $desc = 'List all time intervals.  Results will be an array where'
      . ' individual records consist of (interval_id, time_interval, start,'
      . ' end).';
    return Ubmod_RestResponse::factory(TRUE, $desc);
  }

  /**
   * List time intervals.
   *
   * @param array arguments
   * @param array postData
   * @return Ubmod_RestResponse
   */
  public function listAction(array $arguments, array $postData = NULL)
  {
    $intervals = Ubmod_Model_Interval::getAll();
    return Ubmod_RestResponse::factory(TRUE, NULL, array(
      'data'  => $intervals,
      'total' => count($intervals),
    ));
  }
}
