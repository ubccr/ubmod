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
 * Front controller.
 *
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
   * The current request.
   *
   * @var Ubmod_Request
   */
  private $_request = null;

  /**
   * Private constructor.
   *
   * @return void
   */
  private function __construct()
  {
    $requestUrl  = $_SERVER['REQUEST_URI'];
    $pathInfo    = $_GET['path_info'];
    $queryString = $_SERVER['QUERY_STRING'];
    $getData     = $_GET;
    $postData    = $_POST;

    $this->_request = Ubmod_Request::factory(
      $requestUrl,
      $pathInfo,
      $queryString,
      $getData,
      $postData
    );
  }

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
   * Process the current request.
   *
   * @return void
   */
  public function process()
  {
    $request    = $this->_request;
    $controller = $this->getController();
    $action     = $this->getAction();
    $view       = $this->getView();

    try {
      $controller->$action();

      $content = $this->renderView($view, $controller);

      if ($request->isXmlHttpRequest()) {
        echo $content;
      } else {
        $this->renderLayout($content, $controller, $action);
      }
    } catch (Exception $e) {
      error_log($e->getMessage());
    }
  }

  /**
   * Render a view template.
   *
   * @param string $view The path to the view to render
   * @param Ubmod_BaseController $controller The controller for the view
   *
   * @return string
   */
  private function renderView($view, $controller)
  {
    global $BASE_URL;
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
   * @param string $content The page content
   *
   * @return void
   */
  private function renderLayout($content)
  {
    global $BASE_URL;

    $request    = $this->_request;
    $controller = $request->getEntity();
    $action     = $request->getAction();

    require TEMPLATE_DIR . '/layouts/default.php';
  }

  /**
   * Create a controller for the current request.
   *
   * @return Ubmod_Controller
   */
  private function getController()
  {
    $request = $this->_request;
    $segment = $request->getEntity();
    $class = 'Ubmod_Controller_' . $this->convertPathSegment($segment);
    try {
      return $class::factory($request);
    } catch (Exception $e) {
      header('HTTP/1.0 404 Not Found');
      exit(0);
    }
  }

  /**
   * Returns the name of action for the current request.
   *
   * @return string
   */
  private function getAction()
  {
    $segment = $this->_request->getAction();

    return 'execute' . $this->convertPathSegment($segment);
  }

  /**
   * Convert a path segment string to the corresponding camel case
   * string.
   *
   * @param string $segment The path segment.
   *
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
   * Returns the view file for the current request.
   *
   * @return string
   */
  private function getView()
  {
    $request    = $this->_request;
    $controller = $request->getEntity();
    $action     = $request->getAction();

    return TEMPLATE_DIR . '/views/' . $controller . '/' . $action . '.php';
  }
}

