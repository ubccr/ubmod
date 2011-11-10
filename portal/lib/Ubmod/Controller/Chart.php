<?php
/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
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
   * Execute the user stacked area chart action.
   *
   * @return void
   */
  public function executeUserArea()
  {
    Ubmod_Model_Chart::renderUserStackedArea($this->getGetData());
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

  /**
   * Execute the group stacked area chart action.
   *
   * @return void
   */
  public function executeGroupArea()
  {
    Ubmod_Model_Chart::renderGroupStackedArea($this->getGetData());
  }
}
