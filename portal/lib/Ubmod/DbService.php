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
 * Database service.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
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
  private static $_instance = null;

  /**
   * Database handle.
   *
   * @var PDO
   */
  private $_dbh = null;

  /**
   * Private constructor
   *
   * @param array options
   *   string host     The database host
   *   string dbname   The database name
   *   string user     The database username
   *   string password The database password
   *   int    port     (optional) The database port number
   *
   * @return void
   */
  private function __construct($options)
  {
    $requiredKeys = array('host', 'dbname', 'user', 'password');
    foreach ($requiredKeys as $key) {
      if (!isset($options[$key])) {
        throw new Exception("Missing database config option: '$key'");
      }
    }

    $dsn = "mysql:host={$options['host']};dbname={$options['dbname']}";

    if (isset($options['port'])) {
      $dsn .= ';port=' . $options['port'];
    }

    $this->_dbh = new PDO($dsn, $options['user'], $options['password']);
  }

  /**
   * Factory method.
   *
   * @return Ubmod_DbService
   */
  public static function factory()
  {
    if (self::$_instance === null) {
      $section = 'database';
      $options = $GLOBALS['options'];

      if (!isset($options->$section)) {
        $msg = "Invalid configuration section '$section'";
        throw new Exception($msg);
      }

      $dbOptions = $options->$section;

      $args = array(
        'host'     => $dbOptions->host,
        'dbname'   => $dbOptions->dbname,
        'user'     => $dbOptions->user,
        'password' => $dbOptions->password
      );

      if (isset($dbOptions->port)) {
        $args['port'] = $dbOptions->port;
      }

      self::$_instance = new Ubmod_DbService($args);
    }

    return self::$_instance;
  }

  /**
   * Get a database handle.
   *
   * @return PDO
   */
  public function getHandle()
  {
    return $this->_dbh;
  }

  /**
   * Get a database handle.
   *
   * @return PDO
   */
  public static function dbh()
  {
    $service = static::factory();
    return $service->getHandle();
  }
}

