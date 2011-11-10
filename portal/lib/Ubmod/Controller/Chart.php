<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 */

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
   * Execute the wall time period action.
   *
   * @return void
   */
  public function executeWallTimePeriod()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderWallTimePeriod($params);
  }

  /**
   * Execute the wall time monthly action.
   *
   * @return void
   */
  public function executeWallTimeMonthly()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderWallTimeMonthly($params);
  }

  /**
   * Execute the wait time period action.
   *
   * @return void
   */
  public function executeWaitTimePeriod()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderWaitTimePeriod($params);
  }

  /**
   * Execute the wait time monthly action.
   *
   * @return void
   */
  public function executeWaitTimeMonthly()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderWaitTimeMonthly($params);
  }

  /**
   * Execute the user pie chart action.
   *
   * @return void
   */
  public function executeUserPie()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderUserPie($params);
  }

  /**
   * Execute the user bar chart action.
   *
   * @return void
   */
  public function executeUserBar()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderUserBar($params);
  }

  /**
   * Execute the user stacked area chart action.
   *
   * @return void
   */
  public function executeUserArea()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderUserStackedArea($params);
  }

  /**
   * Execute the group pie chart action.
   *
   * @return void
   */
  public function executeGroupPie()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderGroupPie($params);
  }

  /**
   * Execute the user group bar action.
   *
   * @return void
   */
  public function executeGroupBar()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderGroupBar($params);
  }

  /**
   * Execute the group stacked area chart action.
   *
   * @return void
   */
  public function executeGroupArea()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderGroupStackedArea($params);
  }

  /**
   * Execute the tag pie chart action.
   *
   * @return void
   */
  public function executeTagPie()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderTagPie($params);
  }

  /**
   * Execute the tag bar chart action.
   *
   * @return void
   */
  public function executeTagBar()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderTagBar($params);
  }

  /**
   * Execute the tag stacked area chart action.
   *
   * @return void
   */
  public function executeTagArea()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getGetData());
    Ubmod_Model_Chart::renderTagStackedArea($params);
  }
}
