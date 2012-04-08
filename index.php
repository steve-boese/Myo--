<?php

  define( 'ABSPATH', dirname(__FILE__) . '/' );
  
  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');
  Check_splash_page();
  $GLOBALS['db'] = db_connect();

  session_start();
  ob_start();

  Check_session();
  
  $_URI       = explode("?",_URI_);
  $_URL       = explode("/",$_URI[0]);
  if (in_array($_URL[1],array("index.php","index.html","index.htm"))) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: '.SITE_HTTP);
    exit;
  }
  if ($_URL[1] == "home_page") $_URL[1] = '.';

  if (strstr($_URI[0],'.') && $_URL[1] != 'email-opt-out')
    {header('HTTP/1.1 404 NOT FOUND');exit('404 Not Found');}

  if (!isset($_SESSION['user_type']))
    $pagestatuses = "and p.status in ('active','draft')";
  $page_sql = "
    select
      p.*,
      p.title as page_title,
      s.title as section_title,
      ifnull(t.file,d.file) as template_file,
      ifnull(t.favicon,d.favicon) as favicon,
      ifnull(t.stylesheets,d.stylesheets) as stylesheets,
      ifnull(t.scripts,d.scripts) as scripts
    from
      page p
    left join
      page s
        on p.sub_id = s.id
    left join
      template t
        on p.template = t.id
    join
      template d
        on d.is_default = 1
    where
      p.file_name = '%s' and
      p.deleted = 0 %s
    order by
      display_order";

  if ($_URL[1] == "") $_URL[1] = ".";
  
  $result = do_query(sprintf($page_sql,$_URL[1],$pagestatuses));

  if ($result->num_rows < 1) {
    $result = do_query(sprintf($page_sql,'404-not-found',''));
    header('HTTP/1.1 404 NOT FOUND');
  }
  $GLOBALS['pageInfo'] = $result->fetch_array();
  $GLOBALS['pageInfo']['title'] .= TITLE_SUFFIX;

  require_once (ABSPATH . $GLOBALS['pageInfo']['template_file']);
  
  $Content = ob_get_contents();
  ob_get_clean();
  
  $Content = replace_headers($Content);
  
  echo $Content;
  
  $result->close();
  $GLOBALS['db']->close();

?>