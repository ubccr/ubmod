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
 * REST response message.
 *
 * @author Steve Gallo
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * REST response message.
 *
 * This class is responsible for constructing and formating a response
 * to the REST API.  Valid response formats are indicated by the
 * definition of a method with the response format (lowercase) followed
 * by "Format" (e.g., jsonFormat, xmlFormat, etc.).  Each format method
 * should have a corresponding method for returning (but not actually
 * sending to the browser) a list of headers which should include the
 * MIME header string (e.g., jsonHeader).  The handlers can provide an
 * optional list of headers, which is especially useful for the raw
 * handler and file downloads.
 *
 * See http://www.iana.org/assignments/media-types/application/
 *
 * @package Ubmod
 */
class Ubmod_RestResponse
{

  /**
   * Indicates a successful response
   *
   * @var bool
   */
  private $_success = true;

  /**
   * Repsonse message
   *
   * @var string
   */
  private $_message = null;

  /**
   * The response results
   *
   * @var array
   */
  private $_results = array();

  /**
   * Total number of results
   *
   * This may be larger than the number of results that are returned if
   * paging is being used.
   *
   * @var int
   */
  private $_total = 0;

  /**
   * The number of results that are being returned
   *
   * @var int
   */
  private $_numResults = 0;

  /**
   * Headers used in the response
   *
   * @var array
   */
  private $_headers = null;

  /**
   * Filename used by response formats that return a file
   *
   * @var string
   */
  private $_filename = null;

  /**
   * Column heading and keys for response formats that return data in
   * a column format
   *
   * @var array
   */
  private $_columns = array();

  /**
   * Factory pattern.
   *
   * @param array $params
   *   success  => bool
   *   message  => string
   *   results  => array
   *   total    => int
   *   headers  => array
   *   filename => string
   *   columns  => array
   *
   * @return Ubmod_RestResponse
   */
  public static function factory(array $params = array())
  {
    return new Ubmod_RestResponse($params);
  }

  /**
   * Construct a new instance of a Ubmod_RestResponse object.
   *
   * The constructor is private and is meant to be called by the
   * factory() method.
   *
   * @see factory()
   *
   * @param array $params
   *   success  => bool
   *   message  => string
   *   results  => array
   *   total    => int
   *   headers  => array
   *   filename => string
   *   columns  => array
   *
   * @return Ubmod_RestResponse
   */
  private function __construct(array $params = array())
  {
    if (array_key_exists('success', $params)) {
      $this->_success = (bool) $params['success'];
    }

    if (array_key_exists('message', $params)) {
      if (!is_string($params['message'])) {
        throw new Exception('"message" must be a string');
      }
      $this->_message = $params['message'];
    }

    if (array_key_exists('results', $params)) {
      if (!is_array($params['results'])) {
        throw new Exception('"results" must be an array');
      }
      $this->_results = $params['results'];
      $this->_numResults = count($this->_results);
    }

    if (array_key_exists('total', $params)) {
      if (!is_numeric($params['total'])) {
        throw new Exception('"total" must be a number');
      }
      $this->_total = (int) $params['total'];
    } else {
      $this->_total = $this->_numResults;
    }

    if (array_key_exists('headers', $params)) {
      if (!is_array($params['headers'])) {
        throw new Exception('"headers" must be an array');
      }
      $this->_headers = $params['headers'];
    }

    if (array_key_exists('filename', $params)) {
      if (!is_string($params['filename'])) {
        throw new Exception('"filename" must be a string');
      }
      $this->_filename = $params['filename'];
    }

    if (array_key_exists('columns', $params)) {
      if (!is_array($params['columns'])) {
        throw new Exception('"columns" must be an array');
      }
      $this->_columns = $params['columns'];
    }
  }

  /**
   * Return a description of the "json" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function jsonHelp()
  {
    return 'Display the entire response object encoded as a JSON object.  '
      . 'The response will be returned as an object where the results is an '
      . 'array of arrays.';
  }

  /**
   * Format the response for JSON
   *
   * @return string A JSON formatted response.
   */
  public function jsonFormat()
  {
    return json_encode($this->_results);
  }

  /**
   * Return an array containing the Content-Type header for JSON.
   *
   * @return array
   */
  public function jsonHeader()
  {
    return array(array('Content-Type', 'application/json'));
  }

