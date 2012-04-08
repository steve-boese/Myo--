<?php
  global $_URL;
  
  $table = 'product';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");

  } else if (isset($_URL[2]) && $_URL[2] != "") {
    item_form($_URL[1],$_URL[2]);

  } else
    items_list($_URL[1]);


  function items_list($node) {

?>
      <div class='product_list'>
<?

    $items = retrieve_item_list();

    $dupes = array();
    $dupelist = retrieve_dupe_addresses();
    while ($dupes = mysql_fetch_object ($dupelist))
      $dupe[$dupes->tag] = " dupe";

    while ($item = mysql_fetch_array($items)) {

      $thumbnail = ($item['thumbnail'] == '' || !file_exists(ABSPATH.$item['thumbnail']))
                 ? 'site/image/layout/thumbnail_placeholder.jpg' : $item['thumbnail'];

?>
        <div class='product_thumb'>
          <a href='/<?=$node;?>/<?=$item['tag'];?>/'>
            <img alt='<?=$item['menu_name'];?>' src='/<?=$thumbnail;?>' /><br/>
            <?=$item['menu_name'];?>
            
          </a>
        </div>
<?  }  ?>
      </div>

<?}

  function item_form($node,$tag) {

    $item = retrieve_item($tag);

?>

      <?=paypal_cart_button();?>
      <h2><?=$item['name'];?></h2>
      <div class='product_desc'><?=$item['description'];?>
      
      </div>
      <div class='product_vars'>
        <?=product_var_forms($item['id'],$item);?>
      </div><?=paypal_cart_button();?>

<?
}

  function product_var_forms($id,$item) {

    //echo "<h2>product_var_forms $id</h2>";
    $attribs = retrieve_attributes();
    $details = retrieve_product_details($id);
    $dtl_text = "
            <div class='detail_desc'><br />
              %s
            </div>";
    $dtl_radio = "
              <input type='radio' name='os%d' id='%s_%d_%d' value='%s' />
              <label for='%s_%d_%d'>%s</label>";
    $dtl_radio_box = "
            <div class='detail_radio'>
              <h3>%s</h3>
              <input type='hidden' name='on%d' value='%s' />%s
            </div>";
    $dtl_price = "
            <div class='detail_price'>
              <h3>Price</h3>
              $%1.02f
            </div>";
    $dtl_quantity = "
            <div class='detail_qty'>
              <h3>Quantity</h3>
              <input name='quantity' value='1' style='width:50px;text-align:right;' />
            </div>";
    $dtl_button = "
            <div class='detail_button'><br />
              <input type='submit' value='Add To Cart' />
              <input type='hidden' name='item_name' value='%s - %s' />
              <input type='hidden' name='amount' value='%1.02f' />
              <input type='hidden' name='cmd' value='_cart' />
              <input type='hidden' name='business' value='' />
              <input type='hidden' name='no_note' value='0' />
              <input type='hidden' name='currency_code' value='USD' />
              <input type='hidden' name='add' value='1' />
            </div>";

    $tbi = 1;

    //echo "<pre>".print_r($attribs,true)."</pre>";
    foreach ($details[$id] as $det) {
      //echo "<h1>{$det['description']}</h1>";
      //echo "<pre>".print_r($det,true)."</pre>";
      if ($det['attributes'] != '') {
        $a = explode(',',$det['attributes']);
        $j = 0;
        //echo "<h3>a:</h3>";
        //echo "<pre>".print_r($a,true)."</pre>";
        foreach ($a as $att) {
          $i = 1;
          $name = str_replace(' ','',$attribs[$att]['name']);
          //echo "<h3>att: $att</h3>";
          foreach ($attribs[$att]['vals'] as $attr) {
            $radio[] = sprintf($dtl_radio,$j,$name,$det['id'],$i,$attr,$name,$det['id'],$i,$attr);
            $i++;
          }
          $radiobox .= sprintf($dtl_radio_box,$attribs[$att]['name'],$j,$name,implode('<br />',$radio));
          $radio = array();
          $j++;
        }
      }
      $dtl_row =
        sprintf($dtl_text,$det['description']).
        $radiobox.
        sprintf($dtl_price,$det['price']).
        $dtl_quantity.
        sprintf($dtl_button,$item['name'],$det['description'],$det['price']);
      $dtl_rows .= "
          <form class='prod_dtl' id='prod_dtl_{$det['id']}' target='paypal' action='https://www.paypal.com/cgi-bin/webscr' method='post'>$dtl_row
          </form>";
      $i++;
      $radiobox = '';
    }
    
    return $dtl_rows;
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
          "id"          => $detail->id,
          "description" => $detail->description,
          "price"       => $detail->price,
          "attributes"  => $detail->attributes);
    for ($i=1; $i<=$blanks; $i++)
      $detl[$product][] =
        array(
          "id"          => 0,
          "description" => '',
          "price"       => '',
          "attributes"  => '');
    return $detl;
  }

  function retrieve_item($tag) {
    $item = do_query("
      select
        p.*
      from
        product p
      where
        p.deleted = 0 and
        p.tag = '$tag'");
    if (mysql_num_rows($item) != 1)
      exit("Error: Product $tag not found");
    return
      mysql_fetch_array($item);
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
    $attrib = do_query("
      select
        *
      from
        product_attribute
      where
        deleted = 0");
        
    while ($att = mysql_fetch_object($attrib))
      $attr[$att->id] = array(
        'name' => $att->display_name,
        'vals' => explode(',',$att->value_list));

    return $attr;
  }

  function paypal_cart_button() {
?>

      <form class='paypal_cart' target='paypal' action='https://www.paypal.com/cgi-bin/webscr' method='post'>
        <input type='hidden' name='cmd' value='_cart'>
        <input type='hidden' name='business' value=''>
        <input type='image' src='https://www.paypal.com/images/view_cart.gif' border='0' name='submit' alt='Make payments with PayPal - it's fast, free and secure!'>
        <input type='hidden' name='display' value='1'>
      </form>
<?
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