<?/*  Import-01.php
  
      Read the alpha business listing pages at ChamberMaster
      and collect the URIs of individual members in import_link
      table.
  */

  set_time_limit(600);
  echo "<title>{$_SERVER['PHP_SELF']}</title><h1>{$_SERVER['PHP_SELF']}</h1>";
  define( 'ABSPATH', dirname(__FILE__) . '/' );

  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');
  $GLOBALS['db'] = db_connect();

  require("simple_html_dom.php");
  
  $the_group = 1;
  $new_group = $the_group + 1;
  
  do_query("delete from import_link where the_group > $the_group");
  
  $links = do_query("
    select
      *
    from
      import_link
    where
      the_group = $the_group");

  while ($link = $links->fetch_object()) {
    $html = file_get_html($link->link);
    $x = $html->find('table#cm_search_result_list div.cm_member_name a');
    foreach($x as $y) {
      do_query("
        insert into
          import_link
           (the_group,
            from_link_id,
            link)
          values
           ('$new_group',
            '{$link->id}',
            '{$y->href}')");
    }

    $html->clear();
    unset($html);
    sleep(1);
  }

?>