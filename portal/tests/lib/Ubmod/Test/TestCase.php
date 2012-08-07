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
 * Test case base class
 *
 * Provides helper functions for accessing the database and REST handlers.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod_Test
 */

/**
 */
class Ubmod_Test_TestCase extends PHPUnit_Extensions_Database_TestCase
{

  /**
   * database connection
   *
   * @var PDO
   */
  private static $_pdo = NULL;

  /**
   * database connection
   *
   * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
   */
  private $_connection = NULL;

  /**
   * Overrides PHPUnit setUp method
   *
   */
  public function setUp()
  {

  }

  /**
   * Return a database connection
   *
   * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
   */
  protected function getConnection()
  {
    if ($this->_connection === null) {
      if (self::$_pdo === null) {
        $options = $GLOBALS['options']->database;

        $host     = $options->host;
        $dbname   = $options->dbname;
        $user     = $options->user;
        $password = $options->password;

        $dsn = "mysql:host=$host;dbname=$dbname";

        if (isset($options->port)) {
          $dsn .= ';port=' . $options->port;
        }

        self::$_pdo = new PDO($dsn, $user, $password);
      }

      $this->_connection
        = $this->createDefaultDBConnection(self::$_pdo, $dbname);
    }

    return $this->_connection;
  }

  /**
   * Returns the test dataset.
   *
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  protected function getDataSet()
  {
    return new PHPUnit_Extensions_Database_DataSet_YamlDataSet(
      TEST_DIR . '/data/initial_state.yml'
    );
  }

  /**
   * Query the database
   *
   * @param string $statement SQL statement.
   *
   * @return PDOStatement
   */
  protected function query($statement)
  {
    return $this->getConnection()->query($statement);
  }

  /**
   * Prepare a query for execution
   *
   * @param string $statement SQL statement.
   *
   * @return PDOStatement
   */
  protected function prepare($statement)
  {
    return $this->getConnection()->prepare($statement);
  }
}

