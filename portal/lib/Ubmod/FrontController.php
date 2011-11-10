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
    try {
      return $class::factory($request);
    } catch (Exception $e) {
      header('HTTP/1.0 404 Not Found');
      exit(0);
    }
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
