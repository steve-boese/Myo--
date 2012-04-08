<?php

  if (isset($_POST['reset']))
    $_POST = null;

  if (isset($_POST['register']))
    $error = edit_Register();
    
  if (isset($_POST['register']) && !isset($error)) {
    add_Registration();
    echo "
      <div class='contact_thankYou'>Thank you for registering with us!</div>";

  } else {
    if (is_array($error))
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
    register_Form();
  }

function edit_Register() {
  foreach($_POST as &$value) $value = trim($value);
  if ($_POST['email'] != "") {
    $result = do_query("
      select
        id,
        email
      from
        mailing_list
      where
        email = '{$_POST[email]}' and
        deleted = 0 and
        opted_out = 0");
    if (mysql_num_rows($result) > 0)
      $error[] = "{$_POST['email']} is already registered!";
  }

  if (!isset($error)) {
    if ($_POST['name'] == "")              $error[] = "Your name is required";
    if ($_POST['email'] == "")             $error[] = "Your email is required";
    elseif (!valid_email($_POST['email'])) $error[] = "{$_POST['email']} is not a valid email";
    if ($_POST['address'] == "")           $error[] = "Address is required";
    if ($_POST['city'] == "")              $error[] = "City is required";
    if ($_POST['state'] == "")             $error[] = "State is required";
    if ($_POST['zip'] == "")               $error[] = "Zip is required";
  }

  if (!isset($error)) {
    require (ABSPATH . "module/captcha/captcha.php");
    $codeChecker = new codeChecker();
    if (!$codeChecker->checkCode($_SESSION[code],$_POST[code]))
      $error[] = "Invalid CAPTCHA code entered; please try again";
  }
  
  return $error;
}

function add_Registration() {

  do_query("
    insert
      into mailing_list
       (name,
        email,
        address,
        city,
        state,
        zip,
        phone,
        register_ip,
        created)
      values
       ('$_POST[name]',
        '$_POST[email]',
        '$_POST[address]',
        '$_POST[city]',
        '$_POST[state]',
        '$_POST[zip]',
        '$_POST[phone]',
        inet_aton('{$_SERVER['REMOTE_ADDR']}'),
        now())");

}

function register_Form() {

  $tbi = 1;

?>

  <form id='register_form' method='post' action='<?=_URI_; ?>' onsubmit='return validate_form(this)' >
    <table>
      <tr>
        <td class='left'>
          &nbsp;
        </td>
        <th class='right'>
          All fields are required:
        </th>
      </tr>
      <tr>
        <th>
          Name:
        </th>
        <td>
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="name" title="Your name" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['name']; ?>"/>
        </td>
      </tr>
      <tr>
        <th>
          Email:
        </th>
        <td>
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="email" title="Your email address" class="required email"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['email']; ?>"/>
        </td>
      </tr>
      <tr>
        <th>
          Address:
        </th>
        <td>
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="address" title="Your address" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['address']; ?>"/>
        </td>
      </tr>
      <tr>
        <th>
          City, State, Zip:
        </th>
        <td>
          <input type="text" style="width:55%;" tabindex="<?=$tbi++; ?>"
            name="city" title="City" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['city']; ?>"/>
          <input type="text" style="width:10%;" tabindex="<?=$tbi++; ?>"
            name="state" title="State" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['state']; ?>"/>
          <input type="text" style="width:25%;" tabindex="<?=$tbi++; ?>"
            name="zip" title="Zip code" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['zip']; ?>"/>
        </td>
      </tr>
      <tr>
        <td>
          &nbsp;
        </td>
        <td>
          <img src="/module/captcha/captcha.php?display" alt='' />
        </td>
      </tr>
      <tr>
        <th>
          Letters in the image:
            <a onclick="window.open( '/module/captcha/captcha.php?why', null, ' height=400,width=400,status=yes,toolbar=no,menubar=no,location=no' )">(Why?)</a>
        </th>
        <td>
          <input type="text" style="width:50%;" tabindex="<?=$tbi++; ?>"
            name="code" title="CAPTCHA code" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value=""/>
        </td>
      </tr>
      <tr>
        <td>
          &nbsp;
        </td>
        <td>
          <input type="submit" name="register" tabindex="<?=$tbi++; ?>" value='Register'/> &nbsp; &nbsp;
          <input type='reset'  name='reset'  value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=_URI_;?>"' />
        </td>
      </tr>
    </table>
  </form>

<?

}

?>