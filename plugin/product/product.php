<?php
  global $_URL;

  $table = 'product';

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
      <table class='edit_list product_admin'>
        <tr>
          <td colspan='7'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th class='arrows'>
            &nbsp;
          </th>
          <th style='width:150px;'>
            Product
          </th>
          <th style='width:80px;'>
            Tag
          </th>
          <th style='width:80px;'>
            Status
          </th>
          <th>
            Thumbnail
          </th>
          <th>
            Controls
          </th>
        </tr>
<?

    $items = retrieve_item_list();
    $details = retrieve_product_details();

    foreach ($details as $f => $prod) {
      foreach ($prod as $detl)
        $det[] = sprintf("%s &mdash; \$%10.2f",$detl['description'],$detl['price']);
      $detail[$f] = implode('<br />',$det);
      $det = array();
    }
    
    $dupes = array();
    $dupelist = retrieve_dupe_addresses();
    while ($dupes = mysql_fetch_object ($dupelist))
      $dupe[$dupes->tag] = " dupe";

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

      $id = $item['id'];$main_arrows = '';$sub_arrows = '';$name_class = array($item['status']);
      if (count($name_class) > 0) $name_class = 'class="'.implode(' ',$name_class).'"'; else $name_class = '';
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
      
      $thumbnail = ($item['thumbnail'] == '' || !file_exists(ABSPATH.$item['thumbnail']))
                 ? 'site/image/layout/thumbnail_placeholder.jpg' : $item['thumbnail'];

?>
        <tr class='<?=$item['status'].$dupe[$item['tag']];?>'>
          <td class='arrows'>
            <?=$main_arrows;?>
          </td><?=$title;?>

          <td>
            <?=$item['tag'];?>
          </td>
          <td style='text-align:center;'>
            <?=$item['status'];?>
          </td>
          <td style='text-align:center;' rowspan='2'>
            <img alt='' src='/<?=$thumbnail;?>' style='width:150px;' />
          </td>
          <td>
            <a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a><br/>
          </td>
        </tr>
        <tr>
          <td>
            &nbsp;
          </td>
          <td colspan="3" style='text-align:center;'>
            <?=(isset($detail[$item['id']])) ? $detail[$item['id']] : '&nbsp;';?>
          </td>
          <td>
            &nbsp;
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
    $display_after_opts = sprintf($option_code,'0',$slctd,'-- ','Put this product at the top','--');
    mysql_data_seek($other_pages,0);
    while ($other = mysql_fetch_array($other_pages)) {
      list($slctd,$prefix,$suffix) = array('','','');
      if ($other['id'] == $display_after)
        $slctd = 'selected="selected"';
      if ($other['indent'] == "1")
        $prefix = '--';
      if ($other['status'] <> "active")
        $suffix = " ({$other['status']})";
      $display_after_opts .= sprintf($option_code,
          $other['id'],$slctd,$prefix,form_v($other['menu_name']),$suffix);
    }

    $option_code = "
              <option value='%s' %s>%s</option>";

    $statuses = array('active','inactive');
    if (!in_array($item['status'],$statuses)) $item['status'] = $statuses[0];
    foreach($statuses as $stat) {
      $slctd = '';
      if ($stat == $item['status'])
        $slctd = 'selected="selected"';
      $status_opts .= sprintf($option_code,$stat,$slctd,$stat);
    }

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));

    if ($item['member_of'] > 0)
      $is_sub_page = 1;
    else
      $is_sub_page = 0;

    $checkbox_code = "
            <input type='checkbox' id='attrib_%d' name='attrib[%d]' value='%d' %s tabindex='%%d'>
            <label for='attrib_%d' class='checkbox'>%s</label><br />";
    $the_att = explode(',',$item['attributes']);
    $attribs = retrieve_attributes();
    while ($attrib = mysql_fetch_object($attribs)) {
      $a_id = $attrib->id;
      $selected = (in_array($a_id,$the_att)) ? 'checked="checked"' : '';
      $attrib_opts[] = sprintf($checkbox_code,$a_id,$a_id,$a_id,$selected,$a_id,$attrib->name);
    }
    
    $thumbnail = ($item['thumbnail'] == '' || !file_exists(ABSPATH.$item['thumbnail']))
               ? 'site/image/layout/thumbnail_placeholder.jpg' : $item['thumbnail'];
    
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

    $this_description = htmlspecialchars($item['description']);
    form_v($item);
    $tbi = 1;

    include(ABSPATH . "admin/page_address_script.php");

