<?php

  if (isset($_POST['submit']))
    $error = edit_Form();

  if (isset($_POST['submit']) && !isset($error)) {
    send_Email();
    $thankyou_msg = (isset($GLOBALS['_TAG']['thankyou'])) ? $GLOBALS['_TAG']['thankyou']
          : "Thank you for requesting your refill(s) online!";
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

  if ($_POST['name'] == "")              $error[] = "Your name is required";
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

function send_Email() {

  require(ABSPATH . 'admin/site/phpmailer/class.phpmailer.php');
  require(ABSPATH . 'admin/site/html2text.php');

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
    
  $script_list_code = "
        <li>%s</li>";
  foreach ($_POST['script'] as $script) {
    if ($script != '')
      $script_list .= sprintf($script_list_code,$script);
  }

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
      <h2>When Needed</h2>
      <p>
        {$_POST['when_needed']}
      </p>
      <h2>
        Prescription Refills Requested
      </h2>
      <ol>$script_list
      </ol>
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
  $mail->FromName   = 'Refill Order';
  foreach ($msgTo as $to)
    $mail->AddAddress($to);
  $mail->AddReplyTo($_POST['email'],$_POST['name']);

  $mail->IsHTML(true);

  $mail->Subject    = "Refill ordered by {$_POST['name']}";
  $mail->Body       = $msgBody;
  $mail->AltBody    = $h2t->get_text();

  if (!$mail->Send())
    doError("Unable to send Refill order",$mail->ErrorInfo);

}

function contact_Form() {

  if (isset($GLOBALS['_TAG']['options']))
    $delivery_vals = explode(',',$GLOBALS['_TAG']['options']);
  else
    $delivery_vals = array(
        'Pick up at store today',
        'Pick up at store in 1 day',
        'Pick up at store in 2+ days');
  $delivery_code = "
      <option value='%s'>%s</option>";
  foreach ($delivery_vals as $val)
    $delivery_opts .= sprintf($delivery_code,$val,$val);
    
  $script_input_code = "
          <div>
            <label for=\"script%d\">%d:</label>
            <input name=\"script[]\" id=\"script%d\" value=\"%s\"
              type=\"text\" style=\"width:200px;\" tabindex=\"%d\" /><br />
          </div>";

  $question = (isset($GLOBALS['_TAG']['question'])) ? $GLOBALS['_TAG']['question']
            : 'Please include any comments about your prescription refill:';

  $tbi = 1;
?>

      <form id='request_refill' class='edit_item' method='post' action='<?=_URI_;?>' onsubmit='return validate_form(this)' >
        <div class='field_input'>
          <div>
            <label for="when_needed">When do you need your refill(s)?</label>
            <select id="when_needed" name="when_needed" tabindex='<?=$tbi++;?>'
              style="width:200px;"><?=$delivery_opts;?>
              
            </select>
          </div>
          <div>
            <label for="name">Your name:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="name" id="name" title="Your name" class="required"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['name']; ?>"/>
          </div>
          <div>
            <label for="phone">Your phone number:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="phone" id="phone" title="Your phone number" class="required"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['phone']; ?>"/>
          </div>
          <div>
            <label for="email">Your email address:</label>
            <input type="text" style="width:200px;" tabindex="<?=$tbi++; ?>"
              name="email" id="email" title="Your email address" class="required email"
              onfocus="clear_error(this)" onblur="validate_field(this)"
              value="<?=$_POST['email']; ?>"/>
          </div>
          <div>
            <strong>Enter your prescription number(s):</strong>
          </div>
<?
    for ($i=1; $i<=10; $i++)
      echo sprintf($script_input_code,$i,$i,$i,$_POST['script'][$i-1],$tbi++);
?>
          <div>
            <label for="comments"><?=$question;?></label>
            <textarea style="width:300px;height:7em;" rows="8" cols="80" tabindex="<?=$tbi++; ?>"
              name="comments" id="comments"><?=$_POST['comments']; ?></textarea>
          </div>
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
        </div>
        <div class='buttons'>
          <input type='submit' name='submit' value='Send Refill Order' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Reset Form' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=_URI_;?>"' />
        </div>

      </form>

<?

}

?>