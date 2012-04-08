<?php

  global $_URL;

  $table = 'e_news';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('queue','cancel'))) {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else
    items_list($_URL[2]);
    

  function items_list($node) {

?>
      <h1><?=ucwords("{$node}s");?></h1>
      <table class='edit_list e_news'>
        <tr>
          <td colspan='5'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <span class='queued'>&nbsp;Queued&nbsp;</span>
          </td>
        </tr>
        <tr>
          <th style='width:145px;'>
            Subject
          </th>
          <th style='width:95px;'>
            Group
          </th>
          <th style='width:160px;'>
            Dates
          </th>
          <th style='width:100px;'>
            Counts
          </th>
          <th>
            Controls
          </th>
        </tr>
<?

    $items = retrieve_item_list();

    $date_code = "
              <label>%s</label> %s<br>";
    $date_fields = array(
        "created"    => "Created",
        "updated"    => "Updated",
        "first_sent" => "1st Sent",
        "last_sent"  => "Last Sent");
        
    while ($item = $items->fetch_array()) {
      $date_block = "";
      foreach($date_fields as $fld=>$label) {
        if (is_dbdate($item[$fld]))
          $date_block .= sprintf($date_code,$label,dbdate_show($item[$fld]));
      }
      if ($date_block != "") {
        $date_block = "
            <div>$date_block
            </div>";
      }

      list($btn_text,$link,$item_class,$counts) = array("Queue Send","queue","","");
      if ($item['queued'] > 0) {
        $counts .= number_format($item['queued']). " Queued<br>";
        $item_class = "class=\"queued\"";
        $btn_text  = "Cancel Send";
        $link = "cancel";
      }
      if ($item['sent'] > 0)
        $counts .= number_format($item['sent']). " Sent<br>";

?>
        <tr <?=$item_class;?>>
          <td>
            <?=$item['subject'];?>
          </td>
          <td>
            <?=$item['group_name'];?>
          </td>
          <td class='dates'><?=$date_block;?>
          </td>
          <td><?=$counts;?>
          </td>
          <td>
            <a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a>
            <a class='btn' href='/newsletter/<?=$item['id'];?>/'>Preview</a>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a><br>
            <form action='/admin/<?=$node;?>/send_test/<?=$item['id'];?>/' method='post'>
              Send test email to:<br>
              <input type='hidden' name='action_step' value='process'>
              <input type='text' name='email' style='width:120px;'>
              <input type='submit' value='go'><br>
            </form>
            <a class='btn' href='/admin/<?=$node;?>/<?=$link;?>/<?=$item['id'];?>'><?=$btn_text;?></a>
          </td>
        </tr>
<?  }  ?>
      </table>
      
<?}

  function item_form($node,$action,$id) {

    $actions = array(
       "add"      => "Add ".ucwords($node),
       "edit"     => "Update ".ucwords($node),
       "queue"    => "Queue This ".ucwords($node),
       "cancel"   => "Cancel This ".ucwords($node),
       "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);
     
    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $GLOBALS['pageInfo']['scripts'] .= "\n/site/script/page-name.js\n/xtool/ckeditor/ckeditor.js";

    $groups = retrieve_groups();

    $total = 0;$count = 0;
    $option_code = "
              <option value=\"%s\" %s>%s (%d members)</option>";
    while ($group = $groups->fetch_array()) {
      $total += $group['size'];$count++;
      if ($group['group_name'] == $item['group_name']) {
        $slctd = 'selected="selected"';
        $found = true;
      } else
        $slctd = '';
      if ($group['group_name'] == "")
        $label = '(no group name) ';
      else
        $label = form_v($group['group_name']);
      $group_opts .= sprintf($option_code,form_v($group['group_name']),$slctd,$label,$group['size']);
    }
    if ($count > 1 && $found)
      $group_opts .= sprintf($option_code,"all","","All",$total);
    else if ($count > 1 && $item['group_name'] == "all")
      $group_opts .= sprintf($option_code,"all","selected='selected'","All",$total);
    else if ($count > 1) {   // multiple available, user didn't select from list
      $group_opts = sprintf($option_code,"all","","All",$total).$group_opts;
      $group_opts .= sprintf($option_code,form_v($item['group_name']),"selected='selected'",form_v($item['group_name']),0);
    } else if (!$found)      // only 1 option available, but wasn't selected
      $group_opts .= sprintf($option_code,form_v($item['group_name']),"selected='selected'",form_v($item['group_name']),0);

    $date_code = "
          <li>
            <label>
              %s:
            </label>
            %s
          </li>";
    $date_block = array(
        "created"    => "Created",
        "updated"    => "Updated",
        "deleted"    => "Deleted",
        "first_sent" => "1st Sent",
        "last_sent"  => "Last Sent");
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld]))
        $date_data .= sprintf($date_code,$label,dbdate_show($item[$fld],true));
    }
    if (!isset($date_data)) $date_data = sprintf($date_code,"&nbsp;","&nbsp;");

    $tbi = 1;

