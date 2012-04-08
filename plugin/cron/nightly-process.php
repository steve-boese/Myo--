<?php

	$root = explode('/',dirname(__FILE__));
  for ($i=1; $i<3; $i++) array_pop($root);
  define( 'ABSPATH', implode('/',$root ) . '/' );

  require(ABSPATH . 'admin/site/config.php');
  require(ABSPATH . 'admin/site/functions.php');
  require(ABSPATH . 'admin/site/connect.php');

  require(ABSPATH . 'module/register/nightly-process.php');


?>