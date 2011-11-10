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
    $requestUrl  = $_SERVER['REQUEST_URI'];
    $pathInfo    = $_SERVER['PATH_INFO'];
    $queryString = $_SERVER['QUERY_STRING'];
    $getData     = $_GET;
    $postData    = $_POST;

    $request = UBMoD_Request::factory($requestUrl, $pathInfo, $queryString,
      $getData, $postData);

    $controller = $this->getController($request);
    $action     = $this->getAction($request);
    $view       = $this->getView($request);

    try {
      $controller->$action();

      ob_start();
      require $view;
      $content = ob_get_clean();

      require TEMPLATE_DIR . '/layouts/default.php';
    } catch (Exception $e) {
      echo '<pre>' . $e->getMessage() . '</pre>';
    }
  }

  /**
   * Create a controller for a given request.
   *
   * @return UBMoD_Controller
   */
  private function getController($request)
  {
    $segments = $request->getPathSegments();
    if (count($segments) > 0) {
      $class = 'UBMoD_Controller_' . $this->convertPathSegment($segments[0]);
    } else {
      $class = 'UBMoD_Controller_Dashboard';
    }
    return $class::factory();
  }

  /**
   * Returns the name of action for a given request.
   *
   * @return string
   */
  private function getAction($request)
  {
    $segments = $request->getPathSegments();
    if (count($segments) > 1) {
      $action = 'execute' . $this->convertPathSegment($segments[1]);
    } else {
      $action = 'executeIndex';
    }
    return $action;
  }

  /**
   * Convert a path segment string to the corresponding camel case string.
   *
   * @param string The path segment
   * @return string
   */
  private function convertPathSegment($segment)
  {
    $words = array_map(
      function ($word) {
        return ucfirst(strtolower($word));
      },
        preg_split('/\W+/', $segment)
      );
    return implode('', $words);
  }

  /**
   * Returns the view file for the given request.
   *
   * @return string
   */
  private function getView($request)
  {
    $segments   = $request->getPathSegments();
    $controller = count($segments) > 0 ? $segments[0] : 'dashboard';
    $action     = count($segments) > 1 ? $segments[1] : 'index';
    return TEMPLATE_DIR . '/views/' . $controller . '/' . $action . '.php';
  }
}
