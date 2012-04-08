<?php

  global $_URL;

  $table = 'status';
  
  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('swap'))) {
    db_update($table,$_URL[3],$_URL[4],$_URL[5]);
    header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else
    items_list($_URL[2]);


  function items_list($node) {

?>
      <h1><?="Category Codes";?></h1>
      <table class='edit_list status_admin'>
        <tr>
          <td colspan='7'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?="Category Code";?></a>
          </td>
        </tr>
        <tr>
          <th class='arrows'>
            &nbsp;
          </th>
          <th colspan='2' style='width:250px;'>
            Type, Values
          </th>
          <th style='width:70px;'>
            Default?
          </th>
          <th style='width:70px;'>
            Non Pub?
          </th>
          <th>
            Controls
          </th>
        </tr>
<?

    $items = retrieve_item_list();

    while ($s = $items->fetch_object()) {
      $item_name[$s->id] = htmlentities($s->name);
      if ($s->indent == 0) {
        if (isset($prev_main_id)) {
          $main_prev[$s->id] = $prev_main_id;
          $main_next[$prev_main_id] = $s->id;
        }
        $prev_main_id = $s->id;
        if (isset($prev_sub_id)) {unset($prev_sub_id);}
      }
      else {
        if (isset($prev_sub_id)) {
          $sub_prev[$s->id] = $prev_sub_id;
          $sub_next[$prev_sub_id] = $s->id;
        }
        $prev_sub_id = $s->id;
        $item_count++;
      }
    }

    $non_indented_title = "
          <td colspan='2' %s>
            %s
          </td>";
    $indented_title = "
          <td class='arrows'>
            %s
          </td>
          <td %s style='width:210px;'>
            %s
          </td>";
    $up_arrow_code = "
            <a class='btn' title='Move %s above %s' href='/admin/$node/swap/%d/%d/'><img src='/admin/image/arrow_up.gif' alt=''/></a>";
    $down_arrow_code = "
            <a class='btn' title='Move %s below %s' href='/admin/$node/swap/%d/%d/'><img src='/admin/image/arrow_down.gif' alt=''/></a>";
    $no_arrow_code = "
            <img class='blank_arrow' src='/admin/image/arrow_blank.gif' alt=''/>";

    if ($items->num_rows > 0)
      $items->data_seek(0);
    while ($item = $items->fetch_array()) {

      $id = $item['id'];$main_arrows = '';$sub_arrows = '';$name_class = '';
      if ($item['indent'] == 0) {
        if (isset($main_prev[$id]))
          $main_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$main_prev[$id]],$id,$main_prev[$id]);
        else
          $main_arrows .= $no_arrow_code;
        if (isset($main_next[$id]))
          $main_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$main_next[$id]],$id,$main_next[$id]);
        else
          $main_arrows .= $no_arrow_code;
        $title = sprintf($non_indented_title,$name_class,$item_name[$id]);
      } else {
        if (isset($sub_prev[$id]))
          $sub_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$sub_prev[$id]],$id,$sub_prev[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        if (isset($sub_next[$id]))
          $sub_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$sub_next[$id]],$id,$sub_next[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        $title = sprintf($indented_title,$sub_arrows,$name_class,$item_name[$id]);
      }

?>
        <tr class='<?=$item['status'].$dupe[$item['file_name']];?>'>
          <td class='arrows'>
            <?=$main_arrows;?>
          </td><?=$title;?>

          <td style='text-align:center;'>
            <? if($item['is_default'] == 1) echo "yes";?>
          </td>
          <td style='text-align:center;'>
            <? if($item['is_closed'] == 1) echo "yes";?>
          </td>
          <td>
            <a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a><br/>
          </td>
        </tr>
<?  }  ?>
      </table>

<?}

  function item_form($node,$action,$id) {

    $actions = array(
       "add"      => "Add "."Category Code",
       "edit"     => "Update "."Category Code",
       "delete"   => "Delete this "."Category Code");
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['name']} Category Code".TITLE_SUFFIX;

    $other_statuses = retrieve_item_list($id);
    $display_after = 0;
    if (!isset($item['id']))
      $item['seq'] = 999999;
    while ($other = $other_statuses->fetch_array()) {
      if ($other['seq'] < $item['seq'])
        $display_after = $other['id'];
    }

    $option_code = "
              <option value=\"%s\" %s>%s%s</option>";
    if ($display_after == 0) $slctd = 'selected="selected"';
    $display_after_opts = sprintf($option_code,'0',$slctd,'-- ','Put this code at the top','--');
    $other_statuses->data_seek(0);
    while ($other = $other_statuses->fetch_array()) {
      list($slctd,$prefix) = array('','');
      if ($other['id'] == $display_after)
        $slctd = 'selected="selected"';
      if ($other['indent'] == "1")
        $prefix = '--';
      $display_after_opts .= sprintf($option_code,$other['id'],$slctd,$prefix,htmlentities($other['name']));
    }

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

    if ($item['member_of'] > 0)
      $is_sub_status = 1;
    else
      $is_sub_status = 0;
      
    if ($item['is_default'] <> 1)
      $item['is_default'] = '0';

    if ($item['is_closed'] <> 1)
      $item['is_closed'] = '0';

    $users = do_query("
      select
        id,
        username
      from
        staff
      where
        deleted = 0
      order by
        username");
    while ($u = $users->fetch_object())
      $user[$u->id] = $u->username;
    $date_code = "
          <li>
            <label>
              %s:
            </label>
            %s %s
          </li>";
    $date_block = array(
        "created"    => "Created",
        "updated"    => "Updated",
        "deleted"    => "Deleted");
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld])) {
        if (isset($user[$item[$fld.'_by']]))
          $by_user = 'by '.$user[$item[$fld.'_by']];else $by_user = '';
        $date_data .= sprintf($date_code,$label,dbdate_show($item[$fld]),$by_user);
      }
    }
    if (!isset($date_data)) $date_data = sprintf($date_code,"&nbsp;","&nbsp;","&nbsp;");

    $tbi = 1;

