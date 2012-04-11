<?php

  global $_URL;

  $table = 'site_content';

  if (count($_POST) > 0) {
    db_update($table,$_URL[2],$_URL[3],$_URL[4]);
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

    $search_for = str_replace(array('+','"'),array(' ','&quot;'),urldecode($parms['search']));
?>
      <div id="search">
        <form method="post" id="search_form" action="/admin/<?=$node?>/search/">
          <div>
            <label for="search_for">Search:</label>
            <input type="text" name="search_for" id="search_for" style="width:150px;" value="<?=$search_for;?>" tabindex="1" />
            <input type='submit' name='enter' value='Go' />
          </div>
        </form>
      </div>

      <h1><?=$title;?></h1>
      <table class='edit_list'>
        <tr>
          <td colspan='6'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th style='width:175px;'>
            URL
          </th>
          <th style='width:250px;'>
            Title
          </th>
          <th style='width:40px;'>
            &nbsp;
          </th>
          <th style='width:40px;'>
            &nbsp;
          </th>
          <th style='width:40px;'>
            &nbsp;
          </th>
        </tr>
<?

    while ($item = $items->fetch_array()) {

?>
        <tr>
          <td style='text-align:left'>
            <a href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'><?=$item['url'];?></a>
          </td>
          <td style='text-align:left'>
            <?=$item['title'];?>
          </td>
          <td style='text-align:center'>
            &nbsp;
          </td>
          <td style='text-align:center'>
            &nbsp;
          </td>
          <td style='text-align:center'>
            &nbsp;
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

    $GLOBALS['pageInfo']['title'] = ucwords($action)." Page {$item['url']} ".TITLE_SUFFIX;

    form_v($item);

    $tbi = 1;

?>

      <form id='update_search' class='edit_item' method='post' action='<?=_URI_?>'
            onsubmit='return validate_form(this)' >
        <h2><?=ucwords("$action $node");?></h2>
        <div class='field_input'>
          <div><?=input_field('url',$tbi++,'URL:',null,$item['url'],'200px',null,'before',"");?>
          </div>
          <div><?=input_field('title',$tbi++,'Title:',null,$item['title'],'200px');?>
          </div>
          <div><?=input_field('content_plain',$tbi++,'Content:','textarea',$item['content_plain'],'500px','height:40em');?>
          </div>
        </div>
        <!-- div class='buttons'>
          <?=input_field('enter',$tbi++,null,'submit',$actions[$action]);?>
          <?=input_field('reset',$tbi++,null,'reset','Cancel',null,null,null,
              "onclick='window.location = \"".SITE_HTTP."/admin/$node\"'");?>
        </div -->
        <ul class='date_block'><?=date_block($item);?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($table,$parms) {

    if (isset($parms['search'])) {
      $search_for = str_replace('+',' ',$parms['search']);
      $category['where'] .= "
          where
            match(t.title,t.content_plain)
              against('".addslashes(urldecode($search_for))."') ";
      $category['title'] .= " &mdash; $search_for";
      $relevance = ",
            match(t.title,t.content_plain)
              against('".addslashes(urldecode($search_for))."') as relevance ";
      $category['order']  = "order by relevance desc";
    } else
      $category['order']  = "order by id";


    return array('Site Search Content',do_query("
      select
        t.*
        $relevance
      from
        $table t
      {$category['where']}
      {$category['order']}"));
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

  function db_update($table,$node,$action,$id,$id2 = 0) {
    switch ($action) {
      case "search":
        search_for($table,$node);
        break 1;
        /*
      case "add":
        insert_item($table);
        break 1;
      case "edit":
        update_item($table,$id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
        */
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function search_for($table,$node) {
    if (trim($_POST['search_for']) == '')
      $GLOBALS['_URL'][2] = "$node/";
    else {
      if (FORMS_ESCAPED)
        $_POST['search_for'] = stripslashes($_POST['search_for']);
      $GLOBALS['_URL'][2] = "$node/list/search:".str_replace(' ','+',$_POST['search_for'])."/";
    }
  }

  function insert_item($table) {

    add_slashes($_POST);
    if (strlen(trim($_POST['username'])) < 5
        || strlen(trim($_POST['password'])) < 5
        || !in_array($_POST['type'],array(1,2,3))) {
      return;
    }
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
    $_POST['password'] = md5($_POST['password']);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset'))) {
        if (is_array($value))
          $value = implode(',',$value);
        if (strlen($_POST[$fld]) > 0)
          list($updflds[],$updvals[]) = array($fld,"'$value'");
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
    add_slashes($_POST);
    if (strlen($_POST['username']) < 5
        || (strlen($_POST['password']) > 0 && strlen($_POST['password']) < 5)
        || !in_array($_POST['type'],array(1,2,3))) {
      return;
    }
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
    if ($_POST['password'] == '')
      unset($_POST['password']);
    else
      $_POST['password'] = md5($_POST['password']);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset'))) {
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
  }

?>
