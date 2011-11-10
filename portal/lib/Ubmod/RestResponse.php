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

// ================================================================================
// @author Steve Gallo
// @date 2010-June-15
// @version 1.0
//
// REST response message.  This class is responsible for constructing and
// formating a response to the REST API.  Valid response formats are indicated
// by the definition of a method with the response format (lowercase) followed
// by "Format" (e.g., jsonFormat, xmlFormat, etc.).  Each format method should
// have a corresponding method for returning (but not actually sending to the
// browser) a list of headers which should include the MIME header string (e.g.,
// jsonHeader).  The handlers can provide an optional list of headers, which is
// especially useful for the raw handler and file downloads.  See
// http://www.iana.org/assignments/media-types/application/
// ================================================================================

class Ubmod_RestResponse
{
  //
  private $_success = TRUE;
  private $_message = NULL;
  private $_numResults = 0;
  private $_results = array();
  private $_headers = NULL;

  // --------------------------------------------------------------------------------
  // Factory pattern.
  //
  // @params $success 1 for success, 0 for an error
  // @params $msg Optional message
  // @params $results An array of results.  Single responses should still be
  //   placed into an array to keep things normalized
  // @params $headers An optional array of headers that can be used by some of
  //   the formatting methods (e.g., raw)
  // --------------------------------------------------------------------------------

  public static function factory($success,
                                 $msg,
                                 array $results = array(),
                                 array $headers = array())
  {
    return new Ubmod_RestResponse($success, $msg, $results, $headers);
  }  // factory

  // --------------------------------------------------------------------------------
  // @see factory()
  // --------------------------------------------------------------------------------

  private function __construct($success,
                               $msg,
                               array $results = array(),
                               array $headers = array())
  {
    $this->_success = $success;
    $this->_message = $msg;
    $this->_numResults = count($results);
    $this->_results = $results;
    $this->_headers = $headers;
  }  // __construct()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "json" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

  public function jsonHelp()
  {
    return "Display the entire response object encoded as a JSON object.  The " .
      "response will be returned as an object where the results is an array arrays.";
  }  // jsonHelp()

  // --------------------------------------------------------------------------------
  // Format the response for JSON
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Array of result objects
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

  public function jsonFormat()
  {
    /*
    $retval = array('success' => ($this->_success ? 1 : 0),
                    'message' => $this->_message,
                    'num'     => $this->_numResults,
                    'results' => $this->_results);
     */
    return json_encode($this->_results);
  }  // jsonFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the Content-Type header for JSON
  // --------------------------------------------------------------------------------

  public function jsonHeader()
  {
    return array("Content-Type", "application/json");
  }  // jsonHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "jsonstore" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

  public function jsonstoreHelp()
  {
    return "Display the entire response object encoded as a JSON object formatted for " .
      "use by the ExtJS JsonStore.  This differs from the 'json' format in that the " .
      "results are returned as an array of objects rather than an array of arrays.";
  }  // jsonstoreHelp()

  // --------------------------------------------------------------------------------
  // Format the response for an ExtJS JsonStore.  The ExtJS JsonStore expects
  // the results to be an array of objects.
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Array of result objects
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

  public function jsonstoreFormat()
  {
    // The ExtJS JsonStore expects the results to be an array of objects and
    // also expects "true" or "false" as the success.

    $results = array();
    foreach ( $this->_results as $id => $result )
    {
      foreach ( $result as $tag => &$value )
      {
        if ( is_array($value) ) $value = (object) $value;
      }
      $results[] = (object) $result;
    }

    $retval = array('success' => ($this->_success ? "true" : "false"),
                    'message' => $this->_message,
                    'num'     => $this->_numResults,
                    'results' => $results);
    return json_encode($retval);
  }  // jsonstoreFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the Content-Type header for JSON
  // --------------------------------------------------------------------------------

  public function jsonstoreHeader()
  {
    return array("Content-Type", "application/json");
  }  // jsonstoreHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "text" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

  public function textHelp()
  {
    return "Display the entire response as text surrounded by <pre></pre> tags.  " .
      "Useful for debugging via a browser";
  }  // textHelp()

  // --------------------------------------------------------------------------------
  // Format the response for text
  //
  // success: 0 = FALSE, 1 = TRUE
  // message: Optional message
  // num: Number of results
  // results: Results list
  //
  // @returns A JSON formatted response.
  // --------------------------------------------------------------------------------

  public function textFormat()
  {
    $retval = array('success' => ($this->_success ? 1 : 0),
                    'message' => $this->_message,
                    'num'     => $this->_numResults,
                    'results' => $this->_results);
    return "<pre>" . print_r($retval, 1) . "</pre>";
  }  // jsonFormat()

  // --------------------------------------------------------------------------------
  // @returns An array containing the Content-Type header for text
  // --------------------------------------------------------------------------------

  public function textHeader()
  {
    return array("Content-Type", "text/plain");
  }  // textHeader()

  // --------------------------------------------------------------------------------
  // @returns  A description of the "raw" format for display by the self-discovery
  //   mechanism.
  // --------------------------------------------------------------------------------

  public function rawHelp()
  {
    return "Display the first item in the result array along with any headers that " .
      "have been set by the API handler.  This is useful when an handler needs to " .
      "return unformatted data such as a file download.";
  }  // rawHelp()

  // --------------------------------------------------------------------------------
  // Format a raw response, typically for download or binary data.  This
  // response type must be supported by the handler (i.e., it must set the
  // correct content type and other headers)
  //
  // @returns The raw result.
  // --------------------------------------------------------------------------------

  public function rawFormat()
  {
    return array_shift($this->_results);
  }  // rawFormat()

  // --------------------------------------------------------------------------------
  // @returns The list of headers for a raw response type.
  // --------------------------------------------------------------------------------

  public function rawHeader()
  {
    return $this->_headers;
  }  // textHeader()

  // --------------------------------------------------------------------------------
  // Accessors

  public function success() { return $this->_success; }
  public function message() { return $this->_message; }
  public function numResults() { return $this->_numResults; }
  public function results() { return $this->_results; }
  public function headers() { return $this->_headers; }

}  // class Ubmod_RestResponse

?>
