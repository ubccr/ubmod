<?php
/**
 * Chart controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Chart controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Chart extends Ubmod_BaseController
{

  /**
   * Execute the cpu consumption action.
   *
   * @return void
   */
  public function executeCpuConsumption()
  {
    Ubmod_Model_Chart::renderCpuConsumption($this->getGetData());
  }

  /**
   * Execute the wait time action.
   *
   * @return void
   */
  public function executeWaitTime()
  {
    Ubmod_Model_Chart::renderWaitTime($this->getGetData());
  }

  /**
   * Execute the user pie chart action.
   *
   * @return void
   */
  public function executeUserPie()
  {
    Ubmod_Model_Chart::renderUserPie($this->getGetData());
  }

  /**
   * Execute the user bar chart action.
   *
   * @return void
   */
  public function executeUserBar()
  {
    Ubmod_Model_Chart::renderUserBar($this->getGetData());
  }

  /**
   * Execute the group pie chart action.
   *
   * @return void
   */
  public function executeGroupPie()
  {
    Ubmod_Model_Chart::renderGroupPie($this->getGetData());
  }

  /**
   * Execute the user group bar action.
   *
   * @return void
   */
  public function executeGroupBar()
  {
    Ubmod_Model_Chart::renderGroupBar($this->getGetData());
  }
}
