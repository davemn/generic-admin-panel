<?php
  function validAlphaNumeric($field){
    if(!is_string($field) and !is_int($field))
      return null;
    
    if(is_int($field))
      return strval($field);

    // // Convert any strange Microsoft chars to their UTF-8 counterparts.
    // // E.g. left double quote => ", etc.
    // $microsoftChars = array('‘', 
    //                         '’',
    //                         '“', 
    //                         '”'); 
    // $unicodeChars = array("'", 
    //                       "'", 
    //                       '"', 
    //                       '"'); 
    // 
    // $validated_field = str_replace($microsoftChars, $unicodeChars, $field);
    
    $validated_field = trim($field);
    $validated_field = preg_replace('/\s+/', ' ', $validated_field); // replace multiple consec. space-alike chars with single ' '
    
    // $validated_field = preg_replace('/"/', '\'', $validated_field);  // replace double quotes with single quote
    
    // $validated_field = preg_replace("/\\\\u201(c|d|8|9)/", "'", $validated_field); // replace unicode-escaped double quotes with single quote
    // $validated_field = preg_replace("/\\\\u201(c|d|8|9)/", "'", $validated_field); // replace unicode-escaped double quotes with single quote
    // $validated_field = preg_replace("/[‘’“”]/u", "'", $validated_field); // replace unicode-escaped double quotes with single quote
    // $validated_field = preg_replace("/[\u201c\u201d]/u", "'", $validated_field); // replace unicode-escaped double quotes with single quote
    
    $validated_field = preg_replace("/[^A-Za-z0-9 _.'\/-]/", "", $validated_field);
    
    if(strlen($validated_field) === 0)
      return null;

    return $validated_field;
  }

  $json = null;
  $outFile = null;
  
  try {
    $cliOpts = getopt('f:o:');
    if($cliOpts === false)
      throw new Exception('Unable to parse command-line options!');
    
    if(!array_key_exists('f', $cliOpts))
      throw new Exception('Bad arguments given! Expects -f, followed by filename.');
    if(!array_key_exists('o', $cliOpts))
      throw new Exception('Bad arguments given! Expects -o, followed by output filename.');
      
    $json = file_get_contents($cliOpts['f']);
    if($json === false)
      throw new Exception('Unable to read file!');
    
    $outFile = fopen($cliOpts['o'], 'w');
    if($outFile === false)
      throw new Exception('Unable to open file for writing!');
  }
  catch(Exception $e){
    print $e->getMessage() . PHP_EOL;
    exit(0);    
  }
  
  $data = json_decode($json, true);
  
  // Build list of fields
  $cols = array();
  foreach($data as $record){
    // foreach($record as $key => $val){
    foreach(array_keys($record) as $key){
      if(!array_key_exists($key, $cols))
        $cols[$key] = '';
    }
  }
  // Write a header
  fputcsv($outFile, array_keys($cols));
  
  $normalizedRecord = null;
  
  foreach($data as $record){
    $normalizedRecord = $cols;
    // foreach(array_keys($cols) as $col){
    //   $normalizedRecord[$col] = $record[$col]
    // }
    foreach($record as $col => $val){
      $normalizedVal = validAlphaNumeric($val);
      if($normalizedVal === null)
        $normalizedRecord[$col] = '';
      else
        $normalizedRecord[$col] = $normalizedVal;
    }
    
    fputcsv($outFile, $normalizedRecord);
  }
  
  fclose($outFile);
?>