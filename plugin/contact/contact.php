<?php

  if (isset($_POST['submit'])) 
    $error = edit_Form();
  
  if (isset($_POST['submit']) && !isset($error)) {
    send_Email();
    $thankyou_msg = (isset($GLOBALS['_TAG']['thankyou'])) ? $GLOBALS['_TAG']['thankyou']
          : "Thank you for contacting us!";
    echo "
      <div class='contact_thankYou'>$thankyou_msg</div>";
  } else {
    if (is_array($error))
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
    contact_Form();
  }
  
function edit_Form() {
  foreach($_POST as &$value) {
    if (is_scalar($value))
      $value = trim($value);
    else if (is_array($value)) {
      foreach ($value as &$val)
        $val = trim($val);
    }
  }
  
  //if ($_POST['name'] == "")              $error[] = "Your name is required";
  //if ($_POST['comments'] == "")          $error[] = "A comment is required";
  //if ($_POST['phone'] == "")             $error[] = "Your phone number is required";
  if ($_POST['email'] == "")             $error[] = "Your email is required";
  elseif (!valid_email($_POST['email'])) $error[] = "{$_POST['email']} is not a valid email";
  
  /*
  if (!isset($error)) {
    require (ABSPATH . "module/captcha/captcha.php");
    $codeChecker = new codeChecker();
    if (!$codeChecker->checkCode($_SESSION[code],$_POST[code]))
      $error[] = "Invalid CAPTCHA code entered; please try again";
  }
  */
  
  return $error;
}

function send_Email() {

  require(ABSPATH . 'xtool/phpmailer/class.phpmailer.php');
  require(ABSPATH . 'xtool/html2text.php');
  
  foreach($_POST as &$value) {
    if (is_scalar($value))
      $value = htmlspecialchars($value);
    else if (is_array($value)) {
      foreach ($value as &$val)
        $val = htmlspecialchars($val);
    }
  }

  $msgTo = retrieve_valid_emails($GLOBALS['_TAG']['emailTo']);

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
        {$_POST['email']} $emailsuffix
      </p>
      <h2>Comments:</h2>
      <p>".nl2br(stripslashes($_POST['comments']))."</p>
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
  foreach ($msgTo as $to)
    $mail->AddAddress($to);
  $mail->AddReplyTo($_POST['email'],$_POST['name']);

  $mail->IsHTML(true);

  $mail->Subject    = "Comment posted at ".SITE_VERSION." ".SITE_NAME." site";
  $mail->Body       = $msgBody;
  $mail->AltBody    = $h2t->get_text();

  if (!$mail->Send())
    doError("Unable to send Contact email",$mail->ErrorInfo);

}

function contact_Form() {

  $scripts   = ($GLOBALS['pageInfo']['scripts'] <> '') ? nl2array($GLOBALS['pageInfo']['scripts']) : array();
  $scripts[] = '/admin/script/site.js';
  $GLOBALS['pageInfo']['scripts'] = implode("\n",$scripts);
  $question  = (isset($GLOBALS['_TAG']['question'])) ? $GLOBALS['_TAG']['question']
             : 'Please let us know how we can help you:';

  $tbi = 1;
?>

      <form id='contact_form' class='edit_item' method='post' action='<?=_URI_;?>' onsubmit='return validate_form(this)' >
        <div class='field_input'>
          <div>
            <label for="name">Your name:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="name" id="name" title="Your name" class="required"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['name']; ?>"/>
          </div>
<?
   /*
          <div>
            <label for="phone">Phone:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="phone" id="phone" title="Your phone" class="required"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['phone']; ?>"/>
          </div>
   */
?>
          <div>
            <label for="email">Email:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="email" id="email" title="Your email" class="required email"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['email']; ?>"/>
          </div>
          <div>
            <label for="comments"><?=$question;?></label>
            <textarea style="width:300px;height:7em;" rows="8" cols="80" tabindex="<?=$tbi++; ?>"
              name="comments" id="comments"><?=$_POST['comments']; ?></textarea>
          </div>
<?
   /*
          <div class='captcha'>
            <img src="/module/captcha/captcha.php?display" alt='' />
          </div>
          <div>
            <label for="code">Letters in the image:
              <a onclick="window.open( '/module/captcha/captcha.php?why', null, ' height=400,width=400,status=yes,toolbar=no,menubar=no,location=no' )">(Why?)</a>
            </label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="code" id="code" title="CAPTCHA code" class="required"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value=""/>
          </div>
   */
?>
        </div>
        <div class='buttons'>
          <input type='submit' name='submit' value='Send Contact Info' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Reset Form' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=_URI_;?>"' />
        </div>

      </form>

<?

}

?>