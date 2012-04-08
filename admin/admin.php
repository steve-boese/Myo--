<?php

  // for admin pages, verify that user is logged in
  // and has privileges for the requested module.
  
  // when all is good, require it.

  if ($_URL[2] == 'logout' || !isset($_SESSION[SITE_PORT]['user_id']) || !isset($_SESSION[SITE_PORT]['user_type']))
    $the_include = "admin/login.php";
  else if ($_SESSION[SITE_PORT]['user_id'] == '' || $_SESSION[SITE_PORT]['user_type'] == '')
    $the_include = "admin/login.php";
  else if (!isset($_URL[2]) || $_URL[2] == '')
    $the_include = "admin/admin-home.php";
  else {
    if ($_SESSION[SITE_PORT]['user_type'] == 2)
      $where = "master_only = 0 and";
    else if ($_SESSION[SITE_PORT]['user_type'] == 3) {
      if (count(explode(',',$_SESSION[SITE_PORT]['user_modules'])) == 0)
        $where = "id = 0 and";
      else
        $where = "id in ({$_SESSION[SITE_PORT]['user_modules']}) and";
    }
    $modresults = do_query("
      select
        *
      from
        plugin
      where $where
        deleted = '".ZERO_DATE."' and
        tag     = '$_URL[2]' and
        active  = '1'");
    if ($modresults->num_rows <> 1)
      echo "<h1>Module {$_URL[2]} Not Available";
    else {
      $mod = $modresults->fetch_array();
      $the_include = $mod['admin_loc'];
    }
  }
  if (isset($the_include))
    require(ABSPATH . $the_include);

?>