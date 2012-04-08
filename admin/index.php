<?php
  //error_reporting(E_ALL);
  define( 'ABSPATH', substr(dirname(__FILE__),0,strlen(dirname(__FILE__)) - 6) . '/' );

  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');
  $GLOBALS['db'] = db_connect();

  if ('http://'.$_SERVER['HTTP_HOST'] <> SITE_HTTP)
    exit("<a href='".SITE_HTTP."/admin/'>".SITE_HTTP."/admin/</a>");

  session_start();
  ob_start();                                  /* "69.24.165.115", chamber */
  
  if (in_array($_SERVER['REMOTE_ADDR'],array("173.17.120.233x","127.0.0.1x"))) {
    echo "<div style='text-align:left;background:#fff;'><pre>".print_r($_SESSION,true).print_r($_SERVER,true)."</pre></div>";
  }

  Check_session();

  $_URI       = explode("?",_URI_);
  $_URL       = explode("/",$_URI[0]);

  $tmplt_result = do_query("
    select
      *
    from
      template t
    where
      t.name = 'Admin' and
      t.deleted = 0");
  if ($tmplt_result->num_rows <> 1)
    exit("Admin template error");

  $GLOBALS['pageInfo'] = $tmplt_result->fetch_array();
  $tmplt_result->close();
  
  if (isset($_URL[2]) && $_URL[2] <> '')
    $GLOBALS['pageInfo']['title'] = ucwords($_URL[2].'s'.TITLE_SUFFIX);

  require_once (ABSPATH . $GLOBALS['pageInfo']['file']);

  $Content = ob_get_contents();
  ob_get_clean();

  $Content = replace_headers($Content);

  echo $Content;

  $GLOBALS['db']->close();

?>