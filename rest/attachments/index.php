<?php
  # http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
  function endsWith($haystack, $needle)
  {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
  }

  # Cf. http://www.media-division.com/the-right-way-to-handle-file-downloads-in-php/

  # Sanitize filename input. Otherwise, user can use relative
  # file paths to access arbitrary files.
  $docid = basename(strtolower($_GET["id"]));
  
  # <<<
  # $doc = $docid . ".pdf";
  # ---
  # Note! On Windows, PHP's FileInfo extension must be enabled 
  # in php.ini. In other OSes, FileInfo is enabled by default.
  # Cf. http://us3.php.net/manual/en/fileinfo.installation.php
  $mime_reader = new finfo(FILEINFO_MIME_TYPE);
  $mime = "";
  $doc_found = false;
  $doc_is_unique = true;
  
  # Find the file with this ID in the current directory, and determine its mime type
  foreach(glob($docid . ".*") as $doc){
    # Override mimereader return, if newer Office doc that PHP doesn't yet know about
    if(endsWith($doc, ".xlsm"))
      $mime = "application/vnd.ms-excel.sheet.macroEnabled.12";
    elseif(endsWith($doc, ".docm"))
      $mime = "application/vnd.ms-word.document.macroEnabled.12";
    else
      $mime = $mime_reader->file($doc);

    if($doc_found)
      $doc_is_unique = false;
    $doc_found = true;
  }
  # Continue only if doc found, and is unique
  if(!$doc_found or !$doc_is_unique){
    # header("Location: error.php");
    $err_page = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=8" />
  </head>
  <body>
    <h2>A Problem Occurred</h2>
    <p>The server could not find the file specified, or was unable to retrieve it. Please notify the webmaster.</p>
  </body>
</html>
HTML;
    echo $err_page;
    exit;
  }
  # >>>
  
  # header("Content-Type: application/octet-stream");
  header("Content-Type: " . $mime);
  header("Content-Disposition: attachment; filename=\"" . $doc . "\"");
  
  # <<<
  # readfile($doc);   # Does not handle large file sizes gracefully
  # ---
  set_time_limit(0);   # potentially dangerous: allows script to run indefinitely
  
  # Chunk the file output to the client
  $f_doc = @fopen($doc,"rb");
  while(!feof($f_doc))
  {
    # Stream 8Mb to the client at a time
    print(@fread($f_doc, 1024*8));
    ob_flush();
    flush();
  }
  # >>>
?>
