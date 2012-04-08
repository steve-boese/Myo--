<?php

  $GLOBALS['pageInfo']['stylesheets'] .= "\n/admin/image/doc.css";

  global $_URL;

  $table = 'staff';

  if (count($_POST) > 0) {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('pw_reset'))) {
    password_reset($table,$_URL[2],$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && !in_array($_URL[3],array('','list'))) {
    item_form($table,$_URL[2],$_URL[3],$_URL[4]);

  } else {
    $parms = array();
    for ($i=4; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = $parm[1];
      }
    }
    items_list($table,$_URL[2],$parms);
  }

  function items_list($table,$node,$parms) {

    list($title,$items) = retrieve_item_list($table,$parms);

    if ($_SESSION[SITE_PORT]['user_type'] < 3)
      list($cols,$user_type_head) = array(6,"
          <th style='width:100px;'>
            Type
          </th>");
    else
      list($cols,$user_type_head) = array(5,"");

?>
      <h1><?=$title;?></h1>
      <table class='edit_list'>
        <tr>
          <td colspan='<?=$cols;?>'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th style='width:125px;'>
            User
          </th><?=$user_type_head;?>

          <th style='width:140px;'>
            Last login
          </th>
          <th style='width:80px;'>
            Logins
          </th>
          <th style='width:150px;'>
            &nbsp;
          </th>
        </tr>
<?

    $user_type = array(
      '1' => 'Config',
      '2' => 'Admin',
      '3' => 'User');

    while ($item = $items->fetch_array()) {

      if ($_SESSION[SITE_PORT]['user_type'] < 3)
        $user_type_data = "
          <td style='text-align:center'>
            {$user_type[$item['type']]}
          </td>";

?>
        <tr>
          <td style='text-align:center'>
            <a href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'><?=$item['username'];?></a>
          </td><?=$user_type_data;?>
          <td style='text-align:center'>
            <?=dbdate_show($item['last_login'],true);?>
          </td>
          <td style='text-align:center'>
            <?=$item['total_logins'];?>
          </td>
          <td style='text-align:center'>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a>
            <a class='btn' href='/admin/<?=$node;?>/pw_reset/<?=$item['id'];?>/'>PW Reset</a>
          </td>
        </tr>
<?
    }
    $items->close();
?>
      </table>

<?}

  function item_form($table,$node,$action,$id) {

    $actions = array(
        "add"      => "Add ".ucwords($node),
        "edit"     => "Update ".ucwords($node),
        "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($table,$id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $GLOBALS['pageInfo']['title'] = ucwords($action)." Staff Member {$item['username']} ".TITLE_SUFFIX;

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

    form_v($item);

    $option_code = "
              <option value='%s' %s>%s</option>";

    $user_type = array(
      '1' => 'Config',
      '2' => 'Admin',
      '3' => 'User');

    if (!isset($user_type[$item['type']])) $item['type'] = 2;
    foreach($user_type as $key => $val) {
      $slctd = '';
      if ($key == $item['type'])
        $slctd = 'selected="selected"';
      $type_opts .= sprintf($option_code,$key,$slctd,$val);
    }

    $sel_pages = explode(",",$item['pages']);
    $all_pages = do_query("
      select
        case when length(menu_name) > 2 then menu_name else file_name end as name,
        p.*
      from
        page p
      where
        id > 1 and
        deleted = 0
      order by
        display_order");
    while ($page = $all_pages->fetch_array()) {
      $slctd = '';
      if (in_array($page['id'],$sel_pages))
        $slctd = 'selected="selected"';
      $page_opts .= sprintf($option_code,$page['id'],$slctd,htmlentities($page['name']));
    }

    $sel_modules = explode(",",$item['modules']);
    $all_modules = do_query("
      select
        *
      from
        plugin
      where
        active = '1' and
        id > 2 and
        admin_loc <> '' and
        deleted = 0
      order by
        seq,
        name");
    while ($module = $all_modules->fetch_array()) {
      $slctd = '';
      if (in_array($module['id'],$sel_modules))
        $slctd = 'selected="selected"';
      $module_opts .= sprintf($option_code,$module['id'],$slctd,$module['name']);
    }

    $tbi = 1;

?>

      <form id='update_user' class='edit_item' method='post' action='<?=_URI_?>'
            onsubmit='return validate_form(this)' >
        <h2><?=ucwords("$action $node");?></h2>
        <div class='field_input'>
          <div><?=input_field('username',$tbi++,'Username:',null,$item['username'],'200px',null,'before',"
                class=\"required\" onfocus=\"clear_error(this)\" onblur=\"validate_field(this)\"");?>
          </div>
          <div><?=input_field('email',$tbi++,'Email:',null,$item['email'],'200px',null,'before',"
                class=\"required email\" onfocus=\"clear_error(this)\" onblur=\"validate_field(this)\"");?>
          </div>
<?

    if ($_SESSION[SITE_PORT]['user_type'] < 3) {

?>

          <div>
            <label for='type'>User type:</label>
            <select id='type' name='type' tabindex='<?=$tbi++;?>'><?=$type_opts;?>

            </select>
          </div>
          <div>
            <label for='pages'>Authorized pages:</label>
            <select multiple='multiple' id='pages' name="pages[]" size='5' tabindex='<?=$tbi++;?>'><?=$page_opts;?>

            </select>
          </div>
          <div>
            <label for='modules'>Authorized modules:</label>
            <select multiple='multiple' id='modules' name="modules[]" tabindex='<?=$tbi++;?>'><?=$module_opts;?>

            </select>
          </div>
<?

    }

    $checkbox_code = "
        <li %s>
          <input name=\"tag[%s]\" id=\"tag%s\"  type=\"checkbox\" value=\"%s\" tabindex=\"%s\" %s/>
          <label for=\"tag%s\" class=\"ckbox\">%s</label>
        </li>";

    /*
    $tags = do_query("
        select
          *
        from
          xk_tags
        where
          deleted = '".ZERO_DATE."'
        order by
          name");
    while ($tag = $tags->fetch_object()) {
      list($style,$checked) = array('','');
      if (is_array($item['tags']) && in_array($tag->id,$item['tags']))
        list($style,$checked) = array("class=\"selected\"","checked=\"checked\"");
      $the_tag_opts[] = sprintf($checkbox_code,$style,$tag->id,
                        $tag->id,$tag->id,$tbi++,$checked,$tag->id,$tag->name);
    }
    $tag_code  = "\n<ul>".implode("\n",$the_tag_opts)."\n</ul>\n";
    */

?>

          <div id="tag_ckbox">
            <label>Tags:</label>
            <?=$tag_code;?>
          </div>
        </div>
        <div class='buttons'>
          <?=input_field('enter',$tbi++,null,'submit',$actions[$action]);?>
          <?=input_field('reset',$tbi++,null,'reset','Cancel',null,null,null,
              "onclick='window.location = \"".SITE_HTTP."/admin/$node\"'");?>
        </div>
        <ul class='date_block'><?=date_block($item,array('created','updated','deleted','last_login'));?>

        </ul>
      </form>

<?
}

  function password_reset ($table,$node,$action,$id) {
    $key = rand(2957389000,9257389000)+uniqid();
    do_query("
      update
        staff
      set
        pw_set = '".ZERO_DATE."',
        pw_key = '$key'
      where
        id = '$id'");
    $staff = do_query("
      select
        username,
        email
      from
        staff
      where
        id = '$id'");
    $s = $staff->fetch_object();

    $headers  =
        "MIME-Version: 1.0\r\n".
        "Content-type: text/html; charset=iso-8859-1\r\n".
	      "From: ".SITE_EMAIL_FROM."\r\n".
	      "Bcc: ".SITE_TECH_EMAIL."\r\n";
    $message =
        "<div style=\"text-align:left; font-size:8pt;\">\r\n".
        "<p>Login info for you at the ".SITE_NAME." site has either ".
        "been updated or set up for the first time.</p>".
        "<p>Please go to <a href='".SITE_HTTP."/pwd-reset/$key'>".
        SITE_HTTP."/pwd-reset/$key</a> to set your password and ".
        "log in.</p>".
        "<ul><li>USER NAME: <strong>{$s->username}</strong>".
        "</li><li>PASSWORD: <strong>You will create your own.</strong>".
        "</li></ul>".
        "<p>Thanks!</p>".
        "</div>\n";
        
    mail($s->email,"Login at ".SITE_NAME." site",$message,$headers);
  }

  function retrieve_item_list($table,$parms) {
    return array('Site Staff Members',do_query("
      select
        t.*
      from
        $table t
      where
        deleted = '".ZERO_DATE."' and
        type >= '{$_SESSION[SITE_PORT]['user_type']}'
      order by
        username"));
  }

  function retrieve_item($table,$id) {
    $item = do_query("
      select
        t.*,
        cre.username as created_by_name,
        upd.username as updated_by_name,
        del.username as deleted_by_name
      from
        $table t
      left join staff cre on t.created_by = cre.id
      left join staff upd on t.updated_by = upd.id
      left join staff del on t.deleted_by = del.id
      where
        t.id='$id'");
    return $item->fetch_array();

/*
    $row = $item->fetch_array();

    $tags = do_query("
      select
        *
      from
        staff_tagged
      where
        deleted = '".ZERO_DATE."' and
        staff_id = '$id'");
    $row['tags'] = array();
    while($tag = $tags->fetch_object())
      $row['tags'][] = $tag->tag_id;
    return $row;
*/    

  }

  function db_update($table,$action,$id,$id2 = 0) {
    if (isset($_POST['cancel']))
      return;
    foreach ($_POST as &$fld) {
      if (is_scalar($fld))
        $fld = trim($fld);
    }
    if (!isset($_POST['pages']))
      $_POST['pages'] = array();
    if (!isset($_POST['modules']))
      $_POST['modules'] = array();
    // admin staff can't create master users
    if ($_SESSION[SITE_PORT]['user_type'] != 1) {
      if ($_POST['user_type'] == 1)
        $_POST['user_type'] = 2;
    }
    switch ($action) {
      case "add":
        insert_item($table);
        break 1;
      case "edit":
        update_item($table,$id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

    add_slashes($_POST);
    if (strlen(trim($_POST['username'])) < 5)
      return;

    $user = do_query("
      select
        username
      from
        staff
      where
        deleted = 0 and
        username = '{$_POST['username']}'");
    if ($user->num_rows > 0)
      return;
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset','tag'))) {
        if (is_array($value))
          $value = implode(',',$value);
        if (strlen($_POST[$fld]) > 0)
          list($updflds[],$updvals[]) = array($fld,"'$value'");
      }
    }
    if (!is_array($updflds)) return;
    if ($_SESSION[SITE_PORT]['user_type'] >= 3)
      list($updflds[],$updvals[]) = array('type',"'3'");
    $updflds = array_merge($updflds,array("created","created_by"));
    $updvals = array_merge($updvals,array("now()","'{$_SESSION[SITE_PORT]['user_id']}'"));
    do_query("
      insert into
        $table
         (".implode(',',$updflds).")
        values
         (".implode(',',$updvals).")");
    $new_id = $GLOBALS['db']->insert_id;

    $tags_added     = (isset($_POST['tag']) && is_array($_POST['tag'])) ? $_POST['tag'] : array();

    if (count($tags_added) > 0) {
      foreach ($tags_added as $add)
        $adds[] = "('$new_id','$add',now(),'".$_SESSION[SITE_PORT]['user_id']."')";
      do_query("
        insert into
          staff_tagged
           (staff_id,tag_id,created,created_by)
          values
           ".implode(',',$adds));
    }
  }

  function update_item($table,$id) {
    add_slashes($_POST);
    if (strlen($_POST['username']) < 5)
      return;

    $row = do_query("
      select
        *
      from
        $table
      where
        deleted = '".ZERO_DATE."' and
        (username = '{$_POST['username']}' or id = '$id')");
    if ($row->num_rows > 1)
      return;
    if ($_SESSION[SITE_PORT]['user_type'] <> 1) {
      $item = $row->fetch_array();
      if ($item['type'] == 1)
        return;
    }
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset','tag'))) {
        if (is_array($value))
          $value = implode(',',$value);
        if ($value <> $item[$fld]) {
          if (strlen(trim($value)) == 0)
            $newval = "null";
          else
            $newval = "'".add_slashes($value)."'";
          $updts[] = "$fld = $newval";
        }
      }
    }
    if (isset($updts) && is_array($updts)) {
      $updts[] = "updated = now()";
      $updts[] = "updated_by = '{$_SESSION[SITE_PORT]['user_id']}'";
      do_query("
        update
          $table
        set
          ".implode(',',$updts)."
        where
          id='$id'");
    }

    $tags = do_query("
        select
          group_concat(tag_id) as tags
        from
          staff_tagged
        where
          deleted = '".ZERO_DATE."' and
          staff_id = '$id'");
    $tag = $tags->fetch_array();
    $oldtags = ($tag['tags'] == '') ? array() : explode(',',$tag['tags']);
    $newtags = (isset($_POST['tag']) && is_array($_POST['tag'])) ? $_POST['tag'] : array();
    $tags_added     = array_diff($newtags,$oldtags);
    $tags_deleted   = array_diff($oldtags,$newtags);

    if (count($tags_deleted) > 0)
      do_query("
        update
          staff_tagged
        set
          deleted = now(),
          deleted_by = '".$_SESSION[SITE_PORT]['user_id']."'
        where
          deleted = '".ZERO_DATE."' and
          staff_id = '$id' and
          tag_id in (".implode(',',$tags_deleted).")");

    if (count($tags_added) > 0) {
      foreach ($tags_added as $add)
        $adds[] = "('$id','$add',now(),'".$_SESSION[SITE_PORT]['user_id']."')";
      do_query("
        insert into
          staff_tagged
           (staff_id,tag_id,created,created_by)
          values
           ".implode(',',$adds));
    }
  }

?>
