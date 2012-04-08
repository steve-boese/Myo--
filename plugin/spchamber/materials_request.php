<?php

  if (count($_POST) > 0)
    $error = edit_Form();
  
  if (count($_POST) > 0 && !isset($error)) {
    send_Email();
    $thankyou_msg = (isset($GLOBALS['_TAG']['thankyou'])) ? $GLOBALS['_TAG']['thankyou']
          : "Thank you for contacting us!";
    echo "
      <div class='request_thankYou'>$thankyou_msg</div>";
  } else {
    if (is_array($error))
      echo "
        <div class=\"warning\">".implode("<br />",$error)."</div>";
    request_Form();
  }
  
  function edit_Form() {

    if ($_POST['email'] == "")
      $error = array("Your email is required","Please fix it and re-send");
    elseif (!valid_email($_POST['email']))
      $error = array("{$_POST['email']} is not a valid email","Please fix it and re-send");

    return $error;
  }

  function send_Email() {

    require(ABSPATH . 'xtool/phpmailer/class.phpmailer.php');
    require(ABSPATH . 'xtool/html2text.php');
    
    foreach ($_POST as $fld=>$value)
      $_POST[$fld] = str_replace("\'","'",$_POST[$fld]);

    $msgTo = retrieve_valid_emails($GLOBALS['_TAG']['emailTo']);

    if (!is_array($_POST['categ']))
      $materials = "<blockquote>(None)</blockquote>\r";
    else {
      $matl = do_query("
        select
          name
        from
          status
        where
          id in (".implode(',',$_POST['categ']).")
        order by
          seq");
        while ($mat = $matl->fetch_object())
          $mt[] = $mat->name;
        $materials = "<ul><li>".implode("</li><li>",$mt)."</li></ul>";
    }

    $msgBody = "
<html>
  <body>
    <div style='text-align:left'>
      <h2>Request Information</h2>
      <p>
        <b>First, last name:</b> {$_POST['firstname']} {$_POST['lastname']}<br />
        <b>Address:</b> {$_POST['address']}<br />
        <b>City, state, zip:</b> {$_POST['city']} {$_POST['state']} {$_POST['zip']}<br />
        <b>Phone:</b> {$_POST['phone']}<br />
        <b>Email:</b> {$_POST['email']}<br />
      </p>
      <h2>Message or request:</h2>
      <p>".nl2br(htmlspecialchars($_POST['comments']))."</p>
      <h2>Material(s) Requested:</h2>
        $materials
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
    $mail->AddReplyTo($_POST['email'],$_POST['firstname'].' '.$_POST['lastname']);

    $mail->IsHTML(true);

    $mail->Subject    = "Materials Request from {$_POST['firstname']} {$_POST['lastname']}";
    $mail->Body       = $msgBody;
    $mail->AltBody    = $h2t->get_text();

    if (!$mail->Send())
      doError("Unable to send request email",$mail->ErrorInfo);

  }

  function request_Form() {

    $checkbox_code = "
          <li %s>
            <input name=\"categ[%s]\" id=\"categ%s\"  type=\"checkbox\" value=\"%s\" tabindex=\"%s\" %s/>
            <label for=\"categ%s\">%s</label>
          </li>";

    $the_cats = array(141);
    $tbi = 1;
    foreach ($the_cats as $the_cat) {
      $categ = get_members_of($the_cat);
      while ($catg = $categ->fetch_object()) {
        list($style,$checked) = array('','');
        if (is_array($_POST['category']) && in_array($catg->id,$_POST['category']))
          list($style,$checked) = array("class=\"selected\"","checked=\"checked\"");
        $the_cat_opts[$the_cat][] = sprintf($checkbox_code,$style,$catg->id,
                   $catg->id,$catg->id,++$tbi,$checked,$catg->id,$catg->name);
      }
    }

    $col_size = ceil(count($the_cat_opts[141])/2);
    foreach($the_cat_opts[141] as $req_cat) {
      $i++;
      $cell = floor(($i-1)/$col_size);
      $cat[$cell][] = $req_cat;
    }
    foreach($cat as $ct)
      $c[] = "\n<ul>".implode("\n",$ct)."\n</ul>\n";
    $request_categories = "
        <div class='request_matl_type first'>".implode("</div><div class='request_matl_type'>",$c)."</div>";

    $question  = (isset($GLOBALS['_TAG']['question'])) ? $GLOBALS['_TAG']['question']
               : 'Your message or request:';
               
    foreach ($_POST as $fld=>$value)
      $_POST[$fld] = str_replace("\'","&apos;",$_POST[$fld]);

?>

      <h4>Select materials needed:</h4>
      
      <form id='request_form' class='edit_item' method='post' action='<?=_URI_;?>' onsubmit='return validate_form(this)' >
        <div><?=$request_categories;?>
        </div>

        <div class='request_matl'>
          <div><?=input_field('firstname',$tbi++,'First name',null,$_POST['firstname'],'200px',null,'before');?>
          </div>
          <div><?=input_field('lastname',$tbi++,'Last name',null,$_POST['lastname'],'200px',null,'before');?>
          </div>
        </div>

        <div class='request_matl'>
          <div><?=input_field('address',$tbi++,'Address',null,$_POST['address'],'435px',null,'before');?>
          </div>
        </div>

        <div class='request_matl'>
          <div><?=input_field('city',$tbi++,'City',null,$_POST['city'],'200px',null,'before');?>
          </div>
          <div><?=input_field('state',$tbi++,'State',null,$_POST['state'],'45px',null,'before');?>
          </div>
          <div><?=input_field('zip',$tbi++,'Zip',null,$_POST['zip'],'120px',null,'before');?>
          </div>
        </div>

        <div class='request_matl'>
          <div><?=input_field('phone',$tbi++,'Phone',null,$_POST['phone'],'200px',null,'before');?>
          </div>
          <div><?=input_field('email',$tbi++,'Email',null,$_POST['email'],'200px',null,'before');
                 /* class=\"email\" onfocus=\"clear_error(this)\" onblur=\"validate_field(this)\"") */ ?>
          </div>
        </div>

        <div class='request_matl'>
          <div>
            <label for="comments"><?=$question;?></label>
            <textarea style="width:435px;height:2.8em;" rows="8" cols="80" tabindex="<?=$tbi++; ?>"
              name="comments" id="comments"><?=$_POST['comments']; ?></textarea>
          </div>
        </div>
        <div class='buttons request_matl'>
          <input type='submit' name='submit' value='Send request' tabindex='<?=$tbi++;?>'/>
        </div>

      </form>

<?

  }

?>