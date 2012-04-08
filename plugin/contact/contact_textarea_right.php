<?php

  /*  Contact form with table-based layout & textarea box on the right */

  if (isset($_POST['reset']))
    $_POST = null;
  
  if (isset($_POST['submit'])) {
    $error = edit_Contact();
  }
  
  if (isset($_POST['submit']) && !isset($error)) {
    send_Contact();
    echo "
      <div class='contact_thankYou'>Thank you for contacting us!</div>";
  } else {
    if (is_array($error))
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
    contact_Form();
  }
  
function edit_Contact() {
  foreach($_POST as &$value) $value = trim($value);
  
  if ($_POST['name'] == "")              $error[] = "Your name is required";
  if ($_POST['comment'] == "")           $error[] = "A comment is required";
  if ($_POST['phone'] == "")             $error[] = "Your phone number is required";
  if ($_POST['email'] == "")             $error[] = "Your email is required";
  elseif (!valid_email($_POST['email'])) $error[] = "{$_POST['email']} is not a valid email";
  
  if (!isset($error)) {
    require (ABSPATH . "module/captcha/captcha.php");
    $codeChecker = new codeChecker();
    if (!$codeChecker->checkCode($_SESSION[code],$_POST[code]))
      $error[] = "Invalid CAPTCHA code entered; please try again";
  }
  
  return $error;
}

function send_Contact() {

  require(ABSPATH . 'admin/site/phpmailer/class.phpmailer.php');
  require(ABSPATH . 'admin/site/html2text.php');
  
  global $_TAG;
  $msgTo = valid_email($_TAG['emailTo']) or SITE_EMAIL_TO;

  $domain = explode('@',$_POST['email']);
  $domain = $domain[1];
  if (gethostbyname($domain) == $domain)
    $emailsuffix = ' <em>(Email domain is questionable)</em>';

  $msgBody = "
<html>
  <body>
    <div style='text-align:left'>
      <h2>Contact Information</h2>
      <p>
        {$_POST['name']}<br />
        {$_POST['phone']}<br />
        {$_POST['email']} $emailsuffix
      </p>
      <h2>Comments:</h2>
      <p>".nl2br(htmlentities(stripslashes($_POST['comment'])))."</p>
      <h2>Source:</h2>
      <p>
        Page: {$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}<br>
        Referer: {$_SERVER['HTTP_REFERER']}<br>
        IP Address: {$_SERVER['REMOTE_ADDR']}<br>
      </p>
    </div>
  </body>
</html>";

  $h2t =& new html2text($msgBody);

  $mail = new PHPMailer();

  $mail->IsSendmail();

  $mail->From       = SITE_EMAIL_FROM;
  $mail->FromName   = $_POST['name'];
  $mail->AddAddress($msgTo);
  $mail->AddReplyTo($_POST['email'],$_POST['name']);

  $mail->IsHTML(true);

  $mail->Subject    = "Comment posted at ".SITE_VERSION." ".SITE_NAME." site";
  $mail->Body       = $msgBody;
  $mail->AltBody    = $h2t->get_text();

  if (!$mail->Send())
    doError("Unable to send Contact email",$mail->ErrorInfo);

}

function contact_Form() {

  $tbi = 1;
?>

  <form id='contact_form' method='post' action='<?=_URI_; ?>' onsubmit='return validate_form(this)' >
    <table>
      <tr>
        <td class='left'>
          Name:<br />
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="name" title="Your name" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['name']; ?>"/>
        </td>
        <td rowspan='3' class='right'>
          Comment(s):<br />
          <textarea rows="8" cols="35" style="width:100%; height:100%;"
            name="comment" title="Question or comment" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)" tabindex="<?=$tbi++; ?>"
            ><?=stripslashes($_POST['comment']); ?></textarea>
        </td>
      </tr>
      <tr>
        <td>
          Phone:<br />
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="phone" title="Your phone number" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['phone']; ?>"/>
        </td>
      </tr>
      <tr>
        <td>
          Email:<br />
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="email" title="Your email address" class="required email"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value="<?=$_POST['email']; ?>"/>
        </td>
      </tr>
      <tr>
        <td>
          Letters in the image:
            <a onclick="window.open( '/module/captcha/captcha.php?why', null, ' height=400,width=400,status=yes,toolbar=no,menubar=no,location=no' )">(Why?)</a><br />
          <input type="text" style="width:100%;" tabindex="<?=$tbi++; ?>"
            name="code" title="CAPTCHA code" class="required"
            onfocus="clear_error(this)" onblur="validate_field(this)"
            value=""/>
        </td>
        <td>
          <img src="/module/captcha/captcha.php?display" alt='' />
        </td>
      </tr>
      <tr>
        <td colspan="2" align="center">
          <input type="submit" name="submit" tabindex="<?=$tbi++; ?>" value='Send'/>
          <a class='btn' href='/contact-us/'>Cancel</a>
        </td>
      </tr>
    </table>
  </form>

<?

}

?>