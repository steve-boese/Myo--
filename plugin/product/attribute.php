<?php
  global $_URL;

  $table = 'product_attribute';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('swap'))) {
    db_update($table,$_URL[3],$_URL[4],$_URL[5]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else
    items_list($_URL[2]);


  function items_list($node) {

?>
      <h1><?=ucwords("{$node}s");?></h1>
      <table class='edit_list page_admin'>
        <tr>
          <td colspan='6'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th class='arrows'>
            &nbsp;
          </th>
          <th style='width:80px;'>
            Name
          </th>
          <th style='width:100px;'>
            Display Name
          </th>
          <th style='width:150px;'>
            Values
          </th>
          <th>
            Controls
          </th>
        </tr>
<?

    $items = retrieve_item_list();

    while ($p = mysql_fetch_object ($items)) {
      $item_name[$p->id] = htmlentities($p->menu_name);
      if ($p->indent == 0) {
        if (isset($prev_main_id)) {
          $main_prev[$p->id] = $prev_main_id;
          $main_next[$prev_main_id] = $p->id;
        }
        $prev_main_id = $p->id;
        if (isset($prev_member_of)) {unset($prev_member_of);}
      }
      else {
        if (isset($prev_member_of)) {
          $sub_prev[$p->id] = $prev_member_of;
          $sub_next[$prev_member_of] = $p->id;
        }
        $prev_member_of = $p->id;
        $item_count++;
      }
    }

    //    <td colspan='2' %s>  -- for indenting
    $non_indented_title = "
          <td %s>
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

    mysql_data_seek($items,0);
    while ($item = mysql_fetch_array($items)) {

      $id = $item['id'];$main_arrows = '';$sub_arrows = '';
      if ($item['indent'] == 0) {
        if (isset($main_prev[$id]))
          $main_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$main_prev[$id]],$id,$main_prev[$id]);
        else
          $main_arrows .= $no_arrow_code;
        if (isset($main_next[$id]))
          $main_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$main_next[$id]],$id,$main_next[$id]);
        else
          $main_arrows .= $no_arrow_code;
        $title = sprintf($non_indented_title,'',$item_name[$id]);
      } else {
        if (isset($sub_prev[$id]))
          $sub_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$sub_prev[$id]],$id,$sub_prev[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        if (isset($sub_next[$id]))
          $sub_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$sub_next[$id]],$id,$sub_next[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        $title = sprintf($indented_title,$sub_arrows,'',$item_name[$id]);
      }

?>
        <tr class='<?=$item['status'];?>'>
          <td class='arrows'>
            <?=$main_arrows;?>
          </td><?=$title;?>

          <td>
            <?=$item['display_name'];?>
          </td>
          <td style='text-align:center;'>
            <?=str_replace(',','<br />',$item['value_list']);?>
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
       "add"      => "Add ".ucwords($node),
       "edit"     => "Update ".ucwords($node),
       "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $other_pages = retrieve_item_list($id);
    $display_after = 0;
    if (!isset($item['id']))
      $item['seq'] = 999999;
    while ($other = mysql_fetch_array($other_pages)) {
      if ($other['seq'] < $item['seq'])
        $display_after = $other['id'];
    }

    $option_code = "
              <option value=\"%s\" %s>%s%s%s</option>";
    if ($display_after == 0) $slctd = 'selected="selected"';
    $display_after_opts = sprintf($option_code,'0',$slctd,'-- ','Put this attribute at the top','--');
    mysql_data_seek($other_pages,0);
    while ($other = mysql_fetch_array($other_pages)) {
      list($slctd,$prefix,$suffix) = array('','','');
      if ($other['id'] == $display_after)
        $slctd = 'selected="selected"';
      if ($other['indent'] == "1")
        $prefix = '--';
      $display_after_opts .= sprintf($option_code,
          $other['id'],$slctd,$prefix,form_v($other['menu_name']),$suffix);
    }

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

    if ($item['member_of'] > 0)
      $is_sub_page = 1;
    else
      $is_sub_page = 0;

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
        "deleted"    => "Deleted");
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld]))
        $date_data .= sprintf($date_code,$label,dbdate_show($item[$fld],true));
    }
    if (!isset($date_data)) $date_data = sprintf($date_code,"&nbsp;","&nbsp;");

    form_v($item);
    $tbi = 1;

