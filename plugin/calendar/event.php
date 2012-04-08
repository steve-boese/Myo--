<?php
  global $_URL;

  $table = 'event';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
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

    $parms['cat']   = (isset($parms['cat']))   ? $parms['cat']   : 'pending';
    $parms['order'] = (isset($parms['order'])) ? $parms['order'] : 'forward';

    $items = retrieve_item_list($table,$parms);
    
    $years = retrieve_item_years($table);
    $year_code = "
            <a class='btn' href='/admin/%s/list/cat:%s' title='Events during %s'>%s</a>";
    while ($yr = $years->fetch_object())
      $yrs .= sprintf($year_code,$node,$yr->year,$yr->year,$yr->year);
    $years->close();

?>
      <h1><?=ucwords("{$parms['cat']} {$node}");?>s</h1>
      <table class='edit_list events'>
        <tr>
          <td colspan='5'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add new event</a>
            &nbsp; (Total: <?=$items->num_rows;?>)&nbsp;&nbsp;&nbsp;&nbsp;
            <a class='btn' href='/admin/<?=$node;?>/list/cat:recent/order:<?=$parms['order'];?>' title='Last 3 months'>Recent</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:pending/order:<?=$parms['order'];?>' title='Up-coming'>Pending</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:all/order:<?=$parms['order'];?>' title='All Events'>All</a><?=$yrs;?>
          </td>
        </tr>
        <tr>
          <th style='width:100px;'>
            <a href='/admin/<?=$node;?>/list/cat:<?=$parms['cat'];?>/order:forward/' title='Order: Older to Newer Dates'>Start</a>
          </th>
          <th style='width:100px;'>
            <a href='/admin/<?=$node;?>/list/cat:<?=$parms['cat'];?>/order:backward/' title='Order: Newer to Older Dates'>End</a>
          </th>
          <th style='width:220px;'>
            <a href='/admin/<?=$node;?>/list/cat:<?=$parms['cat'];?>/order:event/' title='Order by Event Title'>Event</a>
          </th>
          <th style='width:220px;'>
            Location
          </th>
          <th>&nbsp;</th>
        </tr>
<?

    while ($item = $items->fetch_array()) {

      $start = dbdate_show($item['start'],true);
      $end   = dbdate_show($item['end'],true);
      list($row_class,$btn_prefix) = array('','');
      if (is_dbdate($item['opted_out']))
        $row_class = "opt_out";
      elseif (is_dbdate($item['deleted']))
        list($row_class,$btn_prefix) = array("deleted","Un-");
      if (strlen($row_class) > 0)
        $row_class = "class='$row_class'";

?>
        <tr <?=$row_class;?>>
          <td><?=$start;?></td>
          <td><?=$end;?></td>
          <td><?=$item['event'];?></td>
          <td><?=$item['location'];?></td>
          <td class='buttons'><a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a><a class='btn' href='/admin/<?=$node;?>/<?=$btn_prefix;?>delete/<?=$item['id'];?>/'><?=$btn_prefix;?>Delete</a></td>
        </tr>
<?
    }
    $items->close();
?>
      </table>

<?}

  function item_form($table,$node,$action,$id) {

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

    $option_code = "
              <option value=\"%s\" %s>%s</option>";

    $groups = do_query("
      select distinct
        group_name
      from
        $table
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
        $table
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

  function retrieve_item_list($table,$parms) {

    $sort = array(
      'forward'  => 'order by start,event',
      'backward' => 'order by start desc,event',
      'event'    => 'order by event,start');
      
    $sortfld = $sort[$parms['order']] or $sort['forward'];
    
    $catg = array(
      'pending'  => array('query' => 1,
                          'where' => 'start > now() - interval 1 day and',
                          'order' => $sortfld),
      'recent'   => array('query' => 1,
                          'where' => 'start between now() - interval 3 month and now() + interval 1 day and',
                          'order' => $sortfld),
      'year'     => array('query' => 1,
                          'where' => "year(start) = '%s' and",
                          'order' => $sortfld),
      'all'      => array('query' => 1,
                          'order' => $sortfld));

    //$GLOBALS['query_debug'] = true;

    if ($parms['cat'] > date("Y") - 10 && $parms['cat'] < date("Y") + 10) {
      $year = $parms['cat'];
      $parms['cat'] = 'year';
      $catg['year']['where'] = sprintf($catg['year']['where'],$year);
    }
    $category = $catg[$parms['cat']] or $catg['all'];

    switch ($category['query']) {
      case 1:
        return do_query("
          select
            *
          from
            $table
          where
            {$category['where']}
            deleted = 0
          {$category['order']}");
      case 2:
        return do_query("
          select
            l.*
          from
            $table l
          join
             (select email,group_name,count(*)
                from $table
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
            $table l
          having
            last_change > timestampadd(day,-7,now())
          order by
            last_change desc");
    }
  }
  
  function retrieve_item_years($table) {
    return do_query("
      select distinct
        year(start) as year
      from
        $table
      where
        year(start) <> 0 and
        deleted = 0
      order by
        year(start) desc");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        *,
        inet_ntoa(opted_out_ip) as o_o_ip,
        inet_ntoa(register_ip) as o_i_ip
      from
        $table
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
        into $table
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
         $table
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