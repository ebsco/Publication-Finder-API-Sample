<?php
/**
 * EBSCO API class
 * LOCATION: http://widgets.ebscohost.com/prod/ftf-atoz/EBSCOAPI.php
 * APP NAME: Publication Finder API Sample
 **/

require_once 'EBSCOConnector.php';
require_once 'EBSCOResponse.php';

/**
   Class: EBSCO API

   Functions related to API transactions:
   connection, authentication, searches.
 **/
class EBSCOAPI
{
    /*
      The authentication token used for API transactions
      @global string
     */
    private $_authenticationToken;

    /*
      The session token for API transactions
      @global string
     */
    private $_sessionToken;

    /*
      The EBSCOConnector object used for API transactions
      @global object EBSCOConnector
     */
    private $_connector;

    /**
     * Function: connector
     * Create a new EBSCOConnector object or reuse an existing one
     *
     * @return EBSCOConnector object
     * @access public
     */
    public function connector()
    {
        if (empty($this->connector)) {
            $this->connector = new EBSCOConnector();
        }

        return $this->connector;
    }

    /**
     * Function: response
     * Create a new EBSCOResponse object
     *
     * @param EBSCOResponse $response object
     *
     * @return EBSCOResponse object
     * @access public
     */
    public function response($response)
    {
        $responseObj = new EBSCOResponse($response);
        return $responseObj;
    }

    /**
     * Function: apiAuthenticationToken
     * Wrapper for authentication API call
     *
     * @return array      An associative array of data or the SimpleXml object itself in case of API error messages
     * @access public
     */
    public function apiAuthenticationToken()
    {
        $response = $this->connector()->requestAuthenticationToken();
        $result = $this->response($response)->result();
        return $result;
    }

    /**
     * Function: getInfo
     * Wrapper for info API call
     *
     * @return array $info An associative array of data
     * @access public
     */
    public function getInfo()
    {
        if (isset($_SESSION['info'])) {
            $InfoArray = $_SESSION['info'];
            $info = $InfoArray['Info'];
        } else {              
            // Get new Info for the profile
            $InfoArray = $this->apiInfo();
            $_SESSION['info'] = $InfoArray;
            $info = $InfoArray['Info'];          
        }
        return $info;
    }
    
    /**
     * Function: apiInfo
     * Wrapper for info API call
     *
     * @return array $info An associative array of data
     * @access public
     */
    public function apiInfo()
    { 
        $response = $this->request('Info', '');
        $Info = array(
            'Info' => $response,
            'timestamp'=>time()
        ); 
        return $Info;
    }

    /**
     * Function: request
     * Request authentication and session tokens, then send the API request.
     * Retry the request if authentication errors occur
     *
     * @param string  $action   The EBSCOConnector method name
     * @param array   $params   The parameters for the HTTP request
     * @param integer $attempts The number of retries. The default number is 3 but can be increased.
     * 3 retries can handle a situation when both autentication and session tokens need to be refreshed + the current API call
     *
     * @return array              An associative array with results.
     * @access protected
     */
    protected function request($action, $params = null, $attempts = 3)
    {
        try {
            $_authenticationToken = $this->getAuthToken();
            $_sessionToken = $this ->getSessionToken($_authenticationToken);
            
            if (empty($_authenticationToken)) {
                $_authenticationToken = $this -> getAuthToken();
            }
           
            if (empty($_sessionToken)) {
                $_sessionToken = $this -> getSessionToken($_authenticationToken, 'y');
            }
                   
            $headers = array(
                'x-authenticationToken: ' . $_authenticationToken,
                'x-sessionToken: ' . $_sessionToken
            );

            $response = call_user_func_array(array($this->connector(), "request{$action}"), array($params, $headers));
            $result = $this->response($response)->result();
            $results = $result;             
            return $results;
        } catch(EBSCOException $e) {
            try {
                // Retry the request if there were authentication errors
                $code = $e->getCode();
                switch ($code) {
                case EBSCOConnector::EDS_AUTH_TOKEN_INVALID:
                    $_authenticationToken = $this->getAuthToken();                
                    $_sessionToken = $this ->getSessionToken($_authenticationToken);
                    $headers = array(
                    'x-authenticationToken: ' . $_authenticationToken,
                    'x-sessionToken: ' . $_sessionToken
                    );
                    if ($attempts > 0) {
                        return $this->request($action, $params, $headers, --$attempts);
                    }
                    break;
                case EBSCOConnector::EDS_SESSION_TOKEN_INVALID:
                    $_sessionToken = $this ->getSessionToken($_authenticationToken, 'y');
                    $headers = array(
                    'x-authenticationToken: ' . $_authenticationToken,
                    'x-sessionToken: ' . $_sessionToken
                    );
                    if ($attempts > 0) {
                        return $this->request($action, $params, $headers, --$attempts);
                    }
                    break;
                default:
                    $result = array(
                        'error' => $e->getMessage()
                    );
                    return $result;
                    break;
                }
            }  catch(Exception $e) {
                $result = array(
                    'error' => $e->getMessage()
                );
                return $result;
            }
        } catch(Exception $e) {
            $result = array(
                'error' => $e->getMessage()
            );
            return $result;
        }
    }
    