?>

      <form id='update_enews' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> E-Newsletter</h2>
        <div class='field_input'>
          <div>
            <label for='group_name'>Group:</label>
            <select id='group_name' name="group_name" tabindex='<?=$tbi++;?>'><?=$group_opts;?>

            </select>
          </div>
          <div>
            <label for="subject">Subject:</label>
            <input id="subject" name="subject" type="text" tabindex='<?=$tbi++;?>' style="width:300px;" value="<?=form_v($item['subject']);?>" />
          </div>

          <div>
            <label for="content_edit">Email Body:</label> &nbsp;
          </div>
        </div>
        <div class='wysiwyg'>
          <textarea name='content_edit' id='content_edit' rows='5' cols='80' tabindex='<?=$tbi++;?>'><?=htmlspecialchars($item['content']);?></textarea>
          <script type='text/javascript'>
            CKEDITOR.replace('content_edit',
              {
                customConfig : '/admin/ckeditor/enews_config.php',
                toolbar : 'Page'
              });
          </script>
        </div>
        <div class='buttons'>
          <input type='submit' name='enter' value='<?=$actions[$action];?>' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?="/admin/$node";?>"' />
          <input type='hidden' name='action_step' value='process' />
        </div>
        <ul class='date_block'><?=$date_data;?>
        
        </ul>
      </form>
   
