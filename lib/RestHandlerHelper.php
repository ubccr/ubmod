<?php
// --------------------------------------------------------------------------------
// @author Steve Gallo
// @date 2008-Jul-14
//
// Singleton database abstraction layer to provide higher level operations on
// the basic Redfly data objects.
// --------------------------------------------------------------------------------

class RestHandlerHelper
{
  // Singleton instance

  private static $instance = NULL;

  // --------------------------------------------------------------------------------

  // Ensure that this class is a singleton

  private function __construct() {}

  // --------------------------------------------------------------------------------

  public static function factory()
  {
    if ( NULL === self::$instance )
    {
      self::$instance = new RestHandlerHelper;
    }

    return self::$instance;
  }  // factory()

  // --------------------------------------------------------------------------------
  // Given a value to be used in a WHERE clause, scan it for API wildcards and
  // convert them to SQL wildcards.  If wildcards were found then convert them
  // to SQL wildcards and return TRUE.
  //
  // @param $value Reference to the value that will be used in the WHERE clause
  //
  // @returns TRUE if wildcards were found
  // --------------------------------------------------------------------------------

  public function convertWildcards(&$value)
  {
    $wildcardsFound = FALSE;
    if ( "*" == $value[0] || "*" == $value[strlen($value) - 1] )
    {
      $value = preg_replace('/^\*|\*$/', '%', $value);
      $wildcardsFound = TRUE;
    }
    return $wildcardsFound;
  }  // convertWildcards()

  // --------------------------------------------------------------------------------
  // Extract optional operators from the start of an argument value and return
  // both the operator and argument value minus the operator.  Valid operators
  // are:
  //
  // Equal: = (default, used if no operator is present)
  // Not equal: !
  // Less Than: <
  // Greater Than: >
  //
  // @param $value Reference to the argument value to be searched, if an
  //   argument is found it will be removed
  // @param $op Reference to the operator found or "=" by default
  //
  // @returns TRUE if an operator was found, FALSE otherwise.
  // --------------------------------------------------------------------------------

  public function extractOperator(&$value, &$op)
  {
    $validOperators = array('=' => '=',
                            '!' => '!=',
                            '>' => '>',
                            '<' => '<');
    $op = "=";
    $operatorFound = FALSE;

    // Bools have no operator
    if ( is_bool($value) ) return $operatorFound;

    if ( array_key_exists($value[0], $validOperators) )
    {
      $op = $validOperators[$value[0]];
      $value = substr($value, 1);
      $operatorFound = TRUE;
    }

    return $operatorFound;

  }  // extractOperator()

  // --------------------------------------------------------------------------------
  // Extract sort columns and optional sort directions from the sort
  // specification.  By default the sort is done in ascending order but if the
  // field is preceeded by a minus then descending order is used (e.g, sort=name
  // vs sort=-name).  Multiple columns may be separated by commas and the
  // available sort operators are "+" for ascending order (the default if no
  // operator is present) and "-" for descending order.
  //
  // @param $sortSpec Sort specification containing one or more comma-separated
  //   sort columns and optional sort direction
  //
  // @returns An array containing sort information where the key is the sort
  //   column and the value is the direction.  The result is order as per the sort
  //   specification.
  // --------------------------------------------------------------------------------

  public function extractSortInfo($sortSpec)
  {
    $sortInformation = array();
    $validSortDirections = array("+", "-");

    // Multiple sort sort columns are comma-separated

    $sortList = explode(",", $sortSpec);
    foreach ( $sortList as $sortColumn )
    {
      if ( in_array($sortColumn[0], $validSortDirections) )
      {
        $direction = ( $sortColumn[0] == "-" ? "DESC" : "ASC" );
        $sortColumn = substr($sortColumn, 1);
        $sortInformation[ $sortColumn ] = $direction;
      }
      else
      {
        $sortInformation[ $sortColumn ] = "ASC";
      }
    }  // foreach ( $sortList as $sortColumn )

    return $sortInformation;

  }  // extractSortInfo()

  // --------------------------------------------------------------------------------
  // Construct the SQL LIMIT string based on the API arguments.  The following
  // arguments are examined:
  //
  // 'limit' - The maximim number of rows to return
  // 'limitoffset' - The offset of the first row to return
  //
  // @param $args The array of API arguments
  //
  // @returns The SQL LIMIT string or an empty string if no limit information
  //   was present.
  // --------------------------------------------------------------------------------

  public function constructLimitStr(array $args)
  {
    if ( ! array_key_exists('limit', $args) ||
         ! is_numeric($args['limit']) ||
         $args['limit'] < 0 )
    {
      return "";
    }

    $limitStr = "";
    if ( array_key_exists('limitoffset', $args) && is_numeric($args['limitoffset']) )
    {
      $limitStr = "LIMIT " . $args['limitoffset'] . "," . $args['limit'];
    } else {
      $limitStr = "LIMIT " . $args['limit'];
    }

    return $limitStr;

  }  // constructLimitStr()

