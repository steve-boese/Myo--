<?

  global $_URL;
  
  if (!isset($_URL[2]) || $_URL[2] == "")
    echo "(email address not specified)";
  else if (!valid_email($_URL[2]))
    echo "({$_URL[2]} is not a valid address)";
  else {
    $address = do_query("
      select
        email
      from
        mailing_list
      where
        opted_out = 0 and
        deleted = 0 and
        email = '{$_URL[2]}'");
    if (mysql_num_rows($address) == 0)
      echo "({$_URL[2]} is not on our mailing list)";
    else {
      do_query ("
        update
          mailing_list
        set
          opted_out = now(),
          opted_out_ip = inet_aton('{$_SERVER['REMOTE_ADDR']}')
        where
          email = '{$_URL[2]}'");
      echo $_URL[2];
    }
  }
  
?>