?>

      <form id='update_status' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?="Category Code";?></h2>
        <div class='field_input'>
          <div>
            <label for="name">Name:</label>
            <input id="name" name="name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=form_v($item['name']);?>" />
          </div>
          <div>
            <label for='display_after'>Display after:</label>
            <select id='display_after' name="display_after" tabindex='<?=$tbi++;?>'><?=$display_after_opts;?>

            </select>
            <input id="display_after_orig" name="display_after_orig" type="hidden" value="<?=$display_after;?>" />
          </div>
          <div>
            <label>This is a sub-status?</label>
            <input type="radio" name="is_sub_status" id="sub_status_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$is_sub_status];?> />
              <label for="sub_status_true" class="radio">Yes</label>
            <input type="radio" name="is_sub_status" id="sub_status_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$is_sub_status];?> />
              <label for="sub_status_false" class="radio">No</label>
            <input id="sub_status_orig" name="sub_status_orig" type="hidden" value="<?=$is_sub_status;?>" />
          </div>
          <div>
            <label>Default value?</label>
            <input type="radio" name="is_default" id="is_default_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['is_default']];?> />
              <label for="is_default_true" class="radio">Yes</label>
            <input type="radio" name="is_default" id="is_default_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['is_default']];?> />
              <label for="is_default_false" class="radio">No</label>
          </div>
          <div>
            <label>Non public?</label>
            <input type="radio" name="is_closed" id="is_closed_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['is_closed']];?> />
              <label for="is_closed_true" class="radio">Yes</label>
            <input type="radio" name="is_closed" id="is_closed_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['is_closed']];?> />
              <label for="is_closed_false" class="radio">No</label>
          </div>
          <div>
            <label for="on_pages">On page(s):</label>
            <input id="on_pages" name="on_pages" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=form_v($item['on_pages']);?>" />
          </div>
        </div>
        <div class='buttons'>
          <input type='submit' name='enter' value='<?=$actions[$action];?>' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=SITE_HTTP."/admin/$node";?>"' />
          <input type='hidden' name='action_step' value='process' />
        </div>
        <ul class='date_block'><?=$date_data;?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($skip_status = '') {
    if ($skip_status > 0)
      $statuslimits = "and s.id <> '$skip_status'";

    return do_query("
     select
        s.id as id,
        s.seq,
        0 as indent,
        s.name,
        0 as is_default,
        0 as is_closed
      from
        status s
      where
        s.deleted = 0 and
        s.member_of = 0 $statuslimits

      union

      select
        s.id as id,
        s.seq,
        1 as indent,
        s.name,
        s.is_default,
        s.is_closed
      from
        status s
      join
        status c
          on s.member_of = c.id
      where
        s.member_of > 0 and
        s.deleted = 0 and
        c.deleted = 0 $statuslimits

      order by
        seq");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        s.*
      from
        status s
      where
        s.id = '$id'");
    return $item->fetch_array();
  }

  function db_update($table,$action,$id,$id2 = 0) {
    if (isset($_POST['cancel']))
      return;
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
      case "swap":
        swap_item($table,$id,$id2);
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

    $disp_order = new_display_order($table,0,$_POST['display_after']);
    $sub_of = new_member_of($table,$_POST['is_sub_status'],$disp_order);

    add_slashes($_POST);

    do_query("
      insert
        into $table
          (name,
           seq,
           member_of,
           is_default,
           is_closed,
           on_pages,
           created_by,
           created)
         values
          ('{$_POST['name']}',
           '$disp_order',
           '$sub_of',
           '{$_POST['is_default']}',
           '{$_POST['is_closed']}',
           '{$_POST['on_pages']}',
           '{$_SESSION[SITE_PORT]['user_id']}',
           now())");

    set_defaults($sub_of,$GLOBALS['db']->insert_id,$_POST['is_default']);
    resequence_table($table);

  }

  function update_item($table,$id) {

    $status = do_query("
      select
        *
      from
        $table
      where
        id = '$id'");
    $status = $status->fetch_array();

    $disp_order = new_display_order($table,$status['seq'],$_POST['display_after']);
    $sub_of     = new_member_of($table,$_POST['is_sub_status'],$disp_order);

    add_slashes($_POST);

    do_query("
      update
        status
      set
        name       = '{$_POST['name']}',
        seq        = '$disp_order',
        member_of  = '$sub_of',
        is_default = '{$_POST['is_default']}',
        is_closed  = '{$_POST['is_closed']}',
        on_pages   = '{$_POST['on_pages']}',
        updated_by = '{$_SESSION[SITE_PORT]['user_id']}',
        updated    = now()
      where
        id='$id'");

    set_defaults($sub_of,$id,$_POST['is_default']);
    resequence_table($table);
  }

  function set_defaults($member_of,$id,$is_default) {
    if ($member_of == 0 or $is_default == 0)
      return;
      
    do_query("
      update
        status
      set
        is_default = 0
      where
        member_of = '$member_of' and
        id <> '$id' and
        is_default = 1");
  }


?>