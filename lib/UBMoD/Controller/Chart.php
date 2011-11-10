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
  }

  /**
   * Execute the wait time action.
   *
   * @return void
   */
  public function executeWaitTime()
  {
    UBMoD_Model_Chart::renderWaitTime($this->getGetData());
  }

  /**
   * Execute the user pie chart action.
   *
   * @return void
   */
  public function executeUserPie()
  {
    UBMoD_Model_Chart::renderUserPie($this->getGetData());
  }

  /**
   * Execute the user bar chart action.
   *
   * @return void
   */
  public function executeUserBar()
  {
    UBMoD_Model_Chart::renderUserBar($this->getGetData());
  }

  /**
   * Execute the group pie chart action.
   *
   * @return void
   */
  public function executeGroupPie()
  {
    UBMoD_Model_Chart::renderGroupPie($this->getGetData());
  }

  /**
   * Execute the user group bar action.
   *
   * @return void
   */
  public function executeGroupBar()
  {
    UBMoD_Model_Chart::renderGroupBar($this->getGetData());
  }
}
