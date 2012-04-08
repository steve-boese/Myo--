<?php
  global $_URL;

  $table = 'e_mail';

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

    $parms['cat']   = (isset($parms['cat']))   ? $parms['cat']   : 'all';
    $parms['order'] = (isset($parms['order'])) ? $parms['order'] : 'name';

    $items = retrieve_item_list($parms);

?>
      <h1><?=ucwords("{$parms['cat']} {$node}");?>s</h1>
      <table class='edit_list emails'>
        <tr>
          <td colspan='5'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add new person</a>
            &nbsp; (Total: <?=$items->num_rows;?>)&nbsp;&nbsp;&nbsp;&nbsp;
            <span class='opt_out'>&nbsp;Opted out&nbsp;</span>&nbsp;&nbsp;&nbsp;
            <span class='deleted'>&nbsp;Deleted&nbsp;</span>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:latest' title='Adds, changes, opt-outs in past week'>Latest</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:invalid' title='Emails with invalid format'>Invalid</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:dupe' title='Duplicate emails in groups'>Dupes</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:all' title='All emails'>All</a>
          </td>
        </tr>
        <tr>
          <th style='width:125px;'>
            <a href='/admin/<?=$node;?>/list/order:name/' title='Order by Name'>Name</a>
          </th>
          <th style='width:145px;'>
            <a href='/admin/<?=$node;?>/list/order:email/' title='Order by Email'>Email</a>
          </th>
          <th style='width:90px;'>
            <a href='/admin/<?=$node;?>/list/order:group/' title='Order by Group'>Group</a>
          </th>
          <th style='width:160px;'>
            Address, Phone
          </th>
          <th>&nbsp;</th>
        </tr>
<?

    while ($item = $items->fetch_array()) {
      $thisname = $item['name'];
      if (strlen($thisname) > 20) {
        if (strlen($thisname) - strlen(str_replace(' ','',$thisname)) < 2)
          $thisname = substr($thisname,0,15).' '.substr($thisname,15,15).'...';
      }
      $thisemail = $item['email'];
      if (strlen($thisemail) > 25) {
        $thisemail = trim(substr($thisemail,0,22)).'...';
      }
      $thisstreet = $item['address'];
      if (strlen($thisstreet) > 25) {
        if (strlen($thisstreet) - strlen(str_replace(' ','',$thisstreet)) < 2)
          $thisstreet = trim(substr($thisstreet,0,25)).'...';
      }
      $address = "";
      if (strlen($thisstreet) > 0)
        $address = $thisstreet."<br/>";
      if (strlen($item['city'].$item['state'].$item['zip']) > 2)
        $address .= "{$item['city']} {$item['state']} {$item['zip']}<br/>";
      if (strlen($item['phone']) > 0)
        $address .= $item['phone']."<br/>";
      $address = substr($address,0,-5);

      list($row_class,$btn_prefix) = array('','');
      if (is_dbdate($item['opted_out']))
        $row_class = "opt_out";
      elseif (is_dbdate($item['deleted']))
        list($row_class,$btn_prefix) = array("deleted","Un-");
      if (strlen($row_class) > 0)
        $row_class = "class='$row_class'";

?>
        <tr <?=$row_class;?>>
          <td><?=$thisname;?></td>
          <td><a href='mailto:<?=$item['email'];?>' title='email: <?=$item['email'];?>'><?=$thisemail;?></a></td>
          <td><?=$item['group_name'];?></td>
          <td><?=$address;?></td>
          <td class='buttons'><a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a><a class='btn' href='/admin/<?=$node;?>/<?=$btn_prefix;?>delete/<?=$item['id'];?>/'><?=$btn_prefix;?>Delete</a></td>
        </tr>
<?  } ?>
      </table>
      
