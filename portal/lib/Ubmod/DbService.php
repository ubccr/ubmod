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
 * Database service.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Database service.
 *
 * @package Ubmod
 */
class Ubmod_DbService
{
  /**
   * Singleton instance.
   *
   * @var Ubmod_DbService
   */
  private static $instance;

  /**
   * Database handle.
   *
   * @var PDO
   */
  private $_dbh;

  /**
   * Private constructor
   *
   * @param string host The database host
   * @param string dbname The database name
   * @param string username The database username
   * @param string password The database password
   * @return void
   */
  private function __construct($host, $dbname, $username, $password)
  {
    $dsn = "mysql:host=$host;dbname=$dbname";
    $this->_dbh = new PDO($dsn, $username, $password);
  }

  /**
   * Factory method.
   *
   * @return Ubmod_DbService
   */
  public static function factory()
  {
    if (self::$instance === NULL) {
      $section = 'database';
      $options = $GLOBALS['options'];

      if ( ! isset($options->$section) ) {
        $msg = "Invalid configuration section '$section'";
        throw new Exception($msg);
      }

      self::$instance = new Ubmod_DbService($options->$section->host,
                                      $options->$section->dbname,
                                      $options->$section->user,
                                      $options->$section->password);
    }

    return self::$instance;
  }

  public function getHandle()
  {
    return $this->_dbh;
  }

  public static function dbh()
  {
    $service = static::factory();
    return $service->getHandle();
  }
}