?>

      <form id='update_attrib' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div>
            <label for="name">Attribute:</label>
            <input id="name" name="name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['name'];?>" />
          </div>
          <div>
            <label for="title">Display name:</label>
            <input id="display_name" name="display_name" type="text" tabindex='<?=$tbi++;?>' style="width:300px;" value="<?=$item['display_name'];?>" />
          </div>
          <div>
            <label for='display_after'>Display after:</label>
            <select id='display_after' name="display_after" tabindex='<?=$tbi++;?>'><?=$display_after_opts;?>

            </select>
            <input id="display_after_orig" name="display_after_orig" type="hidden" value="<?=$display_after;?>" />
          </div>
          <div>
            <label for='value_list'>Value list:</label>
            <textarea id='value_list' name="value_list" cols='80' rows='3'
              style='width:300px;height:8.4em;' tabindex='<?=$tbi++;?>'><?=str_replace(',',"\n",$item['value_list']);?></textarea>
          </div>
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

  function retrieve_item_list($skip_page = '') {
    if ($skip_page > 0)
      $pagelimits = "and p.id <> '$skip_page'";

    return do_query("
     select
        p.id as id,
        p.seq,
        0 as indent,
        p.name as menu_name,
        p.display_name,
        p.value_list
      from
        product_attribute p
      where
        p.deleted = 0 and
        p.member_of = 0 $pagelimits

      union

      select
        p.id as id,
        p.seq,
        1 as indent,
        p.name as menu_name,
        p.display_name,
        p.value_list
      from
        product_attribute p
      join
        product_attribute m
          on p.member_of = m.id
      where
        p.member_of > 0 and
        p.deleted = 0 and
        m.deleted = 0 $pagelimits

      order by
        seq");
  }

  function retrieve_item($id) {
    return mysql_fetch_array(do_query("
      select
        a.*
      from
        product_attribute a
      where
        a.id = '$id'"));
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
        swap_item($table,$id,$id2,'seq','seq_prev','member_of');
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

    $_POST['is_sub_page'] = 0;

    $disp_order = new_display_order($table,0,$_POST['display_after'],'seq');
    $sub_of     = new_member_of($table,$_POST['is_sub_page'],$disp_order,'seq','member_of');

    add_slashes($_POST);

    do_query("
      insert
        into product_attribute
          (name,
           display_name,
           seq,
           member_of,
           value_list,
           created)
         values
          ('{$_POST['name']}',
           '{$_POST['display_name']}',
           '$disp_order',
           '$sub_of',
           '".str_replace("\n",',',$_POST['value_list'])."',
           now())");

    resequence_table($table);

  }

  function update_item($table,$id) {

    $_POST['is_sub_page'] = 0;

    $page = mysql_fetch_array(do_query("
      select
        *
      from
        $table
      where
        id = '$id'"));

    $disp_order = new_display_order($table,$page['seq'],$_POST['display_after'],'seq');
    $sub_of     = new_member_of($table,$_POST['is_sub_page'],$disp_order,'seq','member_of');

    add_slashes($_POST);

    do_query("
      update
        product_attribute
      set
        name         = '{$_POST['name']}',
        display_name = '{$_POST['display_name']}',
        seq          = '$disp_order',
        member_of    = '$sub_of',
        value_list   = '".str_replace("\r",'',str_replace("\n",',',$_POST['value_list']))."',
        updated      = now()
      where
        id='$id'");

    resequence_table($table);
  }


?>