  /**
   * Return a description of the "jsonstore" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function jsonstoreHelp()
  {
    return 'Display the entire response object encoded as a JSON object '
      . 'formatted for use by the ExtJS JsonStore.  This differs from the '
      . '"json" format in that the results are returned as an array of '
      . 'objects rather than an array of arrays.';
  }

  /**
   * Format the response for an ExtJS JsonStore.
   *
   * The ExtJS JsonStore expects the results to be an array of objects.
   *
   * @return array A JSON formatted response.
   */
  public function jsonstoreFormat()
  {
    $results = array();
    foreach ($this->_results as $result) {
      foreach ($result as $value) {
        if (is_array($value)) {
          $value = (object) $value;
        }
      }
      $results[] = (object) $result;
    }

    $retval = array(
      'success' => $this->_success,
      'message' => $this->_message,
      'total'   => $this->_total,
      'results' => $results,
    );
    return json_encode($retval);
  }

  /**
   * Return an array containing the Content-Type header for JSON.
   *
   * @return array
   */
  public function jsonstoreHeader()
  {
    return array(array('Content-Type', 'application/json'));
  }

  /**
   * Return a description of the "csv" format for display by
   * the self-discovery mechanism.
   *
   * @return string
   */
  public function csvHelp()
  {
    return 'Format data as a CSV';
  }

  /**
   * Format the response for a CSV file
   *
   * @return string The contents of a CSV file
   */
  public function csvFormat()
  {
    if (   !isset($this->_columns)
        || !isset($this->_results))
    {
        throw new Exception('Unsupported output format');
    }

    $data = $this->_results;
    $columns = $this->_columns;

    array_unshift($data, $columns);

    // Coverted data to array of numeric arrays
    $keys = array_keys($columns);
    $mapFunc = function ($row) use($keys) {
      $mappedRow = array();
      foreach ($keys as $key) {
        $mappedRow[] = $row[$key];
      }
      return $mappedRow;
    };
    $mappedData = array_map($mapFunc, $data);

    // Quote fields where necessary
    $quoteFunc = function ($field) {
      // Escape double-quotes
      if (strpos($field, '"') !== false) {
        $field = '"' . str_replace('"', '""', $field) . '"';
      }
      // Quote fields with spaces, commas and single-quotes
      elseif (preg_match("/[\s,']/s", $field)) {
        $field = '"' . $field . '"';
      }
      return $field;
    };

    $csvRows = array();
    foreach ($mappedData as $row) {
      $quotedFields = array_map($quoteFunc, $row);
      $csvRows[] = implode(',', $quotedFields);
    }

    return implode("\n", $csvRows);
  }

  /**
   * Return an array containing the Content-Type header for csv and the
   * content disposition for an attachment.
   *
   * @return array
   */
  public function csvHeader()
  {
    $filename = $this->_filename . '.csv';

    return array(
      array('Content-type',        'text/csv'),
      array('Content-disposition', 'attachment; filename=' . $filename),
    );
  }

  /**
   * Return a description of the "tsv" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function tsvHelp()
  {
    return 'Format data as a tab delimted file';
  }

  /**
   * Format the response for a tab delimited file
   *
   * @return string The contents of a tab delimited file
   */
  public function tsvFormat()
  {
    if (   !isset($this->_columns)
        || !isset($this->_results))
    {
        throw new Exception('Unsupported output format');
    }

    $data = $this->_results;
    $columns = $this->_columns;

    array_unshift($data, $columns);

    // Coverted data to array of numeric arrays
    $keys = array_keys($columns);
    $mapFunc = function ($row) use($keys) {
      $mappedRow = array();
      foreach ($keys as $key) {
        $mappedRow[] = $row[$key];
      }
      return $mappedRow;
    };
    $mappedData = array_map($mapFunc, $data);

    $tsvRows = array();
    foreach ($mappedData as $row) {
      $tsvRows[] = implode("\t", $row);
    }

    return implode("\n", $tsvRows);
  }

  /**
   * Return an array containing the Content-Type header for tsv and the
   * content disposition for an attachment.
   *
   * @return array
   */
  public function tsvHeader()
  {
    $filename = $this->_filename . '.tsv';

    return array(
      array('Content-type',        'text/tsv'),
      array('Content-disposition', 'attachment; filename=' . $filename),
    );
  }

