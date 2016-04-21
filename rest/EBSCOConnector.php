<?php
/**
 * EBSCO Connector class
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/EBSCOConnector.php
 * APP NAME: Publication Finder API Sample
 **/

/**
 * EBSCOException class
 * Used when EBSCO API calls return an error message
 */
class EBSCOException extends Exception
{

}

/**
   Class: EBSCOConnector

   Functions that connect to the API server:
 **/
class EBSCOConnector
{
    /**
     * Error codes defined by EDS API
     *
     * @global integer EDS_UNKNOWN_PARAMETER  Unknown Parameter
     * @global integer EDS_INCORRECT_PARAMETER_FORMAT  Incorrect Parameter Format
     * @global integer EDS_INCORRECT_PARAMETER_FORMAT  Invalid Parameter Index
     * @global integer EDS_MISSING_PARAMETER  Missing Parameter
     * @global integer EDS_AUTH_TOKEN_INVALID  Auth Token Invalid
     * ...
     */
    const EDS_UNKNOWN_PARAMETER          = 100;
    const EDS_INCORRECT_PARAMETER_FORMAT = 101;
    const EDS_INVALID_PARAMETER_INDEX    = 102;
    const EDS_MISSING_PARAMETER          = 103;
    const EDS_AUTH_TOKEN_INVALID         = 104;
    const EDS_INCORRECT_ARGUMENTS_NUMBER = 105;
    const EDS_UNKNOWN_ERROR              = 106;
    const EDS_AUTH_TOKEN_MISSING         = 107;
    const EDS_SESSION_TOKEN_MISSING      = 108;
    const EDS_SESSION_TOKEN_INVALID      = 109;
    const EDS_INVALID_RECORD_FORMAT      = 110;
    const EDS_UNKNOWN_ACTION             = 111;
    const EDS_INVALID_ARGUMENT_VALUE     = 112;
    const EDS_CREATE_SESSION_ERROR       = 113;
    const EDS_REQUIRED_DATA_MISSING      = 114;
    const EDS_TRANSACTION_LOGGING_ERROR  = 115;
    const EDS_DUPLICATE_PARAMETER        = 116;
    const EDS_UNABLE_TO_AUTHENTICATE     = 117;
    const EDS_SEARCH_ERROR               = 118;
    const EDS_INVALID_PAGE_SIZE          = 119;
    const EDS_SESSION_SAVE_ERROR         = 120;
    const EDS_SESSION_ENDING_ERROR       = 121;
    const EDS_CACHING_RESULTSET_ERROR    = 122;


    /**
     * HTTP status codes constants
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    const HTTP_OK                    = 200;
    const HTTP_BAD_REQUEST           = 400;
    const HTTP_NOT_FOUND             = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;

      
    /**
       @global string EBSCO API server url
     */
    private static $_end_point ;

    /**
       @global string EBSCO PUBLICATION API url
     */
    private static $_end_point_p ;
    
    /**
       @global string EBSCO API server auth url
     */
    private static $_authentication_end_point ;

    /**
       @global string password used for API transactions
     */
    private $_password;

    /**
     * @global string User id used for API transactions
     */
    private $_userId;

    /**
       @global string Interface ID used for API transactions
     */
    private $_interfaceId;

    /**
      @global string Customer ID used for API transactions
     */
    private $_orgId;
 
    /**
     * Constructor
     * Setup EBSCO API credentials
     *
     * @access public
     */
    public function __construct()
    {      
        //Search in Config.xml for authentication credentials  
        $xml ="Config.xml";
        $dom = new DOMDocument();
        $dom->load($xml);      
        
        //Save urls
        self::$_end_point = $dom->getElementsByTagName('EndPoint')->item(0)->nodeValue;
        self::$_end_point_p = $dom->getElementsByTagName('EndPointPublication')->item(0)->nodeValue;
        self::$_authentication_end_point=$dom->getElementsByTagName('AuthenticationEndPoint')->item(0)->nodeValue;
        
        //Save EDS Credentials
        $EDSCredentials = $dom ->getElementsByTagName('EDSCredentials')->item(0);
        $users = $EDSCredentials -> getElementsByTagName('User');
        
        foreach ($users as $user) {
            $userType = $user->getElementsByTagName('ClientUser')->item(0)->nodeValue;
            $this->userId = $user->getElementsByTagName('EDSUserID')->item(0)->nodeValue;
            $this->password = $user->getElementsByTagName('EDSPassword')->item(0)->nodeValue;                
            $this->interfaceId = $user->getElementsByTagName('EDSProfile')->item(0)->nodeValue;
            $this->orgId = '';
            break;
        }
    }

