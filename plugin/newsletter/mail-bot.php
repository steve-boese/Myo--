<?php

  require(ABSPATH . 'admin/site/phpmailer/class.phpmailer.php');
  require(ABSPATH . 'admin/site/html2text.php');

  $GLOBALS['debug_track'] = true;

  $queue  = do_query("
     select
       q.*,
       n.group_name,
       n.subject,
       n.content
     from
       e_queue q
     join e_news n
       on q.e_news = n.id
     where
       completed = 0 and
       q.deleted = 0 and
       n.deleted = 0
     order by
       q.created,
       q.min_id");

  if (mysql_num_rows($queue) == 0)
    exit("No emails in queue");

  $GLOBALS['track']  = "";
  $config = mysql_fetch_array(do_query("
    select
      *
    from
      e_config"));

  $from   = "{$config['from_name']} <{$config['from_email']}>";
  $style  = file_get_contents(ABSPATH . 'module/newsletter/newsletter.css');
  $last_query = "
     select
       sum(quantity) as sent,
       min(completed) as since,
       minute(timediff(now(),min(completed))) as minutes_ago
     from e_queue
       where completed > date_add(now(),interval -1 hour)";

  while($q = mysql_fetch_array($queue)) {
    do_echo("queue id {$q['id']} quantity {$q['quantity']}");

    $last_hour = mysql_fetch_array(do_query($last_query));
    if ($last_hour['sent'] > $config['max_per_hour'] * .75 && $last_hour['minutes_ago'] > 50) {
      $wait = (60 - $last_hour['minutes_ago']) * 60 + 30;
      do_echo("last sent {$last_hour['minutes_ago']} minutes ago. Waiting $wait seconds");
      do_sleep($wait);
      $last_hour = mysql_fetch_array(do_query($last_query));
    }

    if ($q['quantity'] + $last_hour['sent'] > $config['max_per_hour']) {
      do_echo("Maxed out for the hour: {$last_hour['sent']} sent since {$last_hour['since']}");
      break;
    }

    if($q['group_name'] != "all" && $q['group_name'] != "") {
      $group = "and group_name='{$q['group_name']}'";
    } else
      $group = "";

    $subject = $q['subject'];

    $emails = do_query("
      select
        email,
        name
      from
        mailing_list
      where
        length(email) > 5 and
        opted_out = 0 and
        deleted = 0 $group and
        id between {$q['min_id']} + 1 and {$q['max_id']}
      order by
        id");

    $keep_alive = 1;
    while ($recip = mysql_fetch_array($emails)) {

      $to = $recip['email'];
      //override for testing purposes only
      $to = 'steve@tidydude.com';

      $mail = new PHPMailer();
      $mail->IsSendmail();

      $mail->From       = $config['from_email'];
      $mail->FromName   = $config['from_name'];
      $mail->ReturnPath = $config['from_email'];
      $mail->AddAddress($to,$recip['name']);

      $mail->IsHTML(true);
      $mail->Subject    = $q['subject'];

      $html   = $config['wrapper_head'] .
                $q['content']      .
                str_replace('<opt-out>',$config['opt_out'],$config['wrapper_foot']);
      $tags   = array(
                'subject'   => "$subject",
                'style'     => "$style",
                'email'     => "$to",
                'company'   => "{$config['company_name']}",
                'url'       => "{$config['home_domain']}");
      foreach ($tags as $tag => $value) {
        $html = str_replace('<'.$tag.'>',$value,$html);
        $html = str_replace('&lt;'.$tag.'&gt;',$value,$html);
      }

      $mail->Body       = $html;

      $h2t              =& new html2text($html);
      do_echo("{$recip['name']} <{$recip['email']}>");
      $mail->AltBody    = $h2t->get_text();
      if (!$mail->Send())
        do_echo(" -- FAILED!!!");

      unset($mail);
      $keep_alive = keep_alive($keep_alive);

    }

    do_query("
      update
        e_queue
      set
        completed = now()
      where
        id = {$q['id']}");
    do_echo("");
    do_echo("Completed queue id {$q['id']} after sending ".
              mysql_num_rows($emails)." messages; sleeping now.");
    do_echo("");
    do_echo("");
    $master_counter += mysql_num_rows($emails);
    do_sleep(120);

  }

  do_echo("");
  if ($master_counter > 0)
    $final_message = "Sent $master_counter messages";
  else
    $final_message = "No messages sent";

  do_echo($final_message);


  $mail = new PHPMailer();
  $mail->IsSendmail();

  $mail->From       = $config['from_email'];
  $mail->FromName   = $config['from_name'];
  $mail->ReturnPath = $config['from_email'];
  $mail->AddAddress($config['from_email']);
  //$mail->AddAddress('steve@tidydude.com');

  $mail->IsHTML(true);
  $mail->Subject    = $final_message;
  $mail->Body       = $GLOBALS['track'];
  if (!$mail->Send())
    echo "Tracking message send failed: {$GLOBALS['track']}";

  function do_sleep($seconds) {
    for ($i = 1; $i <= $seconds/10; $i++) {
      echo $i." ";
      do_query("select 1");
      sleep(10);
    }
    echo "  <br>\n";
  }

  function do_echo($line) {
    if (strlen($line) == 0) {
      echo "\n";
      $GLOBALS['track'] .= "&nbsp;<br>";
    } else {
      $GLOBALS['track'] .= date('r').' '.htmlspecialchars($line)."<br>";
      echo date('r').' '.$line."\n";
    }
  }

  function keep_alive($counter) {
    $counter++;
    if ($counter > 10) {
      do_query("select 1");
      $counter -= 10;
    }
    sleep(1);
    return $counter;
  }

?>