    /**
     * Function: getAuthToken
     * Get authentication token from application scop 
     * Check authToen's expiration 
     * if expired get a new authToken and re-new the time stamp
     * 
     * @return $authToken
     * @access public
     **/
    public function getAuthToken() 
    {
        $lockFile = fopen("lock.txt", "r");
        $tokenFile = fopen("token.txt", "r");
        while (!feof($tokenFile)) {
            $authToken = rtrim(fgets($tokenFile), "\n");
            $timeout = fgets($tokenFile)-600;
            $timestamp = fgets($tokenFile);
        }
        fclose($tokenFile);

        //Lock check.
        if (flock($lockFile, LOCK_EX)) {
            $tokenFile = fopen("token.txt", "w+");
            $result = $this->apiAuthenticationToken();
            fwrite($tokenFile, $result['authenticationToken']."\n");
            fwrite($tokenFile, $result['authenticationTimeout']."\n");
            fwrite($tokenFile, $result['authenticationTimeStamp']);
            fclose($tokenFile);
            return $result['authenticationToken'];
        } else {
            return $authToken;
        }
        fclose($lockFile);       
    }

    /**
     * Function: getSessionToken
     * Get session token for a profile 
     * If session token is not available 
     * a new session token will be generated
     * 
     * @param string $authenToken  
     * @param string $invalid     
     *
     * @return $token
     * @access public
     */
    public function getSessionToken($authenToken, $invalid='n')
    {
        $token = ''; 
        
        // Check user's login status
        if (isset($_COOKIE['login'])) {              
            if ($invalid=='y') {                   
                $profile = $_SESSION['sessionToken']['profile'];
                $_sessionToken = $this->apiSessionToken($authenToken, $profile, 'n');                  
                $_SESSION['sessionToken']=$_sessionToken;                 
            }
            $token = $_SESSION['sessionToken']['sessionToken'];            
        } else if (isset($_COOKIE['Guest'])) {
            if ($invalid=='y') {                   
                $profile = $_SESSION['sessionToken']['profile'];
                $_sessionToken = $this->apiSessionToken($authenToken, $profile, 'y');   
                $_SESSION['sessionToken']=$_sessionToken;
            }     
            $token = $_SESSION['sessionToken']['sessionToken'];   
        } else {            
            $xml ="Config.xml";
            $dom = new DOMDocument();
            $dom->load($xml); 
            $EDSCredentials = $dom ->getElementsByTagName('EDSCredentials')->item(0);
            $users = $EDSCredentials -> getElementsByTagName('User');
            $profileId = '';
            foreach ($users as $user) {
                $userType = $user->getElementsByTagName('ClientUser')->item(0)->nodeValue;
                if ($userType == 'guest') {                     
                    $profileId = $user->getElementsByTagName('EDSProfile')->item(0)->nodeValue;               
                    break;
                }
            }                     
            $_sessionToken = $this->apiSessionToken($authenToken, $profileId, 'y');
            $_SESSION['profile'] = $profileId;   
            $_SESSION['sessionToken']=$_sessionToken;          
            setcookie("Guest", $profileId, 0);
            $token = $_sessionToken['sessionToken'];           
        }     
        return $token;
    }

    /**
     * Function: apiSessionToken
     * Wrapper for session API call
     *
     * @param string $authenToken
     * @param string $profile
     * @param string $guest
     *
     * @return $token
     * @access public
     */
    public function apiSessionToken($authenToken, $profile, $guest)
    {
        // Add authentication tokens to headers
        $headers = array(
            'x-authenticationToken: ' . $authenToken
        );

        $response = $this->connector()->requestSessionToken($headers, $profile, $guest);
        $result = $this->response($response)->result();
        $token = array(
            'sessionToken'=>$result,
            'profile' => $profile
        );   
         return $token;
    }
   
    /**
     * Function: apiEndSessionToken
     * Wrapper for end session API call
     *
     * @param string $authenToken
     * @param string $sessionToken
     *
     * @return void
     * @access public
     */
    public function apiEndSessionToken($authenToken, $sessionToken)
    { 
        // Add authentication tokens to headers
        $headers = array(
            'x-authenticationToken: '.$authenToken
        );
        
        $this -> connector()->requestEndSessionToken($headers, $sessionToken);
    }

    /**
     * Function: apiSearch
     * Wrapper for search API call
     *
     * @param Array $params
     *
     * @throws object             PEAR Error
     * @return array              An array of query results
     * @access public
     */
    public function apiSearch($params) 
    {  
        $results = $this->request('Search', $params);
        return $results;
    }


    /**
     * Function: apiRetrieve
     * Wrapper for retrieve API call
     *
     * @param array  $an   The accession number
     * @param string $db   The short database name
     * @param string $term 
     *
     * @throws object             PEAR Error
     * @return array              An associative array of data
     * @access public
     */
    public function apiRetrieve($an, $db, $term)
    {
        // Add the HTTP query params
        $params = array(
            'an'        => $an,
            'dbid'      => $db,
            'highlightterms' => $term // Get currect param name
        );
        $params = http_build_query($params);
        $result = $this->request('Retrieve', $params);
        return $result;
    }



}
?>