<?php

  global $_URL;

  $table = 'client_task';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && !in_array($_URL[3],array('','list'))) {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else {
    $parms = array();
    for ($i=4; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = $parm[1];
      }
    }
    items_list($_URL[2],$parms);
  }

  function items_list($node,$parms) {
    if (!isset($parms['cat']))
      $parms['cat'] = 'open';
    if (!isset($parms['user']))
      $parms['user'] = 'all';

?>
      <h1><?=ucwords("{$parms['cat']}");?>
      Client <?=ucwords("{$node}");?>s for
      <?=ucwords("{$parms['user']}");?></h1>
      <table class='edit_list'>
        <tr>
          <td colspan='7'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:open/user:<?=$parms['user'];?>/'>Open</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:closed/user:<?=$parms['user'];?>/'>Closed</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:<?=$parms['cat'];?>/user:boese/'>Boese</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:<?=$parms['cat'];?>/user:all/'>All</a>
          </td>
        </tr>
        <tr>
          <th style='width:120px;'>
            Client
          </th>
          <th style='width:120px;'>
            Initiated
          </th>
          <th style='width:120px;'>
            Need
          </th>
          <th style='width:120px;'>
            Subject
          </th>
          <th style='width:120px;'>
            Priority
          </th>
          <th style='width:120px;'>
            Follow up
          </th>
          <th style='width:60px;'>
            &nbsp;
          </th>
        </tr>
<?

    $items = retrieve_item_list($parms);

    while ($item = $items->fetch_array()) {

?>
        <tr>
          <td>
            <a href='/admin/client/edit/<?=$item['client'];?>/'><?=$item['client_name'];?></a>
          </td>
          <td>
            <?=dbdate_show($item['initiated']);?><br/>
            <?=$item['via_name'];?>
          </td>
          <td>
            <?=$item['need_name'];?>
          </td>
          <td>
            <a href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'><?=$item['title'];?></a>
          </td>
          <td>
            <?=$item['priority_name'];?>
          </td>
          <td>
            <?=$item['asgn_name'];?><br/>
            <?=dbdate_show($item['f_u_target']);?> <?=$item['f_u_target_text'];?><br/>
            <?=$item['status_name'];?>
          </td>
          <td style='text-align:center'>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a>
          </td>
        </tr>
<?    }  ?>
      </table>

<? }

  function item_form($node,$action,$id) {

    $actions = array(
        "add"      => "Add ".ucwords($node),
        "edit"     => "Update ".ucwords($node),
        "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action (1)</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action (2)</h1>");

    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['title']} $node".TITLE_SUFFIX;

    $option_code = "
              <option value=\"%s\" %s>%s</option>";

    $client_opts   = get_opts('client','name',$item['client'],$option_code);
    $via_opts      = get_status_opts(9,$item['init_via'],$option_code);
    $need_opts     = get_status_opts(26,$item['client_need'],$option_code);
    $priority_opts = get_status_opts(33,$item['priority'],$option_code);
    $staff_opts    = get_opts('staff','username',$item['f_u_assigned_to'],$option_code);
    $status_opts   = get_status_opts(40,$item['f_u_status'],$option_code);

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
         "deleted"    => "Deleted",);
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld])) {
        if ($item["{$fld}_by_name"] <> '')
          $byname = ' by '.$item["{$fld}_by_name"]; else $byname = '';
        $date_data[] = sprintf($date_code,$label,dbdate_show($item[$fld],true).$byname);
      }
    }
    if (!isset($date_data)) $date_data[] = sprintf($date_code,"&nbsp;","&nbsp;");

    form_v($item);
    $tbi = 1;

