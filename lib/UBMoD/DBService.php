<?php
/**
 * Database service.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Database service.
 *
 * @package UBMoD
 */
class UBMoD_DBService
{
  /**
   * Singleton instance.
   *
   * @var UBMoD_DBService
   */
  private static $instance;

  /**
   * Database handle.
   *
   * @var PDO
   */
  private $_dbh;

  /**
   * undocumented function
   *
   * @return void
   */
  private function __construct($dsn, $username, $password)
  {
    $this->_dbh = new PDO($dsn, $username, $password);
  }

  /**
   * Factory method.
   *
   * @return UBMoD_DBService
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

      self::$instance = new UBMoD_DBService($options->$section->dsn,
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