    /**
     * Function: request
     * Send an HTTP request and inspect the response
     *
     * @param string $url     The url of the HTTP request
     * @param string $params  The parameters of the HTTP request
     * @param array  $headers The headers of the HTTP request
     * @param string $method  The HTTP method, default is 'GET'
     *
     * @return SimpleXml      Server response (string) converted to SimpleXML object
     * @access protected
     */
    protected function request($url, $params = null, $headers = null, $method = 'GET') 
    {
        $log = fopen('curl.log', 'w'); // for debugging cURL
        $xml = false;

        // Create a cURL instance
        $ch = curl_init();

        // Set the cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $log);  // for debugging cURL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Termporary

        // Set the query parameters and the url
        if (empty($params)) {
            // Only Info request has empty parameters
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($method == 'GET') { 
                //GET method
                $url .= '?' . $params;                
                curl_setopt($ch, CURLOPT_URL, $url);
            } else {
                // POST method
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } 

        // Set the header
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Send the request
        $response = curl_exec($ch);
        //Save XML file for debug mode      
        if (strstr($url, 'Search')) {
            $_SESSION['resultxml'] = $response;
        }
        if (strstr($url, 'Retrieve')) {
            $_SESSION['recordxml'] = $response;
        }
        // Parse the response
        // In case of errors, throw 2 type of exceptions
        // EBSCOException if the API returned an error message
        // Exception in all other cases. Should be improved for better handling
        if ($response === false) {
            fclose($log); // for debugging cURL
            throw new Exception(curl_error($ch));
            curl_close($ch);
        } else {         
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            fclose($log);  // for debugging cURL
            curl_close($ch);
            switch ($code) {
            case self::HTTP_OK:
                $xml = simplexml_load_string($response);
                
                if ($xml === false) {
                    throw new Exception('Error while parsing the response.');
                } else {
                    return $xml;
                }
                break;
            case self::HTTP_BAD_REQUEST:
                $xml = simplexml_load_string($response);
                if ($xml === false) {
                    throw new Exception('Error while parsing the response.');
                } else {
                    // If the response is an API error
                    $error = ''; $code = 0;
                    $isError = isset($xml->ErrorNumber) || isset($xml->ErrorCode);
                    if ($isError) {
                        if (isset($xml->DetailedErrorDescription) && !empty($xml->DetailedErrorDescription)) {
                            $error = (string) $xml->DetailedErrorDescription;
                        } else if (isset($xml->ErrorDescription)) {
                            $error = (string) $xml->ErrorDescription;
                        } else if (isset($xml->Reason)) {
                            $error = (string) $xml->Reason;
                        }
                        if (isset($xml->ErrorNumber)) {
                            $code = (integer) $xml->ErrorNumber;
                        } else if (isset($xml->ErrorCode)) {
                            $code = (integer) $xml->ErrorCode;
                        }
                        throw new EBSCOException($error, $code);
                    } else {
                        throw new Exception('The request could not be understood by the server due to malformed syntax. Modify your search before retrying.');
                    }
                }
                break;
            case self::HTTP_NOT_FOUND:
                throw new Exception('The resource you are looking for might have been removed, had its name changed, or is temporarily unavailable.');
                break;
            case self::HTTP_INTERNAL_SERVER_ERROR:
                throw new Exception('The server encountered an unexpected condition which prevented it from fulfilling the request.');
                break;
            // Other HTTP status codes
            default:
                throw new Exception('Unexpected HTTP error.');
                break;
            }
        }
    }
    
    /**
     * Function: requestAuthenticationToken
     * Request the authentication token
     *
     * @return SimpleXml      Authentication token in SimpleXML format
     * @access public
     */
    public function requestAuthenticationToken()
    {
        $url = self::$_authentication_end_point.'/UIDAuth';
        // Add the body of the request. Important.
        $params =<<<BODY
<UIDAuthRequestMessage xmlns="http://www.ebscohost.com/services/public/AuthService/Response/2012/06/01">
    <UserId>{$this->userId}</UserId>
    <Password>{$this->password}</Password>
    <InterfaceId>{$this->interfaceId}</InterfaceId>
</UIDAuthRequestMessage>
BODY;
        
        // Set the headers of the request
        // Set the content type to 'application/xml'. Important, otherwise cURL will use the usual POST content type.
        $headers = array(
            'Content-Type: application/xml',
            'Conent-Length: ' . strlen($params)
        );

        $response = $this->request($url, $params, $headers, 'POST');
        return $response;
    }




    //A PARTIR DE ACA NO

    /**
     * Function: requestSessionToken
     * Request the session token
     *
     * @param array  $headers Authentication token
     * @param string $profile 
     * @param string $guest   
     *
     * @return string $response The session token
     * @access public
     */
    public function requestSessionToken($headers, $profile, $guest)
    {
        $url = self::$_end_point . '/CreateSession';
        
        // Add the HTTP query parameters
        $params = array(
            'profile' => $profile,
            'org'     => $this->orgId,
            'guest'   => $guest
        );
        $params = http_build_query($params);
        $response = $this->request($url, $params, $headers);
        return $response;
    }
    
    /**
     * Function: requestEndSessionToken
     * End the session token
     * 
     * @param array $headers      Session token
     * @param array $sessionToken Session token
     *
     * @return void
     * @access public
     */
    public function requestEndSessionToken($headers, $sessionToken)
    {
        $url = self::$_end_point.'/endsession';
        
        // Add the HTTP query parameters
        $params = array(
            'sessiontoken'=>$sessionToken
        );
        $params = http_build_query($params);              
        $this->request($url, $params, $headers);
    }

    /**
     * Function: requestSearch
     * Request the search records
     *
     * @param array $params  Search specific parameters
     * @param array $headers Authentication and session tokens
     *
     * @return array $response An associative array of data
     * @access public
     */
    public function requestSearch($params, $headers)
    {
        $url = self::$_end_point_p . '/Search';
        $response = $this->request($url, $params, $headers);
        return $response;
    }

    /**
     * Function: requestRetrieve
     * Request a specific record
     *
     * @param array $params  Retrieve specific parameters
     * @param array $headers Authentication and session tokens
     *
     * @return  array    An associative array of data
     * @access public
     */
    public function requestRetrieve($params, $headers)
    {
        $url = self::$_end_point . '/Retrieve';

        $response = $this->request($url, $params, $headers);
        return $response;
    }


    /**
     * Function: requestInfo
     * Request the info data
     *
     * @param null  $params  Not used
     * @param array $headers Authentication and session tokens
     *
     * @return  array $response An associative array of data
     * @access public
     */
    public function requestInfo($params, $headers)
    {
        $url = self::$_end_point . '/Info';

        $response = $this->request($url, $params, $headers);
        return $response;
    }



}


?>