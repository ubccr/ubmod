<?php
/**
 * Chart controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Chart controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Chart extends UBMoD_Controller_Base
{

  /**
   * Execute the cpu consumption action.
   *
   * @return void
   */
  public function executeCpuConsumption()
  {
    UBMoD_Model_Chart::renderCpuConsumption($this->getGetData());
    exit(0);
  }

  /**
   * Execute the wait time action.
   *
   * @return void
   */
  public function executeWaitTime()
  {
    UBMoD_Model_Chart::renderWaitTime($this->getGetData());
    exit(0);
  }
}