?>

      <form id='update_task' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords("$action $node");?></h2>
        <div class='field_input'>
          <div>
            <label for='client'>Client:</label>
            <select id='client' name="client" style="width:250px;" tabindex='<?=$tbi++;?>'><?=implode('',$client_opts);?>

            </select>
          </div>
          <div>
            <label for="initiated">Initiated:</label>
            <input id="initiated" name="initiated" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=dbdate_show($item['initiated']);?>" />
          </div>
          <div>
            <label for='init_via'>Via:</label>
            <select id='init_via' name="init_via" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$via_opts);?>

            </select>
            <input id="init_via_new" name="init_via_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['init_via_new'];?>" />
          </div>
          <div>
            <label for='client_need'>Need:</label>
            <select id='client_need' name="client_need" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$need_opts);?>

            </select>
            <input id="client_need_new" name="client_need_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['client_need_new'];?>" />
          </div>
          <div>
            <label for='title'>Subject:</label>
            <input id="title" name="title" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['title'];?>" />
          </div>
          <div>
            <label for="notes">Notes:</label> &nbsp;
          </div>
        </div>
        <textarea id="notes" name="notes" tabindex='<?=$tbi++;?>' cols='80' rows='5'
                style="width:700px;height:15em;"><?=$item['notes'];?></textarea>
        <div class='field_input'>
          <div>
            <label for='priority'>Priority:</label>
            <select id='priority' name="priority" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$priority_opts);?>

            </select>
            <input id="priority_new" name="priority_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['priority_new'];?>" />
          </div>
          <div>
            <label for='f_u_assigned_to'>Assigned to:</label>
            <select id='f_u_assigned_to' name="f_u_assigned_to" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$staff_opts);?>

            </select>
          </div>
          <div>
            <label for="f_u_target">Target date, desc:</label>
            <input id="f_u_target" name="f_u_target" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=dbdate_show($item['f_u_target']);?>" />
            <input id="f_u_target_text" name="f_u_target_text" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['f_u_target_text'];?>" />
          </div>
          <div>
            <label for='f_u_status'>Status:</label>
            <select id='f_u_status' name="f_u_status" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$status_opts);?>

            </select>
            <input id="f_u_status_new" name="f_u_status_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['f_u_status_new'];?>" />
          </div>
          <div>
            <label for="status_as_of">As of:</label>
            <input id="status_as_of" name="status_as_of" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=dbdate_show($item['status_as_of']);?>" />
          </div>
        </div>
        <div class='buttons'>
          <input type='submit' name='enter' value='<?=$actions[$action];?>' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?="/admin/$node";?>"' />
          <input type='hidden' name='action_step' value='process' />
        </div>
        <ul class='date_block'><?=implode('',$date_data);?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($parms) {
    if ($parms['cat'] == 'open') {
      $where = "
        ifnull(stat.is_closed,0) <> 1";
      $order = "
      order by
        c.f_u_target,
        pri.seq desc";
    } else {
      $where = "
        ifnull(stat.is_closed,0) = 1";
      $order = "
      order by
        ifnull(c.status_as_of,c.updated) desc";
    }
    if (isset($parms['user']) && $parms['user'] != 'all')
      $where .= " and
        usr.username = '{$parms['user']}'";

    return do_query("
      select
        c.*,
        clt.short_name as client_name,
        via.name   as via_name,
        need.name  as need_name,
        pri.name   as priority_name,
        usr.username as asgn_name,
        stat.name  as status_name
      from
        client_task c
      left join
        client clt
          on c.client = clt.id
      left join
        status via
          on c.init_via = via.id
      left join
        status need
          on c.client_need = need.id
      left join
        status pri
          on c.priority = pri.id
      left join
        staff usr
          on c.f_u_assigned_to = usr.id
      left join
        status stat
          on c.f_u_status = stat.id
      where
        c.deleted = 0 and $where $order");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        t.*,
        cre.username created_by_name,
        upd.username updated_by_name,
        del.username deleted_by_name
      from
        client_task t
      left join
        staff cre
          on t.created_by = cre.id
      left join
        staff upd
          on t.updated_by = upd.id
      left join
        staff del
          on t.deleted_by = del.id
      where
        t.id = '$id'");
    return $item->fetch_array();
  }

  function db_update($table,$action,$id) {

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
    // admin users can't create master users
    if ($_SESSION['user_type'] != 1) {
      if ($_POST['user_type'] == 1)
        $_POST['user_type'] = 2;
    }
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
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item() {
    pre_update_process();
    if ($_POST['initiated'] == '')
      $_POST['initiated'] = 'now';

    do_query("
      insert
        into client_task
         (client,
          initiated,
          init_via,
          client_need,
          title,
          notes,
          priority,
          f_u_assigned_to,
          f_u_target,
          f_u_target_text,
          f_u_status,
          status_as_of,
          created,
          created_by)
        values
         ('{$_POST['client']}',
          '".dbdate($_POST['initiated'])."',
          '{$_POST['init_via']}',
          '{$_POST['client_need']}',
          '{$_POST['title']}',
          '{$_POST['notes']}',
          '{$_POST['priority']}',
          '{$_POST['f_u_assigned_to']}',
          '".dbdate($_POST['f_u_target'])."',
          '{$_POST['f_u_target_text']}',
          '{$_POST['f_u_status']}',
          '".dbdate($_POST['status_as_of'])."',
          now(),
          '{$_SESSION['user_id']}')");
  }

  function update_item($id) {
    pre_update_process();
    do_query("
      update
        client_task
      set
        client       = '{$_POST['client']}',
        initiated    = '".dbdate($_POST['initiated'])."',
        init_via     = '{$_POST['init_via']}',
        client_need  = '{$_POST['client_need']}',
        title        = '{$_POST['title']}',
        notes        = '{$_POST['notes']}',
        priority     = '{$_POST['priority']}',
        f_u_assigned_to = '{$_POST['f_u_assigned_to']}',
        f_u_target   = '".dbdate($_POST['f_u_target'])."',
        f_u_target_text = '{$_POST['f_u_target_text']}',
        f_u_status   = '{$_POST['f_u_status']}',
        status_as_of = '".dbdate($_POST['status_as_of'])."',
        updated      = now(),
        updated_by   = '{$_SESSION['user_id']}'
      where
        id='$id'");
  }
  
  function pre_update_process() {
    add_slashes($_POST);

    if ($_POST['init_via_new'] <> '')
      $_POST['init_via'] = insert_status(9,$_POST['init_via_new']);

    if ($_POST['client_need_new'] <> '')
      $_POST['client_need'] = insert_status(26,$_POST['client_need_new']);

    if ($_POST['priority_new'] <> '')
      $_POST['priority'] = insert_status(33,$_POST['priority_new']);

    if ($_POST['f_u_status_new'] <> '')
      $_POST['f_u_status'] = insert_status(40,$_POST['f_u_status_new']);
  }

?>
