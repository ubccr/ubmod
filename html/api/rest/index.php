<?php
/**
 * Rest API endpoint.
 *
 * @author Steve Gallo
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

require_once dirname(__FILE__) . '/../../../config/bootstrap.php';

$requestUrl  = $_SERVER['REQUEST_URI'];
$pathInfo    = $_SERVER['PATH_INFO'];
$queryString = $_SERVER['QUERY_STRING'];
$getData     = $_GET;
$postData    = $_POST;

try
{
  $request = RestRequest::factory($requestUrl, $pathInfo, $queryString,
    $getData, $postData);
  $request->process();
  $response = $request->formatResponse();
  $headerPairList = $request->responseHeader();
  foreach ( $headerPairList as $headerInfo ) {
    list($name, $value) = $headerInfo;
    header($name . ": " . $value);
  }
  print $response;
} catch ( Exception $e ) {
  $response = RestResponse::factory(FALSE, $e->getMessage());
  print "<pre>" . print_r($response, 1) . "</pre>";
}