  /**
   * Return a description of the "xls" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function xlsHelp()
  {
    return 'Format data as a Microsoft Excel file';
  }

  /**
   * Format the response for a Microsoft Excel file
   *
   * @return string The contents of a Microsoft Excel file
   */
  public function xlsFormat()
  {
    if (   !isset($this->_columns)
        || !isset($this->_results))
    {
        throw new Exception('Unsupported output format');
    }

    $data = $this->_results;
    $columns = $this->_columns;

    array_unshift($data, $columns);

    // Coverted data to array of numeric arrays
    $keys = array_keys($columns);
    $mapFunc = function ($row) use($keys) {
      $mappedRow = array();
      foreach ($keys as $key) {
        $mappedRow[] = $row[$key];
      }
      return $mappedRow;
    };
    $mappedData = array_map($mapFunc, $data);

    $workbook = new Spreadsheet_Excel_Writer();

    $sheet =& $workbook->addWorksheet('');

    foreach ($mappedData as $y => $row) {
      foreach ($row as $x => $value) {
        $sheet->write($y, $x, $value);
      }
    }

    // Prevent headers from being sent
    ob_start();
    $workbook->send('bogus.xls');
    ob_end_clean();

    // Return buffered output
    ob_start();
    $workbook->close();
    return ob_get_contents();
  }

  /**
   * Return an array containing the Content-Type header for a Microsoft
   * Excel file and the content disposition for an attachment.
   *
   * @return array
   */
  public function xlsHeader()
  {
    $filename = $this->_filename . '.xls';

    return array(
      array('Content-type',        'application/vnd.ms-excel'),
      array('Content-disposition', 'attachment; filename=' . $filename),
    );
  }

  /**
   * Return a description of the "text" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function textHelp()
  {
    return 'Display the entire response as text surrounded by <pre></pre> '
      . 'tags.  Useful for debugging via a browser';
  }

  /**
   * Format the response for text
   *
   * @return string A JSON formatted response.
   */
  public function textFormat()
  {
    $retval = array(
      'success'     => $this->_success ? 'true' : 'false',
      'message'     => $this->_message,
      'total'       => $this->_total,
      'filename'    => $this->_filename,
      'num_results' => $this->_numResults,
      'results'     => $this->_results,
    );
    return print_r($retval, true);
  }

  /**
   * Return an array containing the Content-Type header for text.
   *
   * @return array
   */
  public function textHeader()
  {
    return array(array('Content-Type', 'text/plain'));
  }

  /**
   * Return a description of the "raw" format for display by the
   * self-discovery mechanism.
   *
   * @return string
   */
  public function rawHelp()
  {
    return 'Display the first item in the result array along with any '
      . 'headers that have been set by the API handler.  This is useful when '
      . 'an handler needs to return unformatted data such as a file '
      . 'download.';
  }

  /**
   * Format a raw response, typically for download or binary data.
   *
   * This response type must be supported by the handler (i.e., it must
   * set the correct content type and other headers)
   *
   * @return mixed The raw result.
   */
  public function rawFormat()
  {
    return array_shift($this->_results);
  }

  /**
   * Return the list of headers for a raw response type.
   *
   * @return array
   */
  public function rawHeader()
  {
    return $this->_headers;
  }

  /**
   * Return the success status of this response.
   *
   * @return bool
   */
  public function success()
  {
    return $this->_success;
  }

  /**
   * Return the message of this response.
   *
   * @return string
   */
  public function message()
  {
    return $this->_message;
  }

  /**
   * Return the total number of results.
   *
   * This may be larger than the number of results that are returned if
   * paging is being used.
   *
   * @return int
   */
  public function total()
  {
    return $this->_total;
  }

  /**
   * Return the number of results in this response.
   *
   * @return int
   */
  public function numResults()
  {
    return $this->_numResults;
  }

  /**
   * The return the results in this response.
   *
   * @return array
   */
  public function results()
  {
    return $this->_results;
  }

  /**
   * The return the raw headers in this response.
   *
   * @return array
   */
  public function headers()
  {
    return $this->_headers;
  }
}

