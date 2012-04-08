<?php

  global $_URL;

  $table = 'template';

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
          <th style='width:80px;'>
            Name
          </th>
          <th style='width:145px;'>
            File
          </th>
          <th style='width:145px;'>
            Stylesheets
          </th>
          <th style='width:145px;'>
            Scripts
          </th>
          <th style='width:160px;'>
            Favicon
          </th>
          <th style='width:60px;'>
            &nbsp;
          </th>
        </tr>
<?

    while ($item = $items->fetch_object()) {
      $is_default = ($item->is_default == 1) ? ' (default)' : '';

?>
        <tr>
          <td style='text-align:center;'>
            <a href='/admin/<?=$node;?>/edit/<?=$item->id;?>/'><?=$item->name.$is_default;?></a>
          </td>
          <td style='font-size:8pt;'>
            <?=$item->file;?>
          </td>
          <td style='font-size:8pt;'>
            <?=nl2br($item->stylesheets);?>
          </td>
          <td style='font-size:8pt;'>
            <?=nl2br($item->scripts);?>
          </td>
          <td style='font-size:8pt;'>
            <?=$item->favicon;?>
          </td>
          <td style='text-align:center'>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item->id;?>/'>Delete</a>
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
          <div><?=input_field('name',$tbi++,'Template name:',null,$item['name'],'300px');?>
          </div>
          <div><?=input_field('file',$tbi++,'File:',null,$item['file'],'300px');?>
          </div>
          <div>
            <label>Default:</label>
            <input type="radio" name="is_default" id="is_default_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['is_default']];?> />
              <label for="is_default_true" class="radio">Yes</label>
            <input type="radio" name="is_default" id="is_default_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['is_default']];?> />
              <label for="is_default_false" class="radio">No</label>
          </div>
          <div><?=input_field('stylesheets',$tbi++,'Stylesheets:','textarea',$item['stylesheets'],'300px','height:7.5em');?>
          </div>
          <div><?=input_field('scripts',$tbi++,'Scripts:','textarea',$item['scripts'],'300px','height:7.5em');?>
          </div>
          <div><?=input_field('favicon',$tbi++,'Favicon:',null,$item['favicon'],'300px');?>
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
    return array('Templates',do_query("
      select
        t.*
      from
        $table t
      where
        deleted = '".ZERO_DATE."'"));
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