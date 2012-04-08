<?php

  global $_URL;

  $table = 'client';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else
    items_list($_URL[2]);

  function items_list($node) {

?>
      <h1>Site <?=ucwords("{$node}");?>s</h1>
      <table class='edit_list'>
        <tr>
          <td colspan='6'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th style='width:200px;'>
            Company, Site, Short name
          </th>
          <th style='width:200px;'>
            Contact(s)
          </th>
          <th style='width:120px;'>
            CMS, Payment processor
          </th>
          <th style='width:165px;'>
            Dates
          </th>
          <th style='width:50px;'>
            &nbsp;
          </th>
        </tr>
<?

    $items = retrieve_item_list();
    
    $dates = array(
             'since',
             'created',
             'updated');
    $date_code = "
          <label>%s</label> %s";

    while ($item = $items->fetch_array()) {
      $date_block = array();
      foreach ($dates as $date) {
        if (is_dbdate($item[$date]))
          $date_block[] = sprintf($date_code,$date,dbdate_show($item[$date]));
      }

?>
        <tr>
          <td>
            <a href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'><?=$item['name'];?></a><br />
            <a href='http://<?=$item['domain'];?>'><?=$item['domain'];?></a><br/>
            <?=$item['short_name'];?>
          </td>
          <td>
            <?=$item['person'];?><br />
            <?=$item['phone'];?><br />
            <a href='mailto:<?=$item['email'];?>'><?=$item['email'];?></a>
          </td>
          <td>
            <?=$item['cms_name'];?><br />
            <?=$item['pmt_name'];?>
          </td>
          <td style='text-align:center'>
            <?=implode('<br />',$date_block);?>
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

    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['name']} $node".TITLE_SUFFIX;

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

    $option_code = "
              <option value=\"%s\" %s>%s</option>";

    $cms_opts  = get_status_opts(1,$item['cms'],$option_code);
    $pmt_opts  = get_status_opts(6,$item['payment_proc'],$option_code);

    $date_code = "
           <li>
             <label>
               %s:
             </label>
             %s
           </li>";
    $date_block = array(
         "since"      => "Since",
         "created"    => "Created",
         "updated"    => "Updated",
         "deleted"    => "Deleted",);
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld]))
        $date_data[] = sprintf($date_code,$label,dbdate_show($item[$fld],true));
    }
    if (!isset($date_data)) $date_data[] = sprintf($date_code,"&nbsp;","&nbsp;");

    form_v($item);
    $tbi = 1;

?>

      <form id='update_client' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords("$action $node");?></h2>
        <div class='field_input'>
          <div>
            <label for="name">Client name:</label>
            <input id="name" name="name" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['name'];?>" />
          </div>
          <div>
            <label for="short_name">Short name:</label>
            <input id="short_name" name="short_name" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['short_name'];?>" />
          </div>
          <div>
            <label for="domain">Domain:</label>
            <input id="domain" name="domain" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['domain'];?>" />
          </div>
          <div>
            <label for="person">Person:</label>
            <input id="person" name="person" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['person'];?>" />
          </div>
          <div>
            <label for="email">Email:</label>
            <input id="email" name="email" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['email'];?>" />
          </div>
          <div>
            <label for="phone">phone:</label>
            <input id="phone" name="phone" type="text" tabindex='<?=$tbi++;?>'
                style="width:250px;" value="<?=$item['phone'];?>" />
          </div>
          <div>
            <label for="other_contact">Other contact info:</label>
            <textarea id="other_contact" name="other_contact" tabindex='<?=$tbi++;?>' cols='80' rows='5'
                style="width:400px;height:5.5em;"><?=$item['other_contact'];?></textarea>
          </div>
          <div>
            <label for='cms'>CMS:</label>
            <select id='cms' name="cms" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$cms_opts);?>

            </select>
            <input id="cms_new" name="cms_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['cms_new'];?>" />
          </div>
          <div>
            <label for='payment_proc'>Payment processor:</label>
            <select id='payment_proc' name="payment_proc" style="width:125px;" tabindex='<?=$tbi++;?>'><?=implode('',$pmt_opts);?>

            </select>
            <input id="pmt_new" name="pmt_new" type="text" tabindex='<?=$tbi++;?>'
                style="width:125px;" value="<?=$item['pmt_new'];?>" />
          </div>
          <div>
            <label for="status_general">General status:</label>
            <textarea id="status_general" name="status_general" tabindex='<?=$tbi++;?>' cols='80' rows='5'
                style="width:400px;height:2.8em;"><?=$item['status_general'];?></textarea>
          </div>
          <div>
            <label for="notes">Notes:</label> &nbsp;
          </div>
        </div>
        <textarea id="notes" name="notes" tabindex='<?=$tbi++;?>' cols='80' rows='5'
                style="width:700px;height:15em;"><?=$item['notes'];?></textarea>
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

  function retrieve_item_list() {
    return do_query("
      select
        c.*,
        cms.name as cms_name,
        pmt.name as pmt_name
      from
        client c
      left join
        status cms
          on c.cms = cms.id
      left join
        status pmt
          on c.payment_proc = pmt.id
      where
        c.deleted = 0
      order by
        c.name,
        c.domain");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        *
      from
        client
      where
        id = '$id'");
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
    add_slashes($_POST);
    if ($_POST['cms_new'] <> '')
      $_POST['cms'] = insert_status(1,$_POST['cms_new']);

    if ($_POST['pmt_new'] <> '')
      $_POST['payment_proc'] = insert_status(6,$_POST['pmt_new']);

    do_query("
      insert
        into client
         (name,
          short_name,
          domain,
          person,
          email,
          phone,
          other_contact,
          cms,
          payment_proc,
          status_general,
          notes,
          created)
        values
         ('{$_POST['name']}',
          '{$_POST['short_name']}',
          '{$_POST['domain']}',
          '{$_POST['person']}',
          '{$_POST['email']}',
          '{$_POST['phone']}',
          '{$_POST['other_contact']}',
          '{$_POST['cms']}',
          '{$_POST['payment_proc']}',
          '{$_POST['status_general']}',
          '{$_POST['notes']}',
          now())");
  }

  function update_item($id) {
    add_slashes($_POST);
    if ($_POST['cms_new'] <> '')
      $_POST['cms'] = insert_status(1,$_POST['cms_new']);

    if ($_POST['pmt_new'] <> '')
      $_POST['payment_proc'] = insert_status(6,$_POST['pmt_new']);

    do_query("
      update
        client
      set
        name       = '{$_POST['name']}',
        short_name = '{$_POST['short_name']}',
        domain     = '{$_POST['domain']}',
        person     = '{$_POST['person']}',
        email      = '{$_POST['email']}',
        phone      = '{$_POST['phone']}',
        other_contact  = '{$_POST['other_contact']}',
        cms        = '{$_POST['cms']}',
        payment_proc   = '{$_POST['payment_proc']}',
        status_general = '{$_POST['status_general']}',
        notes      = '{$_POST['notes']}',
        updated    = now()
      where
        id='$id'");
  }

?>
