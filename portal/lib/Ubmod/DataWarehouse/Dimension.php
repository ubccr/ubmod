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
 * Data Warehouse Dimension.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Dimension table representation.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Dimension extends Ubmod_DataWarehouse_Table
{

  /**
   * The roll-up dimensions of this dimension.
   *
   * @var array
   */
  private $_rollUps = array();

  /**
   * Constructor
   *
   * @param array $config The configuration arguments.
   *
   * @return Ubmod_DataWarehouse_Dimension
   */
  public function __construct($config)
  {
    $columns = array();

    // Primary Key
    $columns[] = $config['name'] . '_id';

    foreach ($config['attributes'] as $attr) {
      $columns[] = $attr;
    }

    parent::__construct(array(
      'name'    => $config['name'],
      'columns' => $columns,
    ));
  }

  /**
   * Returns the primary key.
   *
   * @return string
   */
  public function getPrimaryKey()
  {
    return $this->_columns[0];
  }

  /**
   * Add a roll-up to this dimension.
   *
   * @param Ubmod_DataWarehouse_Dimension $dimension The roll-up.
   *
   * @return void
   */
  public function addRollUp($dimension)
  {
    $this->_rollUps[] = $dimension;
  }

  /**
   * Find the smallest roll-up of this dimension with the given columns.
   *
   * Returns null if a suitable roll-up cannot be found.
   *
   * @param array $columns The columns that must be in the dimension.
   *
   * @return Ubmod_DataWarehouse_Dimension
   */
  public function findRollUpWith($columns)
  {

    // XXX this is not optimal when dimensions the roll-ups don't have
    // mutually exclusive attributes

    foreach ($this->_rollUps as $rollUp) {
      if ($rollUp->hasColumns($columns)) {

        // XXX this should probably be recursive
        return $rollUp;
      }
    }

    return null;
  }
}
