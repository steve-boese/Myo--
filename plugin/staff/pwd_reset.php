<?

  global $_URL;

  if (count($_SESSION) > 0)
    $_SESSION = array();

  $users = do_query("
    select
      *
    from
      staff
    where
      pw_key = '{$_URL[2]}' and
      deleted = '".ZERO_DATE."'");
      
  if ($users->num_rows <> 1)
    exit("<p>Sorry, but an error has occurred.<br />Please contact ".SITE_NAME."</p>");

  $user = $users->fetch_object();
  
  $_SESSION['pw_reset']['username'] = $user->username;
  $_SESSION['pw_reset']['key'] = $_URL[2];
  
  header("Location: /admin/");

?>