<?php

  global $_URL;

  $table = 'plugin';

  if (count($_POST) > 0) {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");
    
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

?>
      <h1><?=$title;?></h1>
      <table class='edit_list'>
        <tr>
          <td colspan='6'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th style='width:50px;'>
            Seq
          </th>
          <th style='width:150px;'>
            Module
          </th>
          <th style='width:150px;'>
            Tag
          </th>
          <th style='width:75px;'>
            Status
          </th>
          <th style='width:100px;'>
            Master only
          </th>
          <th style='width:60px;'>
            &nbsp;
          </th>
        </tr>
<?

    $status  = array("1" => "Active");
    $master  = array("1" => "Yes");

    while ($item = $items->fetch_array()) {

?>
        <tr>
          <td style='text-align:center'>
            <?=$item['seq'];?>
          </td>
          <td>
            <a href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'><?=$item['name'];?></a>
          </td>
          <td>
            <?=$item['tag'];?>
          </td>
          <td style='text-align:center'>
            <?=$status[$item['active']];?>
          </td>
          <td style='text-align:center'>
            <?=$master[$item['master_only']];?>
          </td>
          <td style='text-align:center'>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a>
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

   $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['name']} $node".TITLE_SUFFIX;

   $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

   form_v($item);
   $tbi = 1;

?>

      <form id='update_module' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords("$action $node");?></h2>
        <div class='field_input'>
          <div><?=input_field('seq',$tbi++,'Sequence:',null,$item['seq'],'60px');?>
          </div>
          <div><?=input_field('name',$tbi++,'Plugin name:',null,$item['name'],'300px');?>
          </div>
          <div><?=input_field('tag',$tbi++,'Tag:',null,$item['tag'],'300px');?>
          </div>
          <div><?=input_field('plugin_loc',$tbi++,'Location:',null,$item['plugin_loc'],'300px');?>
          </div>
          <div><?=input_field('admin_loc',$tbi++,'Admin loc:',null,$item['admin_loc'],'300px');?>
          </div>
          <div>
            <label>Admin by link:</label>
            <input type="radio" name="admin_by_link" id="admin_by_link_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['admin_by_link']];?> />
              <label for="admin_by_link_true" class="radio">Yes</label>
            <input type="radio" name="admin_by_link" id="admin_by_link_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['admin_by_link']];?> />
              <label for="admin_by_link_false" class="radio">No</label>
          </div>
          <div>
            <label>Master only:</label>
            <input type="radio" name="master_only" id="master_only_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['master_only']];?> />
              <label for="master_only_true" class="radio">Yes</label>
            <input type="radio" name="master_only" id="master_only_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['master_only']];?> />
              <label for="master_only_false" class="radio">No</label>
          </div>
          <div>
            <label>Active:</label>
            <input type="radio" name="active" id="active_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['active']];?> />
              <label for="active_true" class="radio">Yes</label>
            <input type="radio" name="active" id="active_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['active']];?> />
              <label for="active_false" class="radio">No</label>
          </div>
        </div>
        <div class='buttons'>
          <?=input_field('enter',$tbi++,null,'submit',$actions[$action]);?>
          <?=input_field('reset',$tbi++,null,'reset','Cancel',null,null,null,
              "onclick='window.location = \"".SITE_HTTP."/admin/$node\"'");?>
        </div>
        <ul class='date_block'><?=date_block($item);?>

        </ul>
      </form>

<?
}


  function retrieve_item_list($table,$parms) {
    return array('Plugins',do_query("
      select
        t.*
      from
        $table t
      where
        deleted = '".ZERO_DATE."'
      order by
        seq"));
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
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

    add_slashes($_POST);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset')) && strlen($value) > 0) {
        $updflds[] = $fld;
        $updvals[] = "'$value'";
      }
    }
    if (!is_array($updflds)) return;
    $updflds = array_merge($updflds,array("created","created_by"));
    $updvals = array_merge($updvals,array("now()","'{$_SESSION[SITE_PORT]['user_id']}'"));
    do_query("
      insert into
        $table
         (".implode(',',$updflds).")
        values
         (".implode(',',$updvals).")");
    $new_id = $GLOBALS['db']->insert_id;
  }

  function update_item($table,$id) {
    $row = do_query("
      select
        *
      from
        $table
      where
        id = '$id'");
    $item = $row->fetch_array();
    add_slashes($_POST);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset'))) {
        if ($_POST[$fld] <> $item[$fld]) {
          if (strlen(trim($_POST[$fld])) == 0)
            $newval = "null";
          else
            $newval = "'".add_slashes($_POST[$fld])."'";
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
  }

?>