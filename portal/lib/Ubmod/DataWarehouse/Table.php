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
 * Abstract database table representation.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Abstract database table representation.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Table
{

  /**
   * The table's name.
   *
   * @var string
   */
  protected $_name;

  /**
   * Columns belonging to the table.
   *
   * @var array
   */
  protected $_columns = array();

  /**
   * Constructor.
   *
   * @param array $def The table definition.
   *
   * @return Ubmod_DataWarehouse_Table
   */
  public function __construct(array $def)
  {
    $this->_name    = $def['name'];
    $this->_columns = $def['columns'];
  }

  /**
   * Returns the table's name.
   *
   * @return string
   */
  public function getName()
  {
    return $this->_name;
  }

  /**
   * Returns the table's columns.
   *
   * @return array
   */
  public function getColumns()
  {
    return $this->_columns;
  }

  /**
   * Check if the table has certain columns.
   *
   * @param columns array The columns to check for.
   *
   * @return bool
   */
  public function hasColumns($columns)
  {
    foreach ($columns as $column) {
      if (!in_array($column, $this->_columns)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Return the intersection of the table's columns with those provided.
   *
   * @param array $columns The columns to use in the intersection.
   *
   * @return array
   */
  public function intersectColumns($columns)
  {
    $intersection = array();

    foreach ($columns as $column) {
      if (in_array($column, $this->_columns)) {
        $intersection[] = $column;
      }
    }

    return $intersection;
  }
}
