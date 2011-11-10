<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Front controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Front
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
    return new Ubmod_Controller_Front();
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

    $request = Ubmod_Request::factory($requestUrl, $pathInfo, $queryString,
      $getData, $postData);

    $controller = $this->getController($request);
    $action     = $this->getAction($request);
    $view       = $this->getView($request);

    try {
      $controller->$action();

      $content = $this->renderView($view, $controller);

      if ($request->isXmlHttpRequest()) {
        echo $content;
      } else {
        $segments = $request->getPathSegments();
        $page     = $segments[0];
        require TEMPLATE_DIR . '/layouts/default.php';
      }
    } catch (Exception $e) {
      error_log($e->getMessage());
    }
  }

  /**
   * Render a view template.
   *
   * @param view str The path to the view to render
   * @param controller Ubmod_Controller_Base The controller for the view
   * @return string
   */
  private function renderView($view, $controller)
  {
    foreach ($controller->getData() as $key => $value) {
      $$key = $value;
    }
    ob_start();
    require $view;
    return ob_get_clean();
  }

  /**
   * Create a controller for a given request.
   *
   * @return Ubmod_Controller
   */
  private function getController($request)
  {
    $segments = $request->getPathSegments();
    if (count($segments) > 0) {
      $class = 'Ubmod_Controller_' . $this->convertPathSegment($segments[0]);
    } else {
      $class = 'Ubmod_Controller_Dashboard';
    }
    return $class::factory($request);
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
