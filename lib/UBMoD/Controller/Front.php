<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Front controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Front
{

  /**
   * Private constructor.
   *
   * @return void
   */
  private function __construct() {}

  /**
   * Factory method.
   *
   * @return void
   */
  public static function factory()
  {
    return new UBMoD_Controller_Front();
  }

  /**
   * Process request.
   *
   * @return void
   */
  public function process()
  {
    $requestUrl = $_SERVER['REQUEST_URI'];
    $pathInfo = $_SERVER['PATH_INFO'];
    $queryString = $_SERVER['QUERY_STRING'];
    $getData = $_GET;
    $postData = $_POST;

    /*
    echo '<pre>';
    echo $requestUrl . "\n";
    echo $pathInfo . "\n";
    echo $queryString . "\n";
    echo '</pre>';
     */

    $c = 'UBMoD_Controller_Default';
    $action = 'executeIndex';

    try {
      $c::factory()->$action();
      require TEMPLATE_DIR . '/layouts/default.php';
    } catch (Exception $e) {
      echo '<pre>' . $e->getMessage() . '</pre>';
    }
  }
}