  // --------------------------------------------------------------------------------
  // Construct q query from the base SQL, any additional tables to join, search
  // criteria, order by information, and limit clause.
  //
  // @param $sql Reference to the base SQL statement ending with the FROM or
  //   JOIN clauses.  This will be augmented with the rest of the query.
  // @param $joinTables Optional array containing one or more tables to join
  //   with the existing SQL statement.  These are typically determined
  //   conditionally.
  // @param $criteria Optional query criteria that will be ANDed together
  // @param $groupBy Optional grouping information
  // @param $order Optional ordering information
  // @param $limit Optional limit string
  //
  // @returns Nothing
  // --------------------------------------------------------------------------------

  public function constructQuery(&$sql,
                                 array $joinTables,
                                 array $criteria,
                                 array $order = array(),
                                 array $groupBy = array(),
                                 $limit = "")
  {

    if ( 0 != count($joinTables) )
    {
      $sql .= " JOIN " . implode(" JOIN ", $joinTables);
    }
    
    if ( 0 != count($criteria) )
    {
      $sql .= " WHERE " . implode(" AND ", $criteria);
    }
    
    if ( 0 != count($groupBy) )
    {
      $sql .= " GROUP BY " . implode(",", $groupBy);
    }
    
    if ( 0 != count($order) )
    {
      $sql .= " ORDER BY " . implode(",", $order);
    }
    
    $sql .= " $limit";

  }  // constructQuery()

  // --------------------------------------------------------------------------------
  // Query the database and construct the response object.  If there was an
  // error during the query then the response will contain the error message.
  //
  // @param $db The database resource handle
  // @param $sql The sql statement
  //
  // @returns A RestResponse object with the query result
  // --------------------------------------------------------------------------------

  public function query($db, $sql)
  {
    try {
      $queryResult = $db->query($sql);
      $results = array();
      while ( $row = $queryResult->fetch_assoc() )
      {
        // $results[$row['id'] ] = $row;
        $results[] = $row;
      }
      $response = RestResponse::factory(TRUE, NULL, $results);
    } catch ( Exception $e ) {
      $response = RestResponse::factory(FALSE, $e->getMessage());
    }

    return $response;

  }  // query()

  // --------------------------------------------------------------------------------
  // Convert a search or query option to a boolean value for use when querying
  // the database.  Examples of true values are TRUE, 1, "yes" (or "y"), and
  // "true" (or "t") while false values are FALSE, 0, "no", and "false".
  //
  // @param $value Value to convert
  //
  // @returns The converted value
  // --------------------------------------------------------------------------------

  public function convertValueToBool($value)
  {
    // Need to check if it's bool before we try to lowercase it as a string
    if ( is_bool($value) ) return $value;
    if ( is_numeric($value) ) return ( 1 == $value ? 1 : 0 );

    $value = strtolower($value);
    return ( 0 === strpos($value, "t") || 0 === strpos($value, "y") ? 1 : 0 );
  }  // convertValueToBool()

  // --------------------------------------------------------------------------------
  // Generate the redfly entity id.
  //
  // @param $entityType The entity type (e.g., database resource handle)
  // @param $entityId The database entity identifier
  // @param $version The entity version
  //
  // @returns The formatted entity id
  // --------------------------------------------------------------------------------

  public function entityId($entityType, $entityId, $version)
  {
    return "RF" . $entityType . ":" . sprintf("%08s", $entityId) .
      "." . sprintf("%03s", $version);
  }  // entityId()

  // --------------------------------------------------------------------------------
  // Generate the redfly entity id.
  //
  // @param $entityType The entity type (e.g., database resource handle)
  // @param $entityId The database entity identifier
  // @param $version The entity version
  //
  // @returns The formatted entity id
  // --------------------------------------------------------------------------------

  public function parseEntityId($id, &$type, &$entityNum, &$version)
  {
    $validEntities = array(ReporterconstructHandler::EntityCode,
                           BindingsiteHandler::EntityCode);
    $regex = '/^RF(' . implode("|", $validEntities) . '):([0-9]{8,})\.([0-9]{3,})$/';
    $retval = preg_match($regex, $id, $matches);
    if ( FALSE === $retval || 0 === $retval ) return FALSE;

    $type = (string) $matches[1];
    $entityNum = (int) $matches[2];
    $version = (int) $matches[3];
    return TRUE;
  }  // parseEntityId()

