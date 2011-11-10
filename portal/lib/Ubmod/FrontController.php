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
class Ubmod_FrontController
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
    return new Ubmod_FrontController();
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
        $controller = $request->getControllerSegment();
        $action     = $request->getActionSegment();
        $this->renderLayout($content, $controller, $action);
      }
    } catch (Exception $e) {
      error_log($e->getMessage());
    }
  }

  /**
   * Render a view template.
   *
   * @param view string The path to the view to render
   * @param controller Ubmod_BaseController The controller for the view
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
   * Render the layout template.
   *
   * @param content string The page content
   * @param controller string The name of the controller segment
   * @param action string The name of the action
   * @return void
   */
  private function renderLayout($content, $controller, $action)
  {
    require TEMPLATE_DIR . '/layouts/default.php';
  }

  /**
   * Create a controller for a given request.
   *
   * @return Ubmod_Controller
   */
  private function getController($request)
  {
    $segment = $request->getControllerSegment();
    $class = 'Ubmod_Controller_' . $this->convertPathSegment($segment);
    return $class::factory($request);
  }

  /**
   * Returns the name of action for a given request.
   *
   * @return string
   */
  private function getAction($request)
  {
    $segment = $request->getActionSegment();
    return 'execute' . $this->convertPathSegment($segment);
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
    $controller = $request->getControllerSegment();
    $action     = $request->getActionSegment();
    return TEMPLATE_DIR . '/views/' . $controller . '/' . $action . '.php';
  }
}
152:	hit EOF seeking end of quote/pattern starting at line 1 ending in ?
