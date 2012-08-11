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
 * Rest API endpoint.
 *
 * @author Steve Gallo
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

require_once dirname(__FILE__) . '/../../../config/bootstrap.php';

$requestUrl  = $_SERVER['REQUEST_URI'];
$pathInfo    = $_GET['path_info'];
$queryString = $_SERVER['QUERY_STRING'];
$getData     = $_GET;
$postData    = $_POST;

try {
  $request = Ubmod_RestRequest::factory(
    $requestUrl, $pathInfo, $queryString, $getData, $postData
  );
  $request->process();
  $response = $request->formatResponse();
  foreach ($request->responseHeader() as $headerInfo) {
    if (is_array($headerInfo)) {
      list($name, $value) = $headerInfo;
      header($name . ': ' . $value);
    } else {
      header($headerInfo);
    }
  }
  print $response;
} catch (Exception $e) {
  print '<pre>' . $e->getMessage() . '</pre>';
}

