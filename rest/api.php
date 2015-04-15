<?php
  require_once 'ServiceExample.class.php';

  // Requests from the same server don't have a HTTP_ORIGIN header
  if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
      $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
  }

  $service = new ServiceExample($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
  echo $service->processRequest();
?>