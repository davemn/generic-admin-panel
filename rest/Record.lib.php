<?php
  abstract class RecordField {
    const ID           = 'id';
    const FILE_ID      = 'file_id';
    const AREA         = 'area';
    const PROJECT      = 'project';
    const TYPE         = 'type';
    const DATE         = 'date';
    const OWNER        = 'owner';
    const AGENCY       = 'agency';
    const IS_EMERGENCY = 'isEmergency';
    const STATUS       = 'status';
    const IS_PASSING   = 'isPassing';
    const CATEGORY     = 'category';
  }
  
  class RecordIDPool {
    private $curId = 0;
    // $existing is an array of arrays, each of which has an integer 'id' field.
    // e.g.
    // array(
    //   array('id' => 12, ...),
    //   array('id' => 15, ...),
    // )
    // 
    public function __construct($existing){
      if(!is_array($existing))
        throw new Exception('Must provide a valid array of records to create a pool of record IDs.');
    
      $max = 0;
      foreach($existing as $record){
        if($record[RecordField::ID] > $max)
          $max = $record[RecordField::ID];
      }
      $this->curId = $max;
    }
    
    public function next(){
      return ++$this->curId;
    }
  }
    
  class RecordSet {
    private $filename;
    private $data;
    private $idPool;
    
    public function __construct($filename){
      if(!is_string($filename) or strlen($filename) === 0)
        throw new Exception('Must provide a valid filename to read/write.');
      
      $this->filename = $filename;
      
      $raw_data = file_get_contents($this->filename);
      if($raw_data === false)
        throw new Exception('Could not read file!');
      
      $this->data = json_decode($raw_data, true);   // parse into an array
      $this->idPool = new RecordIDPool($this->data);
    }
    
    private function buildID($record){
      return $this->idPool->next();
    }
  
    private function contains($record){
      if(count($this->data) === 0)
        return false;
      
      // Try to find a record with the same ID
      foreach($this->data as $curRecord){
        if($curRecord[RecordField::ID] === $record[RecordField::ID])
          return true;
      }
      return false;
    }
    
    private function indexOf($id){
      if(!is_int($id))
        throw new Exception('Must provide integer ID to locate a record.');
      
      // Find the record
      $foundIdx = -1;
      
      foreach($this->data as $curIdx => $curRecord){
        if($curRecord[RecordField::ID] === $id){
          $foundIdx = $curIdx;
          break;
        }
      }
      return $foundIdx;
    }
    
    public function add($record){
      $record[RecordField::ID] = $this->buildID($record);
      
      array_push($this->data, $record);
      return $record[RecordField::ID];
    }
    
    public function delete($id){
      if(!is_int($id))
        throw new Exception('Must provide integer ID to delete record from file.');
        
      if(!$this->contains(array(RecordField::ID => $id)))
        return null;
        
      // Find the record
      $foundIdx = $this->indexOf($id);
      $deleted = $this->data[$foundIdx];

      // Delete the record
      array_splice($this->data, $foundIdx, 1);
      
      return $deleted;
    }
    
    public function update($id, $record){
      // If the record to update isn't found, default to an ADD operation
      if(!$this->contains(array(RecordField::ID => $id))){
        return $this->add($record);
      }
    
      // Find the old record, if any, and merge in the new record
      $foundIdx = $this->indexOf($id);
      $this->data[$foundIdx] = array_merge($this->data[$foundIdx], $record);
      
      // According to the Backbone.js documentation, "when returning a JSON 
      // response, send down the attributes of the model that have been 
      // *changed* by the server". Here we're returning the entire model
      // object instead, for simplicity.
      return $this->data[$foundIdx];
    }
    
    public function get($id){
      if(!is_int($id))
        throw new Exception('Must provide integer ID to retrieve record from file.');
        
      if(!$this->contains(array(RecordField::ID => $id)))
        return null;
        
      // Find the record
      $foundIdx = $this->indexOf($id);
      return $this->data[$foundIdx];
    }
        
    function save(){
      file_put_contents($this->filename, json_encode($this->data, JSON_PRETTY_PRINT));
    }
  }
?>