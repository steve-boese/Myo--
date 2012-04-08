<?php

  if(isset($_POST['login'])) {
    $error = edit_Login();
    if (count($error) == 0) {
      header("Location: ".SITE_HTTP._URI_);
      return;
    }
    else
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
  }

  if($_URL[2] == "logout") {
    do_Logout();
  } else
    login_Form();

  function edit_Login() {
    $error = array();
    $token = explode('.',$_POST['loginToken']);
    if (count($token) == 4) {
      $pwd_hex = array_pop($token);
      $token = implode('.',$token);
      do_query("
        delete from
          staff_login
        where
          token = '$token'");
      if ($GLOBALS['db']->affected_rows == 1)
        $proceed = true;
    }
    if (!isset($proceed))
      return array("Login Failed","Please try again.");
    $_POST['password'] = hex2ascii($pwd_hex);
    if (isset($_SESSION['pw_reset']['username']))
      return set_new_password();

    add_slashes($_POST);
    $result = do_query("
      select
        *
      from
        staff
      where
        username = '{$_POST['username']}' and
        password = '".md5($_POST['password'])."' and
        deleted  =  '".ZERO_DATE."' and
        pw_set   <> '".ZERO_DATE."'");
    if ($result->num_rows <> 1) {
      $error[] = "Incorrect Login";
      $error[] = "Please try again.";
    } else {
      $user = $result->fetch_array();
      do_query("
        update
          staff
        set
          last_login = now(),
          last_ip = inet_aton('{$_SERVER['REMOTE_ADDR']}'),
          total_logins = total_logins + 1
        where
          id = '{$user['id']}'");
      $_SESSION[SITE_PORT]['user_name'] = $user['username'];
      $_SESSION[SITE_PORT]['user_id'] = $user['id'];
      if ($user['type'] > 0)
        $_SESSION[SITE_PORT]['user_type'] = $user['type'];
      if ($user['type'] == '3') {
        $_SESSION[SITE_PORT]['user_pages'] = $user['pages'];
        $_SESSION[SITE_PORT]['user_modules'] = $user['modules'];
        $tags = do_query("
          select
            tag_id
          from
            staff_tagged
          where
            staff_id = '{$user['id']}'");
        while ($tag = $tags->fetch_object())
          $tg[] = $tag->tag_id;
        $_SESSION[SITE_PORT]['user_tags'] = implode(',',$tg);
      }
    }
    return $error;
  }

  function do_Logout() {

    $_SESSION = array();
    header("Location: ".SITE_HTTP."/");

  }

  function set_new_password() {

    if ($_SESSION['pw_reset']['username'] <> $_POST['username'])
      return array("Password reset failed","Please try again");

    if ($_POST['password'] <> $_POST['password_repeat'])
      return array("Passwords do not match","Please try again");

    if (strlen($_POST['password']) < 7)
      return array("Password must be 7 characters","Please try again");
      
    $result = do_query("
      select
        *
      from
        staff
      where
        username = '{$_POST['username']}' and
        pw_key   = '{$_SESSION['pw_reset']['key']}' and
        deleted  =  '".ZERO_DATE."' and
        pw_set   =  '".ZERO_DATE."'");
    if ($result->num_rows <> 1) {
      $error[] = "Password reset failed";
      $error[] = "Please try again.";
    } else {
      $user = $result->fetch_array();
      do_query("
        update
          staff
        set
          password = '".md5($_POST['password'])."',
          pw_set = now(),
          pw_key = '',
          last_login = now(),
          last_ip = inet_aton('{$_SERVER['REMOTE_ADDR']}'),
          total_logins = total_logins + 1
        where
          id = '{$user['id']}'");
      unset($_SESSION['pw_reset']);
      $_SESSION[SITE_PORT]['user_name'] = $user['username'];
      $_SESSION[SITE_PORT]['user_id'] = $user['id'];
      if ($user['type'] > 0)
        $_SESSION[SITE_PORT]['user_type'] = $user['type'];
      if ($user['type'] == '3') {
        $_SESSION[SITE_PORT]['user_pages'] = $user['pages'];
        $_SESSION[SITE_PORT]['user_modules'] = $user['modules'];
        $tags = do_query("
          select
            tag_id
          from
            staff_tagged
          where
            staff_id = '{$user['id']}'");
        while ($tag = $tags->fetch_object())
          $tg[] = $tag->tag_id;
        $_SESSION[SITE_PORT]['user_tags'] = implode(',',$tg);
      }
    }
    return $error;
  }

  function login_Form() {

    $GLOBALS['pageInfo']['title'] = sprintf(
          "Login - %s - %s, %s",
          SITE_NAME,CMS_NAME,CMS_VERSION);

    $token = uniqid(rand(),true).'.'.uniqid(rand());
    do_query("
      insert into
        staff_login
         (token,
          expires)
        values
         ('$token',
          now() + interval 10 minute)");

    if (isset($_SESSION['pw_reset']['username'])) {
      $item['username'] = $_SESSION['pw_reset']['username'];
      $error = array("Please choose a password","at least 7 characters long");
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
    }


    $tbi = 1;
?>

      <form id='login' class='edit_item' method='post' action='<?=_URI_?>'
            onsubmit='return validate_form(this)' >
        <h2><?=SITE_NAME;?><br />Staff Login</h2>
        <div class='field_input'>
          <div>
            <label for="username">Username:</label>
            <input id="username" name="username" type="text" tabindex='<?=$tbi++;?>'
                class="required" onfocus="clear_error(this)" onblur="validate_field(this)"
                style="width:200px;" value="<?=$item['username'];?>" />
          </div>
          <div>
            <label for="password">Password:</label>
            <input id="password" name="password" type="password" tabindex='<?=$tbi++;?>'
                class="required" onfocus="clear_error(this)" onblur="validate_field(this)"
                style="width:180px;" value="" />
          </div>
<?

    if (isset($_SESSION['pw_reset']['username'])) {

?>
          <div>
            <label for="password_repeat">Confirm password:</label>
            <input id="password_repeat" name="password_repeat" type="password" tabindex='<?=$tbi++;?>'
                class="required" onfocus="clear_error(this)" onblur="validate_field(this)"
                style="width:180px;" value="" />
          </div>
<?

    }

?>
        </div>
        <div class='buttons'>
          <input type='hidden' name='loginToken' value='<?=$token;?>' />
          <input type='submit' name='login' value='Log in' tabindex='<?=$tbi++;?>'/>
        </div>
      </form>
<?
  }

?>