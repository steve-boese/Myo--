<?/*  Import-02.php

      Read the URIs of individual business listing pages at
      ChamberMaster and collect the raw HTML of each one in
      import_member table.
  */

  set_time_limit(600);
  echo "<title>{$_SERVER['PHP_SELF']}</title><h1>{$_SERVER['PHP_SELF']}</h1>";
  define( 'ABSPATH', dirname(__FILE__) . '/' );

  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');
  $GLOBALS['db'] = db_connect();

  require("simple_html_dom.php");

  $the_group = 2;

  do_query("truncate table import_member");

  $links = do_query("
    select
      *
    from
      import_link
    where
      the_group = $the_group
    order by
      id");

  while ($link = $links->fetch_object()) {
    $html = file_get_html($link->link);
    $html_content = add_slashes(array_pop($html->find('div.cm_main'))->innertext);
    do_query("
        insert into
          import_member
           (link_id,
            html_raw)
          values
           ('{$link->id}',
            '$html_content')");

    $html->clear();
    unset($html);
    sleep(1);
  }

?>