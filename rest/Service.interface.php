<?php
  /** 
   * Originally written (and copyrighted?) by Corey Maynard.
   * Modified by David Mann with validations, HTTP file upload handling.
   */
  abstract class Service {
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';
    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';
    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();
    
    /**
     * Property: files
     * An array of information about POSTed files, indexed by HTML form control name.
     * Should be either an empty array (in the case of HTTP methods that don't support 
     * file uploads), or an array of arrays with structure matching PHP's $_FILES
     * superglobal. See: "http://php.net/manual/en/features.file-upload.post-method.php".
     */
    protected $files = array();
    
    final protected function validAlphaNumeric($field){
      if(!is_string($field))
        return null;

      $validated_field = $field;
      $validated_field = trim($validated_field);
      $validated_field = preg_replace("/[^A-Za-z0-9]/", "", $validated_field);
      if(strlen($validated_field) === 0)
        return null;

      return strtoupper($validated_field);
    }
     
    final protected function validMD5Hash($field){
      if(!is_string($field))
        return null;

      $validated_field = $field;
      $validated_field = trim($validated_field);
      $validated_field = preg_replace("/[^A-Fa-f0-9]/", "", $validated_field);
      if(strlen($validated_field) === 0)
        return null;
      if(strlen($validated_field) !== 32)
        return null;

      return strtoupper($validated_field);    
    }
    
    final protected function validRecordID($field){
      if(!is_int($field) and !is_string($field))
        return null;
        
      $validated_field = $field;
      
      if(is_string($field))
        $validated_field = intval($field);
        
      if($validated_field < 1)
        return null;
        
      return $validated_field;
    }

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    protected function __construct($request) {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");
        
        // - These are the actual fields declared in this class. ---
        // 
        // $method   = '';      // The HTTP method this request was made in, either GET, POST, PUT or DELETE
        // $endpoint = '';      // The Model requested in the URI. eg: /files
        // $verb     = '';      // An optional additional descriptor about the endpoint
        // $args     = Array(); // Any additional URI components after the endpoint and verb have been removed, in our case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1> or /<endpoint>/<arg0>
        
        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);
        if (array_key_exists(0, $this->args) && !$this->validRecordID($this->args[0])) {
            $this->verb = array_shift($this->args);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method) {
        case 'POST':
            // Proceed as normal if fields are posted as application/x-www-form-urlencoded.
            // Otherwise, read & parse the raw request body as a JSON string.
            // backbone.js POSTs send data as a non- URL-encoded JSON payload, 
            // so parsing the raw request body is most appropriate for backbone 
            // POST requests.
            
            $isJsonSubmit = strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;
            $isMultipartSubmit = strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === 0;
            
            // Standard form submit
            if(!array_key_exists('CONTENT_TYPE', $_SERVER) or (!$isJsonSubmit and !$isMultipartSubmit)){
              $this->request = $this->_cleanInputs($_POST);
              break;
            }
            // JSON form submit
            if($isJsonSubmit){
              $reqPayload = file_get_contents('php://input');
              $this->request = json_decode($reqPayload, true);
              break;
            }
            // JSON form submit + file attachment
            if($isMultipartSubmit){
              // Form fields
              $payloadName = '_payload_';
              if(!array_key_exists($payloadName, $_FILES) or $_FILES[$payloadName]['error'] > 0) {
                $this->request = array();   // No form fields submitted, or error occurred
              }
              else {
                $reqPayload = file_get_contents($_FILES[$payloadName]['tmp_name']);
                if($reqPayload === false)
                  $this->request = array();   // Unable to read submitted form fields
                else
                  $this->request = json_decode($reqPayload, true);
              }
                
              // File attachment(s)                
              $rawFiles = array();
              foreach($_FILES as $partName => $part){
                if($partName === '_payload_')
                  continue;
                if(!is_uploaded_file($_FILES[$partName]['tmp_name']))
                  continue;
                $rawFiles[$partName] = $part;
              }
              
              // Only set "files" if the user actually uploaded a file
              if(count($rawFiles) !== 0)
                $this->files = $rawFiles;
            }
            break;
        case 'PUT':
            // PUT is meant to be a barebones method. PHP does no parsing on the content 
            // body, as the body is assumed to be raw data of a single MIME type.
            
            $this->request = array();
            
            $isJsonSubmit = strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;
            if(!$isJsonSubmit)   // only accept JSON-formatted PUT requests
              break;
            
            // Form fields
            $reqPayload = file_get_contents('php://input');
            if($reqPayload === false){
              // Unable to read submitted form fields (including any attached files)
              break;
            }
            
            $req = json_decode($reqPayload, true);
            $reqFile = null;            
            foreach($req as $ctlName => $ctlVal){
              if($ctlName === 'attachment')
                $reqFile = $ctlVal;
              else
                $this->request[$ctlName] = $ctlVal;
            }
            
            // File attachment
            if($reqFile === null)   // No attachment given
              break;
            
            $rawAttachment = explode(',', $reqFile['dataurl']);
            $attachment = base64_decode($rawAttachment[1]);  // actual binary data of attached file

            $name_parts = pathinfo($reqFile['name']);
            $basename   = $name_parts['filename'];
            $extension  = $name_parts['extension'];
          
            // $canonical_name = md5($attachment);
            // 
            // // file_put_contents('filename', $attachment);
            // 
            // echo "Original Name := $basename.$extension" . PHP_EOL;
            // echo "Computed Name := $canonical_name.$extension" . PHP_EOL;
            
            $tmpName = '';
            $tmp = tmpfile(); // "The file is automatically removed when closed"
            if($tmp === false)
              break;

            $isWriteSuccess = fwrite($tmp, $attachment);
            if($isWriteSuccess === false)
              break;
            
            $tmpMeta = stream_get_meta_data($tmp); // get path of temporary file
            $tmpName = $tmpMeta['uri'];
            
            $this->files['attachment'] = array(   // emulate the structure of the $_FILES superglobal
              'name' => $reqFile['name'],
              'type' => $reqFile['type'],
              'size' => strlen($attachment),
              'tmp_name' => $tmpName,
              'tmp_handle' => $tmp, // Prevent premature removal of temporary file by keeping a reference around
              'error' => UPLOAD_ERR_OK
            );

            break;
        case 'GET':
            $this->request = $this->_cleanInputs($_GET);
            break;
        case 'DELETE':
            // No action necessary; DELETE requests only need to parse $this->args.
            break;
        default:
            $this->_response('Invalid Method', 405);
            break;
        }
    }
    
    public function processRequest() {
        if ((int)method_exists($this, $this->endpoint) > 0) {
            try {
                return $this->_response($this->{$this->endpoint}($this->args));
            } catch (Exception $e) {
                return $this->_response(
                    array('error' => $e->getMessage()), 
                    500);
            }
        }
        return $this->_response("No Endpoint: $this->endpoint", 404);
    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code) {
        $status = array(  
            200 => 'OK',
            404 => 'Not Found',   
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ); 
        return ($status[$code])?$status[$code]:$status[500]; 
    }
  }
?>