<?}

  function item_form($node,$action,$id) {

    $actions = array(
       "add"       => "Add ".ucwords($node),
       "edit"      => "Update ".ucwords($node),
       "delete"    => "Delete This ".ucwords($node),
       "Un-delete" => "Un-delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['email']} $node".TITLE_SUFFIX;

    $option_code = "
              <option value=\"%s\" %s>%s</option>";

    $groups = do_query("
      select distinct
        group_name
      from
        e_mail
      order by
        group_name");

    $group_opts = sprintf($option_code,'','',' &mdash; Select &mdash;');
    while ($group = $groups->fetch_object())
      $group_opts .= sprintf($option_code,form_v($group->group_name),
            (($group->group_name == $item['group_name']) ? 'selected="selected"' : ''),
            form_v($group->group_name));

    $positions = do_query("
      select distinct
        position
      from
        e_mail
      order by
        position");

    $position_opts = sprintf($option_code,'','',' &mdash; Select &mdash;');
    while ($position = $positions->fetch_object())
      $position_opts .= sprintf($option_code,form_v($position->position_name),
            (($position->position == $item['position']) ? 'selected="selected"' : ''),
            form_v($position->position));

    if ($item['opted_out_ip'] > 0)
      $ip['opted_out'] = 'from IP: '.$item['o_o_ip'];
    if ($item['register_ip'] > 0)
      $ip['created'] = 'from IP: '.$item['o_i_ip'];

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
      if (is_dbdate($item[$fld]))
        $date_data .= sprintf($date_code,$label,dbdate_show($item[$fld],true),$ip[$fld]);
    }
    if (!isset($date_data)) $date_data = sprintf($date_code,'&nbsp;','&nbsp;','&nbsp;');

    form_v($item);
    $tbi = 1;

?>

      <form id='update_email' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div>
            <label for="file_name">Name:</label>
            <input id="name" name="name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['name'];?>"/>
          </div>
          <div>
            <label for="email">Email:</label>
            <input id="email" name="email" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['email'];?>" />
          </div>
          <div>
            <label for="company_name">Organization:</label>
            <input id="company_name" name="company_name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['company_name'];?>" />
          </div>
          <div>
            <label for="address">Street address:</label>
            <textarea id="address" name="address" tabindex='<?=$tbi++;?>' rows='3' cols='45'
              style='width:300px;height:2.7em;'><?=$item['address'];?></textarea>
          </div>
          <div>
            <label for="city">City, state, zip:</label>
            <input id="city" name="city" type="text" tabindex='<?=$tbi++;?>'
              style="width:160px;" value="<?=$item['city'];?>" />
            <input id="state" name="state" type="text" tabindex='<?=$tbi++;?>'
              style="width:35px;" value="<?=$item['state'];?>" />
            <input id="zip" name="zip" type="text" tabindex='<?=$tbi++;?>'
              style="width:90px;" value="<?=$item['zip'];?>" />
          </div>
          <div>
            <label for="phone">Phone:</label>
            <input id="phone" name="phone" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['phone'];?>" />
          </div>
          <div>
            Choose from existing options or create a new one:<br/>
            <label for='group_name'>Group:</label>
            <select id='group_name' name="group_name" tabindex='<?=$tbi++;?>'
              style='width:125px;'><?=$group_opts;?>

            </select>
            <input id="group_new" name="group_new" type="text" tabindex='<?=$tbi++;?>'
              style="width:125px;" value="" />
          </div>
          <div>
            <label for='position'>Position:</label>
            <select id='position' name="position" tabindex='<?=$tbi++;?>'
              style='width:125px;'><?=$position_opts;?>

            </select>
            <input id="position_new" name="position_new" type="text" tabindex='<?=$tbi++;?>'
              style="width:125px;" value="" />
          </div>
          <div>
            <label for="opted_out">Opted out:</label>
            <input id="opted_out" name="opted_out" type="text" tabindex='<?=$tbi++;?>'
              style="width:125px;" value="<?=dbdate_show($item['opted_out']);?>" />
              <?=$ip['opted_out'];?>
          </div>
          <div>
            <label for="notes">Internal notes:</label>
            <textarea id="notes" name="notes" tabindex='<?=$tbi++;?>' rows='3' cols='45'
              style='width:300px;height:7.2em;'><?=$item['notes'];?></textarea>
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

  function retrieve_item_list($parms) {

    $sort = array(
      'name'    => 'order by name,email',
      'email'   => 'order by email,name',
      'group'   => 'order by group_name,name,email');
    $sortfld = $sort[$parms['order']] or $sort['name'];

    $catg = array(
      'all'     => array('query' => 1,'where' => '','order' => $sortfld),
      'dupe'    => array('query' => 2),
      'latest'  => array('query' => 3),
      'invalid' => array('query' => 1,
                         'where' => "where not email REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$'",
                         'order' => $sortfld));
    $category = $catg[$parms['cat']] or $catg['all'];
    
    switch ($category['query']) {
      case 1:
        return do_query("
          select
            *
          from
            e_mail
          {$category['where']}
          {$category['order']}");
      case 2:
        return do_query("
          select
            l.*
          from
            e_mail l
          join
             (select email,group_name,count(*)
                from e_mail
               where deleted = 0
                 and opted_out = 0
            group by email,group_name
              having count(*) > 1) d
            on l.email = d.email and l.group_name = d.group_name
          order by
            l.group_name,
            l.email,
            l.name");
      case 3:
        return do_query("
          select
            l.*,
            case
              when l.deleted > 0
                then l.deleted
              when l.opted_out > 0
                then l.opted_out
              when l.updated > 0
                then l.updated
              else
                l.created
            end as last_change
          from
            e_mail l
          having
            last_change > timestampadd(day,-7,now())
          order by
            last_change desc");
    }
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        *,
        inet_ntoa(opted_out_ip) as o_o_ip,
        inet_ntoa(register_ip) as o_i_ip
      from
        e_mail
      where
        id='$id'");
    return $item->fetch_array();
  }

  function db_update($table,$action,$id,$id2 = 0) {
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
      case "Un-delete":
        delete_item($table,$id);
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item() {
    add_slashes($_POST);
    if ($_POST['group_new'] != "")
      $_POST['group_name'] = $_POST['group_new'];
    if ($_POST['position_new'] != "")
      $_POST['position'] = $_POST['position_new'];
    do_query("
      insert
        into e_mail
         (name,
          email,
          company_name,
          address,
          city,
          state,
          zip,
          phone,
          group_name,
          position,
          opted_out,
          notes,
          created)
        values
         ('{$_POST['name']}',
          '{$_POST['email']}',
          '{$_POST['company_name']}',
          '{$_POST['address']}',
          '{$_POST['city']}',
          '{$_POST['state']}',
          '{$_POST['zip']}',
          '{$_POST['phone']}',
          '{$_POST['group_name']}',
          '{$_POST['position']}',
          '".dbdate($_POST['opted_out'])."',
          '{$_POST['notes']}',
          now())");
  }

  function update_item($id) {
    add_slashes($_POST);
    if ($_POST['group_new'] != "")
      $_POST['group_name'] = $_POST['group_new'];
    if ($_POST['position_new'] != "")
      $_POST['position'] = $_POST['position_new'];
    do_query("
       update
         e_mail
       set
         name    = '{$_POST['name']}',
         email   = '{$_POST['email']}',
         company_name = '{$_POST['company_name']}',
         address = '{$_POST['address']}',
         city    = '{$_POST['city']}',
         state   = '{$_POST['state']}',
         zip     = '{$_POST['zip']}',
         phone   = '{$_POST['phone']}',
         group_name = '{$_POST['group_name']}',
         position   = '{$_POST['position']}',
         opted_out = '".dbdate($_POST['opted_out'])."',
         notes   = '{$_POST['notes']}',
         updated = now()
       where
         id='$id'");
  }


?>