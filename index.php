<?php

  /*
     index.php

     driver for all public functionality.
     
     traffic arrives here
       * for the bare domain name
       * whenever .htaccess triggers a 404/403 redirect, because of
          - friendly page address
          - image, document, stylesheet, etc., no longer exists
  */

  define( 'ABSPATH', dirname(__FILE__) . '/' );

  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');

  // splash page replaces site content with countdown-to-launch or maintenance page
  Check_splash_page();
  
  // connect to database table
  $GLOBALS['db'] = db_connect();

  // trigger session management and output buffering
  session_start();
  ob_start();

  // check to see if a logged-in user has been dormant longer than max to stay logged in
  Check_session();
  
  $_URI       = explode("?",_URI_);
  $_URL       = explode("/",$_URI[0]);
  
  // redirect requests for indexes to the plain domain name
  if (in_array($_URL[1],array("index.php","index.html","index.htm"))) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: '.SITE_HTTP);
    exit;
  }
  if ($_URL[1] == "home_page") $_URL[1] = '.';

  // friendly page names generally don't include periods.
  // pages with links to no-longer-available images or stylesheets can have many such links.
  // so, produce an abreviated 404 response.
  if (strstr($_URI[0],'.') && $_URL[1] != 'email-opt-out')
    {header('HTTP/1.1 404 NOT FOUND');exit('404 Not Found');}

  // if user is not logged in, allow access only to active and draft pages.
  if (!isset($_SESSION['user_type']))
    $pagestatuses = "and p.status in ('active','draft')";
    
  // page & template query
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

  // if the friendly page address was not found, retrieve the 404 content
  //   and set the http header status
  if ($result->num_rows < 1) {
    $result = do_query(sprintf($page_sql,'404-not-found',''));
    header('HTTP/1.1 404 NOT FOUND');
  }
  
  // make the query result available to plugins;
  // append the title suffix from admin/config.php to the page title
  $GLOBALS['pageInfo'] = $result->fetch_array();
  $GLOBALS['pageInfo']['title'] .= TITLE_SUFFIX;

  // execute the template
  require_once (ABSPATH . $GLOBALS['pageInfo']['template_file']);
  
  // retrieve the output buffer
  $Content = ob_get_contents();
  ob_get_clean();
  
  // default stylesheets, scripts and favicon were set in the template
  // plugins may have altered them; incorporate those changes now
  $Content = replace_headers($Content);
  
  // write the collected content
  echo $Content;
  
  $result->close();
  $GLOBALS['db']->close();

?>