  // --------------------------------------------------------------------------------
  // Format the coordinates of the entity as follows chr:start..stop
  //
  // @param $chr Chromosome
  // @param $start Start coordinate
  // @param $stop Stop coordinate
  //
  // @returns The formatted coordinate string.
  // --------------------------------------------------------------------------------

  public function formatCoordinates($chr, $start, $end)
  {
    return $chr . ":" . $start . ".." . $end;
  }  // formatCoordinates()

  // --------------------------------------------------------------------------------
  // Return TRUE if the coordinate version is supported.
  //
  // @param $version Coordinate version (e.g., "r3")
  //
  // @returns TRUE if the coordinate version is supported
  // --------------------------------------------------------------------------------

  public function isSupportedCoordinateVersion($version)
  {
    return ( in_array($version, array("r3", "r4", "r5")) );
  }  // isSupportedCoordinateVersion()

  // --------------------------------------------------------------------------------
  // Construct a url for accessing a flymine image of the specified region.
  //
  // @param $entityName Name of the REDfly entity
  // @param $coordinates REDfly entity coordinates
  // @param $entityStart REDfly entity start coordinate
  // @param $entityEnd REDfly entity end coordinate
  // @param $geneFbgn Flybase id for the gene
  // @param $geneStart Gene start coordinates
  // @param $geneEnd Gene end coordinates
  //
  // @returns The URL for retrieving the flymine image
  // --------------------------------------------------------------------------------

  public function constructFlymineUrl($entityName, $coordinates,
                                      $entityStart, $entityEnd,
                                      $geneFbgn, $geneStart, $geneEnd)
  {
    $zoom = 4000;
    $startCoordinates = NULL;
    $stopCoordinates = NULL;
    $bufferSize = 4000;

    // Generate coordinates that will drive the size of the chromosome
    // slice to be displayed on the chart.  We are calculating the
    // window size that is relative to the gene start and end
    // positions.  For example, if the gene is FBgn0004102
    // (X:8524192..8544710) and the window size is 0..20519 the gene
    // will fill the window.  If we would like to display 5kbp on
    // either side then we would use -5000..25519
    //
    // For a description of gbrowse options see:
    //
    // http://www.flymine.org/cgi-bin/gbrowse_img/flymine-release-17.0/

    $geneSize = $geneEnd - $geneStart;

    if ( $entityEnd <= $geneStart )
    {
      // Entity is entirely 5' of the gene.  If on the negative strand
      // we will want to swap window start and end values.

      // $windowStart = - ($geneStart - $entityStart + $bufferSize);
      // $windowEnd = $geneSize + $bufferSize;
      $windowStart = - $bufferSize;
      $windowEnd = $geneSize + ($geneStart - $entityStart) + $bufferSize;
    }
    else if ( $entityStart >= $geneEnd )
    {
      // Entity is entirely 3' of the gene
      $windowStart = - $bufferSize;
      $windowEnd = $geneSize + $bufferSize;
    }
    else if ( $entityStart >= $geneStart && $entityEnd <= $geneEnd )
    {
      // Entity is contained within the gene
      $windowStart = - $bufferSize;
      $windowEnd = $geneSize + $bufferSize;
    }
    else if ( $entityStart < $geneStart && $entityEnd > $geneEnd )
    {
      // Gene is contained within the entity
      $windowStart = $geneStart - $entityStart - $bufferSize;
      $windowEnd = $geneSize + ($entityEnd - $geneEnd) + $bufferSize;
    }
    else if ( $entityStart < $geneStart && $entityEnd > $geneStart && $entityEnd < $geneEnd )
    {
      // Entity starts 5' of gene and ends within the gene
      $windowStart = $geneStart - $enityStart - $bufferSize;
      $windowEnd = $geneSize + $bufferSize;
    }
    else if ( $entityEnd > $geneEnd && $entityStart > $geneStart && $entityStart < $geneEnd )
    {
      // Entity starts within the gene and ends 3' of the gene
      $windowStart = - $bufferSize;
      $windowEnd = $geneSize + ($entityEnd - $geneEnd) + $bufferSize;
    }

    
    $highlightRegion = "h_region=Drosophila_melanogaster_chr_" . $coordinates;
    $displayRegion = "name=" . $geneFbgn . ":" . $windowStart . ".." . $windowEnd;
    $imageWidth = "w=550";

    $url = "http://www.flymine.org/cgi-bin/gbrowse_img/flymine-release-17.0/?type=Genes+CRMs+TFBindingSites;h_feat=" . urlencode($entityName) . "@blue;" . $highlightRegion . ";o=CRMs+1+TFBindingSites+1;" . $displayRegion . ";" . $imageWidth . ";b=1";

    return $url;

  }  // constructFlymineUrl()


}  // class RestHandlerHelper
