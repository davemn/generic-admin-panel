<?php
  require_once 'Service.interface.php';
  require_once 'Record.lib.php';
  
  class ServiceExample extends Service {
    // protected $User;
    private $records;

    public function __construct($request, $origin) {
        parent::__construct($request);

        // // Abstracted out for example
        // $APIKey = new Models\APIKey();
        // $User = new Models\User();
        // 
        // if (!array_key_exists('apiKey', $this->request)) {
        //     throw new Exception('No API Key provided');
        // } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
        //     throw new Exception('Invalid API Key');
        // } else if (array_key_exists('token', $this->request) &&
        //      !$User->get('token', $this->request['token'])) {
        // 
        //     throw new Exception('Invalid User Token');
        // }
        // 
        // $this->User = $User;
        
        $this->records = new RecordSet('data.json');
    }
    
    // /**
    //  * Example of an Endpoint
    //  */
    // protected function example() {
    //   if ($this->method == 'GET') {
    //       return "Your name is " . $this->User->name;
    //   } else {
    //       return "Only accepts GET requests";
    //   }
    // } 
    
    private function change_request_post(){
      if(count($this->request) === 0)
        throw new Exception('Must provide at least 1 field in order to create a record.');
        
      // Handle file attachments, if any
      
      $attachmentName = 'attachment';   // name of the HTML form control associated with the file upload
      if(array_key_exists($attachmentName, $this->files)){   // otherwise, no file attachment given
        if($this->files[$attachmentName]['error'] > 0)
          throw new Exception('An error occurred while trying to upload the attached file associated with the record.');

        $name_parts = pathinfo($this->files[$attachmentName]['name']);
        $basename   = $name_parts['filename'];
        $extension  = $name_parts['extension'];
        
        // if($extension !== 'pdf'){
        //   ...
        // }
        
        $canonical_name = md5_file($this->files[$attachmentName]['tmp_name']);
        if($canonical_name === false)
          throw new Exception('Unable to compute a hash for the attached file.');
        
        $this->request['file_id'] = $canonical_name;
        
        $moveResult = move_uploaded_file($this->files[$attachmentName]['tmp_name'], 'attachments/' . $canonical_name . '.' . $extension);
        if($moveResult === false)
          throw new Exception('Unable to move attached file to permanent storage.');
      }
        
      $id = $this->records->add($this->request);
      $this->records->save();
      
      $changed = array('id' => $id);
      // From the Backbone documentation: "When returning a JSON response, send 
      // down the attributes of the model that have been changed by the server, 
      // and need to be updated on the client."
      if(array_key_exists('file_id', $this->request) and strlen($this->request['file_id']) !== 0)
        $changed['file_id'] = $this->request['file_id'];
        
      return $changed;
    }
    
    private function change_request_get(){
      if(count($this->args) === 0)
        throw new Exception('Must provide a resource ID to retrieve.');
            
      $reqId = $this->validRecordID($this->args[0]);
      if($reqId === null)
        throw new Exception('Resource ID not a valid identifier.');
      
      $record = $this->records->get($reqId);
      if($record === null)
        throw new Exception('No record found with given ID.');
        
      return $record;
    }
    
    // Not sure which attributes Backbone expects back on a DELETE ...
    // We'll return the entire deleted object.
    private function change_request_delete(){
      if(count($this->args) === 0)
        throw new Exception('Must provide a resource ID to delete.');
        
      $reqId = $this->validRecordID($this->args[0]);
      if($reqId === null)
        throw new Exception('Resource ID not a valid identifier.');
        
      $record = $this->records->delete($reqId);
      if($record === null)
        throw new Exception('No record found with given ID.');
      
      $this->records->save();
      return $record;
    }
    
    private function change_request_update(){
      if(count($this->args) === 0)
        throw new Exception('Must provide the ID of the record to update.');
      
      $reqId = $this->validRecordID($this->args[0]);
      if($reqId === null)
        throw new Exception('Resource ID not a valid identifier.');
      
      // Remove any ID in the record itself. RecordSet::update() defaults to an 
      // 'add' operation if a record with the given ID isn't found. 'add' generates
      // an ID, so scrub the one given (if any).
      if(array_key_exists('id', $this->request))
        unset($this->request['id']);
      
      if(count($this->request) === 0)
        throw new Exception('Must provide at least 1 field in order to update a record.');
        
      // Handle file attachments, if any
      
      $attachmentName = 'attachment';   // name of the HTML form control associated with the file upload
      if(array_key_exists($attachmentName, $this->files)){   // otherwise, no file attachment given
        if($this->files[$attachmentName]['error'] > 0)
          throw new Exception('An error occurred while trying to upload the attached file associated with the record.');

        $name_parts = pathinfo($this->files[$attachmentName]['name']);
        $basename   = $name_parts['filename'];
        $extension  = $name_parts['extension'];
        
        // if($extension !== 'pdf'){
        //   ...
        // }
        
        $canonical_name = md5_file($this->files[$attachmentName]['tmp_name']);
        if($canonical_name === false)
          throw new Exception('Unable to compute a hash for the attached file.');
          
        // A file is attached, and differs from the previously attached file, if any
        // Lets us avoid further (slow) file operations if the file hasn't changed.
        if(!array_key_exists('file_id', $this->request) or $this->request['file_id'] !== $canonical_name){
          $this->request['file_id'] = $canonical_name;
          
          // If the destination file already exists, it will be overwritten. 
          // Since updates are done via PUT, and our web service handles files over PUT outside of PHP's 
          // file upload mechanism, we can't use move_uploaded_file.
          $moveResult = copy($this->files[$attachmentName]['tmp_name'], "attachments/$canonical_name.$extension");
          if($moveResult === false)
            throw new Exception('Unable to move attached file to permanent storage.');
        }
      }
        
      $updated = $this->records->update($reqId, $this->request);
      $this->records->save();
      return $updated;
    }
    
    protected function change_request(){
      // $this->request['...']
      // $this->method
      // $this->verb
      // $this->args['...']
      
      if(strlen($this->verb) !== 0)
        throw new Exception('Verbs not supported on this resource.');
      
      switch($this->method) {
      case 'GET':
        return $this->change_request_get();
        break;
      case 'POST':
        return $this->change_request_post();
        break;
      case 'DELETE':
        return $this->change_request_delete();
        break;
      case 'PUT':
        return $this->change_request_update();
        break;
      default:
        throw new Exception("This endpoint does not accept $this->method requests.");
      }
    }
  }
?>