<?
}

  function retrieve_groups() {
    return do_query("
      select
        group_name,
        count(*) as size
      from
        e_mail
      where
        deleted = 0 and
        opted_out = 0
      group by
        group_name
      order by
        group_name");
  }

  function retrieve_item_list() {
    return do_query("
      select
        n.*,
        s.*,
        q.*
      from
        e_news n
      left join
       (select
          e_news as s_news,
          sum(quantity) as sent,
          min(completed) as first_sent,
          max(completed) as last_sent
        from e_queue
          where completed > 0 and deleted = 0
        group by s_news) s
          on n.id = s.s_news
      left join
       (select
          e_news as q_news,
          sum(quantity) as queued
        from e_queue
          where completed = 0 and deleted = 0
        group by q_news) q
          on n.id = q.q_news
      where
        n.deleted = 0
      order by
        case when n.updated = 0 then n.created else n.created end desc,
        subject");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        n.*,
        q.*
      from
        e_news n
      left join
       (select
          e_news,
          min(completed) as first_sent,
          max(completed) as last_sent,
          sum(quantity)  as total_sent
        from
          e_queue
        where
          completed > 0 and
          e_news = '$id'
        group by
          e_news) q
        on n.id = q.e_news
      where
        n.id = '$id'");
    return $item->fetch_array();
  }

  function db_update($table,$action,$id) {
    if (isset($_POST['cancel']))
      return;
    switch ($action) {
      case "add":
        insert_item();
        break 1;
      case "edit":
        update_item($id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
      case "queue":
        queue_item($id);
        break 1;
      case "cancel":
        cancel_item($id);
        break 1;
      case "send_test":
        send_test($id);
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item() {
    do_query("
      insert
        into e_news
         (group_name,
          subject,
          content,
          created)
        values
         ('".add_slashes($_POST['group_name'])."',
          '".add_slashes($_POST['subject'])."',
          '{$_POST['content_edit']}',
          now())");
  }

  function update_item($id) {
    do_query("
      update
        e_news
      set
        group_name = '".add_slashes($_POST['group_name'])."',
        subject    = '".add_slashes($_POST['subject'])."',
        content    = '{$_POST['content_edit']}',
        updated    = now()
      where
        id='$id'");
  }
  
  function queue_item($id) {
    do_query("
      update
        e_queue
      set
        deleted=now()
      where
        deleted = 0 and
        completed = 0 and
        e_news = '$id'");
        
    $item = do_query("
      select
        group_name
      from
        e_news
      where
        id='$id'");
    $item = $item->fetch_array();
    if (!isset($item['group_name']))
      exit("<h1>e-Newsletter $id not found</h1>");
        
    $config = do_query("
      select
        batch_size
      from
        e_config");
    $config = $config->fetch_array();
    if (!isset($config['batch_size']))
      exit("<h1>Batch size not set</h1>");

    if ($item['group_name'] == "all")
      $group = "";
    else
      $group = "and group_name = '{$item['group_name']}'";
      
    $max = do_query("
      select
        max(id) as max
      from
        e_mail
      where
        deleted = 0 and
        opted_out = 0 $group");
    $max = $max->fetch_array();

    $low=0;
    while ($low < $max['max']) {
      $limits = do_query("
        select
          max(s.id) as high,
          count(*) as quantity
        from
         (select
            id
          from
            e_mail
          where
            deleted = 0 and
            opted_out = 0 and
            id > {$low} {$group}
          order by
            id
          limit {$config['batch_size']}) s");
      $limits = $limits->fetch_array();
      do_query("
        insert into
          e_queue
           (e_news,
            min_id,
            max_id,
            quantity,
            created)
          values
           ($id,
            $low,
            {$limits['high']},
            {$limits['quantity']},
            now())");
      $low = $limits['high'];
    }
  }

  function cancel_item($id) {
    do_query("
      update
        e_queue
      set
        deleted = now()
      where
        deleted = 0 and
        completed = 0 and
        e_news = '$id'");
  }
  
  function send_test($id) {
    //echo "<h1>send test</h1><pre>".print_r($_POST,true)."</pre>";exit;
    if (!valid_email($_POST['email']))
      exit("Invalid email format");
      
    require(ABSPATH . 'admin/site/phpmailer/class.phpmailer.php');
    require(ABSPATH . 'admin/site/html2text.php');

    $e_news = do_query("
      select
        *
      from
        e_news
      where
        id = $id");
    $e_news = $e_news->fetch_array();
    $config = do_query("
      select
        *
      from
        e_config");
    $config = $config->fetch_array();

    $mail = new PHPMailer();
    $mail->IsSendmail();

    $mail->From       = SITE_EMAIL_FROM;
    $mail->FromName   = SITE_NAME;
    $mail->AddAddress($_POST['email']);
    $mail->IsHTML(true);
    $mail->Subject    = $e_news['subject'];

    $style  = file_get_contents(ABSPATH . 'site/style/enews.css');
    $head   = str_replace("<subject>",$subject,$config['wrapper_head']);
    $head   = str_replace("<style>",$style,$head);
    $optout = str_replace("<email>",$_POST['email'],$config['opt_out']);
    $optout = str_replace("<company>",$config['company_name'],$optout);
    $optout = str_replace("<url>",SITE_HTTP,$optout);
    $foot   = str_replace("<opt-out>",$optout,$config['wrapper_foot']);
    $html   = $head.$e_news['content'].$foot;

    $mail->Body       = $html;

    $h2t              =& new html2text($html);
    $mail->AltBody    = $h2t->get_text();
    if (!$mail->Send())
      exit("<br /><br />Sending of test email to {$_POST['email']} failed.");
  }

?>