?>

      <form id='update_product' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div id='thumbnail_div'>
            <img id='thumbnail_img' alt='' src='/<?=$thumbnail;?>' />
          </div>
          <div>
            <label for="name">Product:</label>
            <input id="name" name="name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['name'];?>" />
          </div>
          <div>
            <label for="tag">Tag:</label>
            <input id="tag" name="tag" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['tag'];?>" onkeyup="cleanupName('tag');" />
          </div>
          <div>
            <label for='thumbnail'>Thumbnail:</label>
            <input id="thumbnail" name="thumbnail" type="text" tabindex='<?=$tbi++;?>'
              style="width:200px;" value="<?=$item['thumbnail'];?>" />
            <input type='button' value='Browse Server' id='browsebtn' onclick='fire_KFM();' />
            <script>
              function fire_KFM() {
  			          window.SetUrl=(function(){
  				          return function(value){
                      if ((value.substr,0,5) != '/site')
                        value = value.substr(value.search('/site'));
                      segs = value.split('/');
                      file = segs.pop();
                      if (file.search(/[.]/) < 1)
                        value = value.concat('.jpg');
  					          document.getElementById('thumbnail').value = value;
  					          document.getElementById('thumbnail_img').src = value;
  				          }
  			          })(this.id);
                  var kfm_url='/kfm/index.php';
  			          var kfmwindow = window.open(kfm_url,'kfm','modal,width=800,height=400');
  			          kfmwindow.focus();
              };
            </script>
          </div>
          <div>
            <label for='display_after'>Display after:</label>
            <select id='display_after' name="display_after" tabindex='<?=$tbi++;?>'><?=$display_after_opts;?>

            </select>
            <input id="display_after_orig" name="display_after_orig" type="hidden" value="<?=$display_after;?>" />
          </div>
          <div>
            <label for='status'>Status:</label>
            <select id='status' name="status" tabindex='<?=$tbi++;?>'><?=$status_opts;?>

            </select>
          </div>
        </div>
        <div class='wysiwyg'>
          <textarea name='description' id='description' rows='5' cols='80'
            tabindex='<?=$tbi++;?>'><?=$this_description;?></textarea>
          <script type='text/javascript'>
            CKEDITOR.replace('description',
              {
                customConfig : '/admin/ckeditor/config.php',
                toolbar : 'Page'
              });
          </script>
        </div>
        <div id='product_var'>
          <?=product_var_form($id,$tbi);?>
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

  function product_var_form($id,&$tbi) {

    $details = retrieve_product_details($id,5);

    $i = 1;
    $attributes = retrieve_attributes();
    while ($attrib = mysql_fetch_object($attributes)) {
      $att[] = array("id" => $attrib->id,"name" => $attrib->name);
      $att_hdr .= "
                    <th>
                      $i
                    </th>";
      $att_lgd .= "
                    <li>$attrib->name</li>";
      $i++;
    }
    
    $dtl_text_input = "
                    <td>
                      <input type='text' name='%s[%d]' value='%s'
                             style='width:%s;%s' tabindex='%d'/>
                    </td>";
    $dtl_checkbox = "
                    <td>
                      <input type='checkbox' name='attrib[%d][%d]' value='%d' %s tabindex='%d'/>
                    </td>";
    $i = 1;
    foreach ($details[$id] as $det) {
      $dtl_row =
        sprintf($dtl_text_input,'desc',$i,$det['description'],'200px','',$tbi++).
        sprintf($dtl_text_input,'price',$i,$det['price'],'60px','text-align:right;',$tbi++);
      $selected = explode(',',$det['attributes']);
      foreach ($att as $a) {
        $slctd = (in_array($a['id'],$selected)) ? 'checked="checked"' : '';
        $dtl_row .= sprintf($dtl_checkbox,$i,$a['id'],$a['id'],$slctd,$tbi++);
      }
      $dtl_rows .= "
                  <tr>$dtl_row
                  </tr>";
      $i++;
    }

?>

          <table>
            <tr>
              <td>
                <h2>Product Variations</h2>
              </td>
              <td>
                &nbsp;
              </td>
            </tr>
            <tr>
              <td>
                <table>
                  <tr>
                    <td colspan="2">
                      &nbsp;
                    </td>
                    <th colspan="<?=count($att);?>">
                      Option Types
                    </th>
                  </tr>
                  <tr>
                    <th>
                      Description
                    </th>
                    <th>
                      Price
                    </th><?=$att_hdr;?>

                  </tr><?=$dtl_rows;?>
                  
                  </table>
                </td>
                <td id='attrib_legend'>
                  <h3>Option Types</h3>
                  <ol><?=$att_lgd;?>
                  
                  </ol>
                </td>
              </tr>
            </table>
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
        p.tag,
        p.status,
        p.thumbnail
      from
        product p
      where
        p.deleted = 0 and
        p.member_of = 0 $pagelimits

      union

      select
        p.id as id,
        p.seq,
        1 as indent,
        p.name as menu_name,
        p.tag,
        p.status,
        p.thumbnail
      from
        product p
      join
        product m
          on p.member_of = m.id
      where
        p.member_of > 0 and
        p.deleted = 0 and
        m.deleted = 0 $pagelimits

      order by
        seq");
  }
  
  function retrieve_product_details($product = 0,$blanks = 0) {
    $where   = ($product > 0) ? "product = '$product' and " : '';
    $details = do_query("
      select
        *
      from
        product_detail
      where $where
        deleted = 0
      order by
        product,
        seq");
    while ($detail = mysql_fetch_object($details))
      $detl[$detail->product][] =
        array(
          "description" => $detail->description,
          "price"       => $detail->price,
          "attributes"  => $detail->attributes);
    for ($i=1; $i<=$blanks; $i++)
      $detl[$product][] =
        array(
          "description" => '',
          "price"       => '',
          "attributes"  => '');
    return $detl;
  }

  function retrieve_item($id) {
    return mysql_fetch_array(do_query("
      select
        p.*
      from
        product p
      where
        p.id = '$id'"));
  }

  function retrieve_dupe_addresses() {
    return do_query("
      select
        p.tag,
        count(*)
      from
        product p
      left join
        product m
          on p.member_of = m.id
      where
        p.deleted = 0 and
        p.status <> 'inactive' and
        ifnull(m.deleted,0) <> 0
      group by
        p.tag
      having
        count(*) > 1");
  }
  
  function retrieve_attributes() {
    return do_query("
      select
        *
      from
        product_attribute
      where
        deleted = 0
      order by
        seq");
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
    
    $_POST['thumbnail'] = (substr($_POST['thumbnail'],0,1) == '/')
                        ?  substr($_POST['thumbnail'],1) : $_POST['thumbnail'];

    add_slashes($_POST);

    do_query("
      insert
        into product
          (name,
           tag,
           thumbnail,
           seq,
           member_of,
           status,
           description,
           created)
         values
          ('{$_POST['name']}',
           '{$_POST['tag']}',
           '{$_POST['thumbnail']}',
           '$disp_order',
           '$sub_of',
           '{$_POST['status']}',
           '{$_POST['description']}',
           now())");
           
    $id = mysql_insert_id();

    resequence_table($table);

    update_product_details($id);

    resize_thumbnail($id,$_POST['thumbnail']);
  }

  function update_item($table,$id) {

    //echo "<div style='text-align:left;'><pre>".print_r($_POST,true)."</pre></div>";
    //$GLOBALS['query_debug'] = true;
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

    $_POST['thumbnail'] = (substr($_POST['thumbnail'],0,1) == '/')
                        ?  substr($_POST['thumbnail'],1) : $_POST['thumbnail'];

    add_slashes($_POST);

    do_query("
      update
        product
      set
        name         = '{$_POST['name']}',
        tag          = '{$_POST['tag']}',
        thumbnail    = '{$_POST['thumbnail']}',
        seq          = '$disp_order',
        member_of    = '$sub_of',
        status       = '{$_POST['status']}',
        description  = '{$_POST['description']}',
        updated       = now()
      where
        id='$id'");
        
    resequence_table($table);
    
    update_product_details($id);
    
    resize_thumbnail($id,$_POST['thumbnail']);
  }

  function update_product_details($id) {

    do_query("
      update
        product_detail
      set
        deleted = now()
      where
        deleted = 0 and
        product = '$id'");

    for ($i=1; $i<=count($_POST['price']); $i++) {
      if ($_POST['price'][$i] > 0)
        do_query("
          insert into
            product_detail
             (product,
              description,
              price,
              attributes,
              seq,
              created)
            values
             ('$id',
              '{$_POST['desc'][$i]}',
              '{$_POST['price'][$i]}',
              '".@implode(',',$_POST['attrib'][$i])."',
              '$i',
              now())");
    }
  }
  
  function resize_thumbnail ($id,$thumbnail,$toWidth = 150,$toHeight = 120) {

    if ($thumbnail == '' || !file_exists(ABSPATH . $thumbnail))
      return;

    list($width,$height) = getimagesize(ABSPATH . $thumbnail);
    if ($width < 152 && $height < 122)
      return;

    $xscale=$width/$toWidth;
    $yscale=$height/$toHeight;

    if ($yscale > $xscale) {
      $new_width  = round($width  * (1/$yscale));
      $new_height = round($height * (1/$yscale));
    } else {
      $new_width  = round($width  * (1/$xscale));
      $new_height = round($height * (1/$xscale));
    }

    $imageResized = imagecreatetruecolor($new_width, $new_height);
    $imageTmp     = imagecreatefromjpeg (ABSPATH . $thumbnail);
    imagecopyresampled($imageResized, $imageTmp, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    $new_thumb = explode('.',$thumbnail);
    $new_thumb[count($new_thumb)-2] .= '_thumb';
    $new_thumb = implode('.',$new_thumb);
    
    imagejpeg($imageResized,ABSPATH.$new_thumb,75);
    
    do_query("
      update
        product
      set
        thumbnail    = '$new_thumb'
      where
        id='$id'");

  }

?>