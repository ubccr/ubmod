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
 * Menu singleton.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Menu singleton.
 *
 * @package Ubmod
 */
class Ubmod_Menu implements Iterator
{

  /**
   * Singleton object.
   *
   * @var Ubmod_Menu
   */
  private static $_instance = null;

  /**
   * Menu items.
   *
   * @var array
   */
  private $_items = null;

  /**
   * Iterator position in menu items.
   *
   * @var int
   */
  private $_iteratorPosition = 0;

  /**
   * Private constructor for factory pattern.
   *
   * @param array $items Array of menu items.
   */
  private function __construct(array $items)
  {
    $this->_items = $items;
  }

  /**
   * Factory method.
   */
  public static function factory()
  {
    if (self::$_instance === null) {
      $menuFile = MENU_CONFIG_FILE;

      $menuJson = file_get_contents($menuFile);
      if ($menuJson === FALSE) {
        $msg = "Failed to read data from $menuFile";
        throw new Exception($msg);
      }

      $menuData = json_decode($menuJson, true);
      if ($menuData === null) {
        $msg = "Failed to decode data from $menuFile";
        throw new Exception($msg);
      }

      self::$_instance = new Ubmod_Menu($menuData);
    }

    return self::$_instance;
  }

  /**
   * Implements Iterator interface.
   */
  public function current()
  {
    return $this->_items[$this->_iteratorPosition];
  }

  /**
   * Implements Iterator interface.
   */
  public function key()
  {
    return $this->_iteratorPosition;
  }

  /**
   * Implements Iterator interface.
   */
  public function next()
  {
    $this->_iteratorPosition++;
  }

  /**
   * Implements Iterator interface.
   */
  public function rewind()
  {
    $this->_iteratorPosition = 0;
  }

  /**
   * Implements Iterator interface.
   */
  public function valid()
  {
    return isset($this->_items[$this->_iteratorPosition]);
  }
}

