<?php
  global $_URL;

  //echo "<pre>".print_r($_URL,true)."</pre>";exit;

  $table = 'austin_customer';

  if (count($_GET) > 0 && count($_POST) > 0) {
    list($GLOBALS['next_url'],$parms) = explode('?',_URI_);
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: {$GLOBALS['next_url']}");

  } else if (isset($_URL[3]) && $_URL[3] == "search") {
    $parms = array();
    for ($i=4; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = urldecode($parm[1]);
      }
    }
    search_results($_URL[2],$_URL[3],$parms);

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    $parms = array();
    for ($i=5; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = $parm[1];
      }
    }
    item_form($_URL[2],$_URL[3],$_URL[4],$parms);

  } else
    items_list($_URL[2]);


  function search_results($node,$action,$parms) {

    $GLOBALS['pageInfo']['title'] = ucwords($action)." $node".TITLE_SUFFIX;
    $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/customer_edit.css,/site/style/search_result.css";
    $GLOBALS['pageInfo']['scripts'] .= "\n/site/script/customer_edit.js";

    echo "<h2>Search Customers &amp; Cars</h2>";

    search_form($parms);
    
    if (count($parms) == 0)
      return;

    $max_cust = 100;
    $max_cars = 3;
    
    if (isset($parms['cust']) && isset($parms['car']))
      search_results_cust_and_car($max_cust,$max_cars,$parms);
    else if (isset($parms['cust']))
      search_results_cust($max_cust,$max_cars,$parms['cust']);
    else
      search_results_cars($max_cust,$max_cars,$parms['car']);

  }
  
  function search_results_cust_and_car($max_cust,$max_cars,$parms) {

    $cust_list = retrieve_car_and_cust_matches($parms);
    echo search_message($cust_list->num_rows,$max_cust,'car');
    if ($cust_list->num_rows == 0)
      return;

    $nbr = 0;
    while (($cust = $cust_list->fetch_object()) && $nbr <= $max_cust) {
      $car_ids[] = $cust->id;
      $car_data[$cust->cust_id][] = $cust;
      $nbr++;
    }
    $cust_list->data_seek(0);

    $repairs = retrieve_repair_last_for_cars($car_ids);
    while ($rpr = $repairs->fetch_object()) {
      $repair[$rpr->car_id] =
        date('M Y',strtotime($rpr->date_completed)).": ".
        ucwords(strtolower($rpr->repairs));
    }

    $nbr = 0;
    while (($car = $cust_list->fetch_object()) && $nbr <= $max_cust)
      $repair_data[$car->cust_id][] = $repair[$car->id];
    $cust_list->data_seek(0);

    generate_search_results($cust_list,$car_data,$repair_data,$max_cust);

  }

  function search_results_cust($max_cust,$max_cars,$search_for) {

    $cust_list = retrieve_cust_matches($search_for);
    echo search_message($cust_list->num_rows,$max_cust,'customer');
    if ($cust_list->num_rows == 0)
      return;

    $nbr = 0;
    while (($cust = $cust_list->fetch_object()) && $nbr <= $max_cust) {
      $cust_ids[] = $cust->cust_id;
      $nbr++;
    }
    $cust_list->data_seek(0);
    
    $cars = retrieve_cust_top_cars($cust_ids,$max_cars);

    if ($cars->num_rows > 0) {
      while ($car = $cars->fetch_object()) {
        $car_ids[] = $car->id;
      }
      $cars->data_seek(0);

      $repairs = retrieve_repair_last_for_cars($car_ids);
      while ($rpr = $repairs->fetch_object()) {
        $repair[$rpr->car_id] =
          date('M Y',strtotime($rpr->date_completed)).": ".
          ucwords(strtolower($rpr->repairs));
      }
    }

    while ($car = $cars->fetch_object()) {
      $car_data[$car->cust_id][] = $car;
      $repair_data[$car->cust_id][] = $repair[$car->id];
    }

    generate_search_results($cust_list,$car_data,$repair_data,$max_cust);

  }

  function search_results_cars($max_cust,$max_cars,$search_for) {

    $cust_list = retrieve_car_matches($search_for);
    echo search_message($cust_list->num_rows,$max_cust,'car');
    if ($cust_list->num_rows == 0)
      return;

    $nbr = 0;
    while (($cust = $cust_list->fetch_object()) && $nbr <= $max_cust) {
      $car_ids[] = $cust->id;
      $car_data[$cust->cust_id][] = $cust;
      $nbr++;
    }
    $cust_list->data_seek(0);
    
    $repairs = retrieve_repair_last_for_cars($car_ids);
    while ($rpr = $repairs->fetch_object()) {
      $repair[$rpr->car_id] =
        date('M Y',strtotime($rpr->date_completed)).": ".
        ucwords(strtolower($rpr->repairs));
    }

    $nbr = 0;
    while (($car = $cust_list->fetch_object()) && $nbr <= $max_cust)
      $repair_data[$car->cust_id][] = $repair[$car->id];
    $cust_list->data_seek(0);

    generate_search_results($cust_list,$car_data,$repair_data,$max_cust);

  }

  function search_message($num_rows,$max_rows,$label) {
    if ($num_rows == 0)
      $search_message = "No {$label}s found";
    else if ($num_rows > $max_rows)
      $search_message = "<strong>".number_format($num_rows)."</strong> {$label}s found &mdash; first $max_rows shown";
    else if ($num_rows == 1)
      $search_message = "<strong>One</strong> {$label} found";
    else
      $search_message = "<strong>$num_rows</strong> {$label}s found";
    return "
      <div id='search_count'>
        $search_message
      </div>";
  }

  function items_list($node) {

    $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/customer_edit.css,/site/style/search_result.css";
    $max_cust = 100;
    $max_cars = 3;
    $days = 14;

    search_form();

    echo "<h2 class='clear'>Customers</h2>";

    $cust_list = retrieve_cust_recent_updates($days);
    if ($cust_list->num_rows == 0)
      return;
      
    echo "<h4>Updates in past $days days</h4>";
    $nbr = 0;
    while (($cust = $cust_list->fetch_object()) && $nbr <= $max_cust) {
      $cust_ids[] = $cust->cust_id;
      $nbr++;
    }
    $cust_list->data_seek(0);

    $cars = retrieve_cust_top_cars($cust_ids,$max_cars);

    if ($cars->num_rows > 0) {
      while ($car = $cars->fetch_object()) {
        $car_ids[] = $car->id;
      }
      $cars->data_seek(0);

      $repairs = retrieve_repair_last_for_cars($car_ids);
      while ($rpr = $repairs->fetch_object()) {
        $repair[$rpr->car_id] =
          date('M Y',strtotime($rpr->date_completed)).": ".
          ucwords(strtolower($rpr->repairs));
      }
    }

    while ($car = $cars->fetch_object()) {
      $car_data[$car->cust_id][] = $car;
      $repair_data[$car->cust_id][] = $repair[$car->id];
    }

    generate_search_results($cust_list,$car_data,$repair_data,$max_cust);


  }

  function item_form($node,$action,$id,$parms) {

    //echo "<pre>".print_r($parms,true)."</pre>";exit;

    $actions = array(
       "add"      => "Add ".ucwords($node),
       "edit"     => "Update ".ucwords($node),
       "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    if (isset($parms['car']) && isset($parms['rprdelete']))
      $repair_message = delete_repair($node,$id,$parms['car'],$parms['rprdelete']);

    $item = array();
    if ($id > 0)
      list($item,$cars,$repairs) = retrieve_item($id,$parms);

    if (is_object($repairs)) {
      while ($rpr = $repairs->fetch_array())
        $revenue += $rpr['cost'];
      $repairs->data_seek(0);
    }
    
    search_form();

    customer_form($node,$action,$id,$parms,$actions,$item,$cars,$revenue);

    cars_form($node,$action,$id,$item,$cars,$parms);
    
    if (is_object($cars))
      repairs_form($node,$action,$id,$parms,$item,$cars,$repairs,$repair_message);

  }
  
  function search_form($parms = array()) {

    foreach ($parms as $type=>$search_for) {
      if (strpos($search_for,' ') > 0)
        $value[$type] = $search_for;
      else
        $value[$type] = str_replace(',',' ',$search_for);
    }
?>

      <form id='search_form' method='post' action='/admin/customer/search/?form=search'>
        <h3>Search</h3>
        <div>
          <label for="search_for_cust">Customer:</label>
          <input id="search_for_cust" name="search_for_cust" type="text" value="<?=$value['cust'];?>" />
        </div>
        <div>
          <label for="search_for_car">Car:</label>
          <input id="search_for_car" name="search_for_car" type="text" value="<?=$value['car'];?>" />
          <button type='submit' name='go'>Go</button>
        </div>
      </form>
      <form id='add_customer' method='post' action='/admin/customer/add'>
        <button type='submit' name='add'>Add new customer</button>
      </form>

<?
  }

  function customer_form($node,$action,$id,$parms,$actions,$item,$cars,$revenue_total) {

    if (!(($action != "add" && isset($item['cust_id']))
       || ($action == "add" && !isset($item['cust_id']))))
      exit("<h1>Invalid action</h1>");

    $cust_name = ucwords(strtolower($item['name_full']));
    $GLOBALS['pageInfo']['title'] = ucwords($action)." $cust_name $node".TITLE_SUFFIX;
    $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/customer_edit.css";
    $GLOBALS['pageInfo']['scripts'] .= "\n/site/script/customer_edit.js";

    form_v($item);
    $tbi = 1;
    $repair_link = (!is_object($cars)) ? '' : "
        <a href='#update_repairs' class='skip_to' tabindex='".$tbi++."'>Skip to repairs</a>";
    if (isset($parms['rprdelete'])) {
      $formaction = explode('/',_URI_);
      array_pop($formaction);
      $formaction = implode('/',$formaction);
    } else
      $formaction = _URI_;

?>

      <form method='post' action='<?=$formaction;?>?form=cust' class="clear">
      <div id="update_cust" class='edit_item' ><?=$repair_link;?>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div class="block1">
            <div>
              <label for="name_first">First, last name:</label>
              <input id="name_first" name="cust[name_first]" type="text" tabindex='<?=$tbi++;?>'
                style="width:110px;" value="<?=$item['name_first'];?>" />
              <input id="name_last" name="cust[name_last]" type="text" tabindex='<?=$tbi++;?>'
                style="width:115px;" value="<?=$item['name_last'];?>" />
            </div>
            <div>
              <label for="name_spouse">Spouse name:</label>
              <input id="name_spouse" name="cust[name_spouse]" type="text" tabindex='<?=$tbi++;?>'
                style="width:230px;" value="<?=$item['name_spouse'];?>" />
            </div>
            <div>
              <label for="street">Street:</label>
              <textarea id="street" name="cust[street]" tabindex='<?=$tbi++;?>'
                style="width:230px;height:5em;" rows="3" cols="50"
                ><?=$item['street'];?></textarea>
            </div>
            <div>
              <label for="city">City, State, Zip:</label>
              <input id="city" name="cust[city]" type="text" tabindex='<?=$tbi++;?>'
                style="width:130px;" value="<?=$item['city'];?>" />
              <input id="state" name="cust[state]" type="text" tabindex='<?=$tbi++;?>'
                style="width:30px;" value="<?=$item['state'];?>" />
              <input id="zip" name="cust[zip]" type="text" tabindex='<?=$tbi++;?>'
                style="width:60px;" value="<?=$item['zip'];?>" />
            </div>
          </div>
          <div class="block2">
            <div>
              <label for="phone_work">Work:</label>
              <input id="phone_work" name="cust[phone_work]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['phone_work'];?>" />
            </div>
            <div>
              <label for="phone_home">Home:</label>
              <input id="phone_home" name="cust[phone_home]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['phone_home'];?>" />
            </div>
            <div>
              <label for="phone_cell">Cell:</label>
              <input id="phone_cell" name="cust[phone_cell]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['phone_cell'];?>" />
            </div>
            <div>
              <label for="phone_other">Other:</label>
              <input id="phone_other" name="cust[phone_other]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['phone_other'];?>" />
            </div>
            <div>
              <label for="email">Email(s):</label>
              <input id="email" name="cust[email]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['email'];?>" />
            </div>
            <div>
              <input id="email2" name="cust[email2]" type="text" tabindex='<?=$tbi++;?>'
                style="width:180px;" value="<?=$item['email2'];?>" />
            </div>
          </div>
          <div class="block3">
            <div>
              <label>Cust no:</label>
              <?="{$item['cust_id']}&nbsp;";?>
            </div>
            <div>
              <label>Orig cust no:</label>
              <?="{$item['cust_no_old']}&nbsp;";?>
            </div>
            <div>
              <label>Related cust:</label>
              <?="{$item['cust_related']}&nbsp;";?>
            </div>
            <div>
              <label>Created:</label>
              <?=dbdate_show($item['created']);?>&nbsp;
            </div>
            <div>
              <label>Updated:</label>
              <?=dbdate_show($item['updated']);?>&nbsp;
            </div>
            <div>
              <label>Revenue:</label>
              <?='$'.number_format($revenue_total,2);?>&nbsp;
            </div>
          </div>
          <div class="clear">
            <label for="notes">Notes:</label>
            <textarea id="notes" name="cust[notes]" tabindex='<?=$tbi++;?>'
              style="width:650px;height:6.0em;" rows="3" cols="250"
              ><?=$item['notes'];?></textarea>
          </div>
        </div>
      </div>

<?
    $GLOBALS['tabindex'] = $tbi;

  }

  function cars_form($node,$action,$id,$item,$cars,$parms) {

    $tbi = $GLOBALS['tabindex'];
    $col = array('model_year'=>'Year','make'=>'Make','model'=>'Model','color'=>'Color','plate'=>'Plate','car_no'=>'Car No.','vin'=>'VIN');
    if (isset($parms['car']))
      $all_cars_link = "
        <a href='/admin/$node/edit/$id/' class='skip_to'>Show all cars</a>";
    foreach ($col as $name=>$title) {
      $header .= "
                <input type=\"text\" readonly=\"readonly\" class=\"$name colhead\" value=\"$title\" />";
    }
    $header = "
              <div id=\"car_header\">$header
              </div>";
    $fldid = 1;
    $cols  = 7;
    $carct = 0;
    if (is_object($cars)) {
      while ($car = $cars->fetch_array()) {
        $carct++;
        $detail = "";
        foreach ($col as $name=>$title) {
          $detail .= "
            <input name=\"car[{$car['id']}][{$name}]\" id=\"carfld".$fldid."\" type=\"text\" tabindex='".$tbi++."'
              class=\"$name\" value=\"{$car[$name]}\" onkeydown=\"key_handler(event,'carfld',".$fldid++.",$cols);\" />";
        }
        $detail .= "
            <a href='"._URI_."/car:{$car['id']}/' class='car_info'>info".
              "<span class='hover'>".
                "<span class='title'>{$car['vehicle_desc']}</span>".
                "<span class='block'><span class='label'>First:</span>{$car['First']}&nbsp;</span>".
                "<span class='block'><span class='label'>Last:</span>{$car['Last']}&nbsp;</span>".
                "<span class='block'><span class='label'>Repairs:</span>{$car['Repairs']}&nbsp;</span>".
                "<span class='block'><span class='label'>Total:</span>$ ".number_format($car['Total'],2)."&nbsp;</span>".
                "<span class='block'><span class='label'>Odometer:</span>".number_format($car['Odometer'])."&nbsp;</span>".
              "</span>
            </a>";
        $details .= "
          <div>$detail
          </div>";
      }
    }
    $carct = ($carct > 0) ? sprintf(" (%d)",$carct) : "";
    for ($x=1; $x<=2; $x++) {
      $detail = "";
      foreach ($col as $name=>$title) {
        $detail .= "
            <input name=\"car[new_$x][{$name}]\" id=\"carfld".$fldid."\" type=\"text\" tabindex='".$tbi++."'
              class=\"$name\" onkeydown=\"key_handler(event,'carfld',".$fldid++.",$cols);\" />";
      }
      $details .= "
          <div$lastclass>$detail
          </div>";
      $lastclass = " class=\"last\"";
    }
    
    $GLOBALS['tabindex'] = $tbi;

?>

      <div id="update_cars" class="edit_item">
        <div class="field_input">
          <h3>Cars<?=$carct;?><?=$all_cars_link;?></h3>
          <div id="cars_head"><?=$header;?>

          </div>

          <?=$details;?>

        </div>
        <div class='buttons'>
          <button type='submit' name='enter' tabindex='<?=$tbi++;?>'>Update Customer &amp; Cars</button>
        </div>
      </div>
      </form>

<?

  }

  function repairs_form($node,$action,$id,$parms,$item,$cars,$repairs,$repair_message) {

    $tbi = $GLOBALS['tabindex'];
    $buttontbi = $tbi++;
    $col = array('date_completed'=>'Date','car_id'=>'Vehicle','cost'=>'Cost','invoice'=>'Invoice No.','odometer'=>'Odometer','odometer_note'=>'Note','car_no'=>'Old car no.');
    foreach ($col as $name=>$title) {
      $header .= "
                <input type=\"text\" readonly=\"readonly\" class=\"$name colhead\" value=\"$title\" />";
    }
    $header = "
              <div id=\"repair_header\">$header
              </div>";
    $carlist = array();
    if (is_object($cars)) {
      $sufflds = array('color','plate','car_no');
      $cars->data_seek(0);
      $carlist[] = '';
      while ($c = $cars->fetch_array()) {
        $suffix = array();
        if ($c['vehicle_desc'] <> '') {
          foreach ($sufflds as $fld) {
            if ($c[$fld] <> '')
              $suffix[] = $c[$fld];
          }
          $suffix = (count($suffix) > 0) ? " (".implode(', ',$suffix).")" : "";
          $carlist[$c['id']] = ucwords(strtolower($c['vehicle_desc'])).$suffix;
        }
        else
          $carlist[$c['id']] = $c['car_no'];
      }
    }
    $fldid = 1;
    $cols  = 8;
    for ($x=1; $x<=1; $x++) {
      $detail = "";
      foreach ($col as $name=>$title) {
        if ($name == "car_id") {
          $detail .= car_dropdown($carlist,'0',$tbi++,"new_$x",$fldid++);
        } else {
          $detail .= "
            <input name=\"rpr[new_$x][{$name}]\" id=\"rprfld".$fldid."\" type=\"text\" tabindex='".$tbi++."'
              class=\"$name\" onkeydown=\"key_handler(event,'rprfld',".$fldid++.",$cols);\" />";
        }
      }
      $detail .= "
          </div>
          <div>
            <textarea name=\"rpr[new_$x][repairs]\" id=\"rprfld".$fldid++."\" tabindex='".$tbi++."'
              class=\"repairs\" rows=\"3\" cols=\"80\"></textarea>";
      $details .= "
          <div$lastclass>$detail
          </div>";
      $lastclass = " class=\"last\"";
    }
    $rprct = 0;
    if (is_object($repairs)) {
      while (($rpr = $repairs->fetch_array()) && $rprct < 100) {
        //echo "<pre>".print_r($rpr,true)."</pre>";exit;
        $rprct++;
        if ($rprct > 100) break;
        $detail = "";
        foreach ($col as $name=>$title) {
          switch ($name) {
            case "cost":
              $value = number_format($rpr[$name],2);
              break 1;
            case "odometer":
              $value = number_format($rpr[$name]);
              break 1;
            default:
              $value = $rpr[$name];
          }
          if ($name == "car_id") {
            $detail .= car_dropdown($carlist,$rpr['car_id'],$tbi++,$rpr['id'],$fldid++);
          } else {
            $detail .= "
            <input name=\"rpr[{$rpr['id']}][{$name}]\" id=\"rprfld".$fldid."\" type=\"text\" tabindex='".$tbi++."'
              class=\"$name\" value=\"$value\" onkeydown=\"key_handler(event,'rprfld',".$fldid++.",$cols);\" />";
          }
        }
        $detail .= "
          </div>
          <div>
            <a href='/admin/$node/edit/$id/car:{$rpr['car_id']}/rprdelete:{$rpr['id']}' class='delete_repair'>delete</a>
            <textarea name=\"rpr[{$rpr['id']}][repairs]\" id=\"rprfld".$fldid++."\" tabindex='".$tbi++."'
              class=\"repairs\" rows=\"3\" cols=\"80\">{$rpr['repairs']}</textarea>";
      $details .= "
          <div>$detail
          </div>";
      }
    }
    $rprct = $repairs->num_rows;
    $rprct = ($rprct > 0) ? sprintf(" (%d)",$rprct) : "";
    if (isset($parms['rprdelete'])) {
      $formaction = explode('/',_URI_);
      array_pop($formaction);
      $formaction = implode('/',$formaction);
    } else
      $formaction = _URI_;

?>

      <?=$repair_message;?>
      <form method='post' action='<?=$formaction;?>?form=repairs'>
      <div id="update_repairs" class="edit_item">
        <a href="#update_cust" class="skip_to" tabindex='<?=$tbi++;?>'>Skip to customer</a>
        <div class="field_input">
          <div class='buttons'>
            <button type='submit' name='enter' tabindex='<?=$buttontbi;?>'>Update Repairs</button>
          </div>
          <h3>Repairs<?=$rprct;?></h3>
          <div><?=$header;?>

          </div>

          <?=$details;?>

        </div>
      </div>
      </form>

<?

  }

  function delete_repair($node,$custid,$carid,$repairid) {
    $repair = do_query("
      select
        r.*,
        c.desc_full
      from
        austin_repair r
      join
        austin_car c
          on r.car_id = c.id
      where
        r.car_id = '$carid' and
        r.id = '$repairid'");
    if ($repair->num_rows <> 1)
      return;
    $rpr = $repair->fetch_object();
    do_query("
      update
        austin_repair
      set
        deleted =
          case
            when deleted = '".ZERO_DATE."'
              then now()
            else
              '".ZERO_DATE."'
          end
      where
        id = '$repairid'");
    $message = "
      <div id='repair_message'>
        <p>Repair %s: %s, %s, \$%s, %s (ID:%s)</p>
        <p><a href='/admin/$node/edit/$custid/car:$carid/rprdelete:$repairid'>Undo now</a></p>
      </div>";
    if ($rpr->deleted == ZERO_DATE)
      $action = "deleted";
    else
      $action = "un-deleted";
    $message = sprintf($message,
                   $action,
                   $rpr->date_completed,
                   $rpr->desc_full,
                   number_format($rpr->cost,2),
                   substr($rpr->repairs,0,30),
                   $repairid);
     return $message;
  }

  function car_dropdown($carlist,$car_id,$tbi,$rprid,$fldid) {
    if (!array_key_exists($car_id,$carlist))
      $car_id = '0';
    $selected[$car_id] = " selected=\"selected\"";
    foreach ($carlist as $id=>$car_name) {
      $option[] = "
              <option value=\"$id\"{$selected[$id]}>$car_name</option>";
    }
    return "
            <select name=\"rpr[$rprid][car_id]\" id=\"rprfld".$fldid."\" tabindex='$tbi' class=\"car_id\" >".
              implode("",$option)."
            </select>";
  }

  function retrieve_cust_matches($search_for) {
    list($cust_select,$name_select) = split_off_cust($search_for);
    return do_query("
      select
        cust_id,
        name_full,
        address_full,
        contact_full
      from
        austin_customer cu
      where
        $cust_select
        $name_select
        deleted = '".ZERO_DATE."'");
  }

  function retrieve_cust_top_cars($cust_ids,$max_cars) {
    return do_query("
      select
        z.*,
        car.desc_full
      from
        (select
           e.cust_id,
           e.id,
           find_in_set(e.id, x.carslist) as rank
         from
           (select
              cust_id,
              group_concat(id order by date_completed desc) as carslist
            from
              (select
                 ca.cust_id,
                 ca.model_year,
                 ca.id,
                 max(re.date_completed) as date_completed
               from
                 austin_car ca
               left join
                 austin_repair re
                   on ca.id = re.car_id
               where
                 ca.cust_id in (".implode(',',$cust_ids).") and
                 ca.deleted = '".ZERO_DATE."' and
                 (re.deleted = '".ZERO_DATE."' or re.id is null)
               group by
                 ca.cust_id,
                 ca.model_year,
                 ca.id) as k
            group by
              cust_id) as x,
            austin_car as e
          where
            e.cust_id = x.cust_id) as z
      join
        austin_car car
          on z.id = car.id
      where
        rank between 1 and $max_cars
      order by
        cust_id,
        rank");
  }

  function retrieve_car_matches($search_for) {
    list($year_select,$search_for) = split_off_year($search_for);
    
    $cars = do_query("
      select
        ca.id,
        ca.desc_full,
        ca.cust_id,
        cu.name_full,
        cu.address_full,
        cu.contact_full
      from
        austin_car ca
      join
        austin_customer cu
          on ca.cust_id = cu.cust_id
      where
        ca.deleted = '".ZERO_DATE."' and
        cu.deleted = '".ZERO_DATE."' and
        $year_select
        match(desc_full)
          against('".add_slashes($search_for)."')");
    return $cars;
  }

  function retrieve_car_and_cust_matches($parms) {
    list($cust_select,$name_select) = split_off_cust($parms['cust']);
    list($year_select,$search_for_car) = split_off_year($parms['car']);

    $cars = do_query("
      select
        ca.id,
        ca.desc_full,
        ca.cust_id,
        cu.name_full,
        cu.address_full,
        cu.contact_full
      from
        austin_car ca
      join
        austin_customer cu
          on ca.cust_id = cu.cust_id
      where
        ca.deleted = '".ZERO_DATE."' and
        cu.deleted = '".ZERO_DATE."' and
        $year_select
        $cust_select
        $name_select
        match(desc_full)
          against('".add_slashes($search_for_car)."')");
    return $cars;
  }

  function retrieve_cust_recent_updates($days) {
    do_query("
      set @days_ago = interval -$days day + now()");
      
    return do_query("
      select
        ac.cust_id,
        ac.name_full,
        ac.address_full,
        ac.contact_full,
        u.updated
      from
        austin_customer ac
      join
       (select
          b.cust_id,
          max(b.updated) as updated
        from
         (select
            cust_id,
            updated_last as updated
          from
            austin_customer
          where
            updated_last > @days_ago and
            deleted = '".ZERO_DATE."'

          union

          select
            ca.cust_id,
            max(c.updated) as updated
          from
           (select
              car_id,
              updated_last as updated
            from
              austin_repair
            where
              updated_last > @days_ago and
              deleted = '".ZERO_DATE."'

            union

            select
              id,
              updated_last as updated
            from
              austin_car
            where
              updated_last > @days_ago and
              deleted = '".ZERO_DATE."') c
          join
            austin_car ca
              on c.car_id = ca.id
          group by
            ca.cust_id) b
        group by
          b.cust_id) u
        on ac.cust_id = u.cust_id
      where
        ac.deleted = '".ZERO_DATE."'
      order by
        u.updated desc");
  }

  function retrieve_repair_last_for_cars($car_ids) {
    return do_query("
      select
        r1.car_id,
        r1.date_completed,
        max(left(r1.repairs,28)) as repairs
      from
        austin_repair r1
      join
       (select
          car_id,
          max(date_completed) as date_completed
        from
          austin_repair
        where
          deleted = '".ZERO_DATE."' and
          car_id in (".implode(',',$car_ids).")
        group by
          car_id) r2
      on
        r1.car_id = r2.car_id and
        r1.date_completed = r2.date_completed
      where
        r1.deleted = '".ZERO_DATE."'
      group by
        r1.car_id,
        r1.date_completed");
  }

  function split_off_cust($search_for) {
    list($cust_select,$name_select,$names) = array('','',array());
    $word = explode(',',$search_for);
    foreach ($word as $w) {
      $x = sprintf('%d',$w).'';
      if ($x == $w)
        $cust_select = "cu.cust_id = '$w' and";
      else
        $names[] = $w;
    }
    if (count($names) > 0)
      $name_select = "match(cu.name_full,cu.contact_full) against('".add_slashes(implode(',',$names))."') and";
    return array($cust_select,$name_select);
  }

  function split_off_year($search_for) {
    $year_select = "";
    $word = explode(',',$search_for);
    $year = $word[0];
    if (strlen($year) == 2 or strlen($year) == 4) {
      $range = array(array(1910,date("Y")+2),
                     array(10,99),
                     array('00',date("y")+2));
      $is_year = false;
      foreach ($range as $r) {
        if (strlen($year) == strlen($r[0]) && $year >= $r[0] && $year <= $r[1])
          $is_year = true;
      }
      if ($is_year) {
        if (strlen($year) == 2)
          $year = ($year >= '30') ? "19$year" : "20$year";
        $year_select = "model_year = '$year' and";
        array_shift($word);
        $search_for = implode(',',$word);
      }
    }
    return array($year_select,$search_for);
  }

  function year_4_digit($year) {
    if ($year.'' <> sprintf('%d',$year))
      return $year;
    if ($year > 99 or $year == '')
      return $year;
    if ($year > date("y")+3)
      return 1900 + $year;
    return 2000 + $year;
  }
  function generate_search_results($cust_list,$car_data,$repair_data,$max_cust) {

    $pdetail = "
        <tr>
          <td>
            <div class='name'><a href='/admin/customer/edit/%d'>%s</a></div>
            <div class='addr'>%s</div>
            <div class='cont'>%s</div>
          </td>
          <td>%s
          </td>
          <td>%s
          </td>
        </tr>";
    $pcars = "
            <div><a href='/admin/customer/edit/%d/car:%d/'>%s</a></div>";
    $prepairs = "
            <div>%s</div>";

    $nbr = 0;$processed = array();
    while (($cust = $cust_list->fetch_object()) && $nbr <= $max_cust) {
      $nbr++;$carcell="";$rprcell="";
      if (!in_array($cust->cust_id,$processed)) {
        if (isset($car_data[$cust->cust_id])) {
          foreach ($car_data[$cust->cust_id] as $c)
            $carcell .= sprintf($pcars,$c->cust_id,$c->id,ucwords(strtolower(substr($c->desc_full,0,38))));
          foreach ($repair_data[$cust->cust_id] as $r)
            $rprcell .= sprintf($prepairs,$r);
        }
        $details .= sprintf($pdetail,
           $cust->cust_id,
           ucwords(strtolower($cust->name_full)),
           substr(ucwords(strtolower($cust->address_full)),0,40),
           substr(ucwords(strtolower($cust->contact_full)),0,40),
           $carcell,
           $rprcell);
      }
      $processed[] = $cust->cust_id;
    }
?>

      <table class="search_results">
        <tr>
          <th id="cust_col">
            Customer
          </th>
          <th id="cars_col">
            Cars
          </th>
          <th id="repr_col">
            Repairs
          </th>
        </tr><?=$details;?>
        
      </table>


<?

  }

  function retrieve_item_list($skip_page = '') {
    if ($_SESSION['user_type'] == 3 && !($skip_page > 0))
      $pagelimits = "and p.id in ({$_SESSION['user_pages']})";
    else if ($_SESSION['user_type'] == 3)
      $pagelimits = "and p.id <> '$skip_page'";
    else if ($skip_page > 0)
      $pagelimits = "and p.id <> '$skip_page'";

    return do_query("
     select
        p.id as id,
        p.display_order,
        0 as indent,
        case
          when p.display_link = 1
            then p.menu_name
          else p.title
        end as menu_name,
        p.display_link,
        p.status,
        p.file_name
      from
        page p
      where
        (p.id > 1 and p.deleted = 0) and
        (p.sub_id = 0) $pagelimits

      union

      select
        p.id as id,
        p.display_order,
        1 as indent,
        case
          when p.display_link = 1
            then p.menu_name
          else p.title
        end as menu_name,
        p.display_link,
        p.status,
        p.file_name
      from
        page p
      join
        page m
          on p.sub_id = m.id
      where
        p.sub_id > 0 and
        p.deleted = 0 and
        m.deleted = 0 $pagelimits

      order by
        display_order");
  }

  function retrieve_item($id,$parms) {

    $item = do_query("
      select
        cu.*
      from
        austin_customer cu
      where
        deleted = '".ZERO_DATE."' and
        cu.cust_id = '$id'");
    if ($item->num_rows == 1)
      $cust = $item->fetch_array();
    else
      return array(null,null,null);
      
    if (isset($parms['car']))
      $carselect = "and ca.id = {$parms['car']}";

    $list = do_query("
      select
        ca.*,
        concat(
          ifnull(concat(
            cast(ca.model_year as char(4)),' '),
            ''),
          ifnull(concat(
            ca.make,' '),
            ''),
          ifnull(ca.model,'')) as vehicle_desc,
        sum.*
      from
        austin_car ca
      left join
         (select
            re.car_id,
            min(date_completed) as First,
            max(date_completed) as Last,
            sum(cost) as Total,
            count(*) as Repairs,
            max(odometer) as Odometer
          from
            austin_repair re
          join
            austin_car c2
              on re.car_id = c2.id
          where
            c2.cust_id = '$id'
          group by
            re.car_id) sum
        on ca.id = sum.car_id
      where
        ca.cust_id = '$id' $carselect and
        ca.deleted = '".ZERO_DATE."'
      order by
        ca.model_year desc,
        make,
        model,
        car_no");

    if ($list->num_rows > 0)
      $cars = $list;
    else
      return array($cust,null,null);

    //$GLOBALS['query_debug'] = true;
    $list = do_query("
      select
        re.*,
        concat(
          cast(ca.model_year as char(4)),
          ' ',
          ca.make,
          ' ',
          left(ca.model,20)) as vehicle_desc
      from
        austin_repair re
      join
        austin_car ca
          on re.car_id = ca.id
      where
        re.deleted = '".ZERO_DATE."' and
        ca.cust_id = '$id' $carselect and
        ca.deleted = '".ZERO_DATE."'
      order by
        re.date_completed desc");
    //$GLOBALS['query_debug'] = false;
    if ($list->num_rows > 0)
      return array($cust,$cars,$list);
    else
      return array($cust,$cars,null);

  }

  function retrieve_dupe_addresses() {
    return do_query("
      select
        p.file_name,
        count(*)
      from
        page p
      left join
        page m
          on p.sub_id = m.id
      where
        p.deleted = 0 and
        p.status <> 'inactive' and
        ifnull(m.deleted,0) <> 0
      group by
        p.file_name
      having
        count(*) > 1");
  }

  function db_update($table,$action,$id) {
    switch ($_GET['form']) {
      case "cust":
        update_cust($table,$action,$id);
        break 1;
      case "repairs":
        update_repairs($id);
        break 1;
      case "search":
        set_search_url();
        break 1;
      default:
        exit("<h1>Invalid form id for update</h1>");
    }
  }

  function update_cust($table,$action,$id) {
    switch ($action) {
      case "add":
        insert_item($table);
        update_cars($GLOBALS['_URL'][4]);
        break 1;
      case "edit":
        update_item($table,$id);
        update_cars($id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
      case "swap":
        swap_item($table,$id,$id2,'display_order','display_order_prev','sub_id');
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }
  
  function insert_item($table) {

    $pfieldname = "
          %s";
    $pfieldvalue = "
          '%s'";

    foreach ($_POST['cust'] as $name=>$val) {
      if ($val <> '') {
        $fieldname[] = sprintf($pfieldname,$name);
        $fieldvalue[] = sprintf($pfieldvalue,add_slashes($val));
      }
    }
    if (count($fieldname) == 0) {
      $GLOBALS['next_url'] = str_replace("/add","",$GLOBALS['next_url']);
      return;
    }

    do_query("
      insert
        into $table
         (".implode(",",$fieldname).",
          created,
          updated_last)
        values
         (".implode(",",$fieldvalue).",
         now(),
         now())");

    $GLOBALS['_URL'][4] = $GLOBALS['db']->insert_id;
    $GLOBALS['next_url'] = str_replace("/add","/edit/{$GLOBALS['_URL'][4]}",$GLOBALS['next_url']);
    update_cust_full_text_data($GLOBALS['_URL'][4]);

  }

  function update_item($table,$id) {

    foreach ($_POST['cust'] as $name=>$val)
      $fieldlist[] = $name;

    $db_query = do_query("
      select
        ".implode(",",$fieldlist)."
      from
        $table
      where
        cust_id = '$id'");

    $db_data = $db_query->fetch_array();

    $preplace = "
        %s = '%s'";
    $changed = array();
    foreach ($fieldlist as $fieldname) {
      if ($_POST['cust'][$fieldname] <> $db_data[$fieldname])
        $changed[] = sprintf($preplace,$fieldname,add_slashes($_POST['cust'][$fieldname]));
    }
    if (count($changed) == 0)
      return;

    do_query("
      update
        $table
      set
        ".implode(",",$changed).",
        updated       = now(),
        updated_last  = now()
      where
        cust_id = '$id'");
        
    update_cust_full_text_data($id);

  }

  function update_cars($custid) {

    $fieldlist = array("model_year","make","model","plate","color","car_no","vin");
    $cars = do_query("
      select
        id,".implode(",",$fieldlist)."
      from
        austin_car
      where
        cust_id = '$custid' and
        deleted = '".ZERO_DATE."'");

    while ($car = $cars->fetch_array()) {
      update_one_car($custid,$car,$fieldlist);
    }
    foreach ($_POST['car'] as $carid=>$formdata) {
      if (!isset($formdata['processed']))
        insert_one_car($custid,$carid,$formdata,$fieldlist);
    }

  }

  function update_one_car($custid,$car,$fieldlist) {

    if (!is_array($_POST['car'][$car['id']])) return;

    $preplace = "
        %s = '%s'";
    $preplace_null = "
        %s = NULL";
    foreach ($fieldlist as $fieldname) {
      $value = $_POST['car'][$car['id']][$fieldname];
      if (strlen(add_slashes($value)) == 0)
        $blank++;
      if ($value <> $car[$fieldname]) {
        if ($fieldname == 'make')
          $value = check_make_fixes($value);
        if ($fieldname == 'model_year' && $value == "")
          $changed[] = sprintf($preplace_null,$fieldname);
        else
        $changed[] = sprintf($preplace,$fieldname,add_slashes($value));
      }
    }

    if ($blank == count($fieldlist))
      do_query("
        update
          austin_car
        set
          deleted = now()
        where
          id = '{$car['id']}'");
    else if (count($changed) > 0) {
      do_query("
        update
          austin_car
        set
          ".implode(",",$changed).",
          updated       = now(),
          updated_last  = now()
        where
          id = '{$car['id']}'");
      update_car_full_text_data($car['id'],$_POST['car'][$car['id']]['model_year']);
    }

    $_POST['car'][$car['id']][processed] = true;
  }

  function insert_one_car($custid,$carid,$formdata,$fieldlist) {
    if (substr($carid,0,3) <> "new") return;

    foreach ($fieldlist as $fieldname) {
      $value = $formdata[$fieldname];
      if (strlen(add_slashes($value)) == 0)
        $blank++;
      else {
        if ($fieldname == 'make')
          $value = check_make_fixes($value);
        $fields[] = $fieldname;
        $values[] = "'".add_slashes($value)."'";
      }
    }

    if ($blank >= count($fieldlist))
      return;

    do_query("
      insert
        into austin_car
         (cust_id,".implode(",",$fields).",created,updated_last)
        values
         ('$custid',".implode(",",$values).",now(),now())");
         
    update_car_full_text_data($GLOBALS['db']->insert_id,$formdata['model_year']);
    
  }

  function check_make_fixes($make) {
    if (add_slashes($make) == '')
      return '';
    $fix = do_query("
      select
        new_make
      from
        austin_car_make_fix
      where
        make = '".add_slashes($make)."' and
        new_make is not null");
    if ($fix->num_rows == 1) {
      $fix = $fix->fetch_object();
      return $fix->new_make;
    } else
      return $make;
  }

  function update_repairs($custid) {

    //$GLOBALS['query_debug'] = true;
    
    $table = "austin_repair";
    $fieldlist  = array("date_completed","car_id","cost","invoice","odometer","odometer_note","car_no","repairs");
    $fieldtype  = array("date"=>array("date_completed"),
                        "currency"=>array("cost"),
                        "integer"=>array("car_id","odometer"));
    foreach ($fieldlist as $fld)
      $fieldlist2[] = "$table.$fld";
    $repairs = do_query("
      select
        $table.id,".implode(",",$fieldlist2)."
      from
        austin_repair
      join
        austin_car ca
          on $table.car_id = ca.id
      where
        ca.cust_id = '$custid' and
        ca.deleted = '".ZERO_DATE."'
      order by
        $table.date_completed desc");
    while ($repair = $repairs->fetch_array()) {
      update_one_repair($custid,$repair,$fieldlist,$fieldtype);
    }

    foreach ($_POST['rpr'] as $rprid=>$formdata) {
      if (!isset($formdata['processed']))
        insert_one_repair($custid,$rprid,$formdata,$fieldlist,$fieldtype);
    }
    
    //exit;
  }

  function update_one_repair($custid,$repair,$fieldlist,$fieldtype) {

    // change to a return once debugged -- suggests that two people are working on the same customer
    //echo "<h2>update one repair</h2>";
    //echo "<pre>".print_r($repair,true)."</pre>";
    //echo "<pre>".print_r($_POST['rpr'][$repair['id']],true)."</pre>";
    if (!is_array($_POST['rpr'][$repair['id']])) return;

    edit_repair($fieldtype,$repair,$repair['id']);

    $preplace = "
        %s = '%s'";
    $blank = 0;
    foreach ($fieldlist as $fieldname) {
      $value = $_POST['rpr'][$repair['id']][$fieldname];
      if ($value == '' && $fieldname == 'car_id') {
        $blank++;
        $changed[] = "
        cust_id = cust_id";
      } else if (strlen(add_slashes($value)) == 0) {
        $blank++;
      } else if ($value <> $repair[$fieldname]) {
        $changed[] = sprintf($preplace,$fieldname,add_slashes($value));
      }
    }

    if ($blank >= count($fieldlist)) {
      do_query("
        update
          austin_repair
        set
          deleted = now()
        where
          id = '{$repair['id']}'");
    } else if (count($changed) > 0 && $_POST['rpr'][$repair['id']]['car_id'] > 0) {
      do_query("
        update
          austin_repair
        set
          ".implode(",",$changed).",
          updated       = now(),
          updated_last  = now()
        where
          id = '{$repair['id']}'");
    } else if (count($changed) > 0) {
      $changed[] = sprintf($preplace,'car_id',insert_unknown_car($custid));
      do_query("
        update
          austin_repair
        set
          ".implode(",",$changed).",
          updated       = now(),
          updated_last  = now()
        where
          id = '{$repair['id']}'");
    }

    $_POST['rpr'][$repair['id']][processed] = true;
  }
  
  function insert_one_repair($custid,$rprid,$formdata,$fieldlist,$fieldtype) {

    //echo "<h2>insert one repair $rprid</h2>";
    //echo "<pre>".print_r($_POST['rpr'][$rprid],true)."</pre>";

    if (substr($rprid,0,3) <> "new") return;

    $repair = null;
    edit_repair($fieldtype,$repair,$rprid);

    foreach ($fieldlist as $fieldname) {
      $value = $_POST['rpr'][$rprid][$fieldname];
      if (strlen(add_slashes($value)) == 0)
        $blank++;
    }

    if ($blank >= count($fieldlist))
      return;

    if ($_POST['rpr'][$rprid]['date_completed'] == '')
      $_POST['rpr'][$rprid]['date_completed'] = date(DB_DATE_NO_TIME);
    if ($_POST['rpr'][$rprid]['car_id'] == '')
      $_POST['rpr'][$rprid]['car_id'] = insert_unknown_car($custid);

    foreach ($fieldlist as $fieldname) {
      $value = $_POST['rpr'][$rprid][$fieldname];
      if (strlen(add_slashes($value)) > 0)
        list($fields[],$values[]) = array($fieldname,"'".add_slashes($value)."'");
    }

    do_query("
      insert
        into austin_repair
         (cust_id,".implode(",",$fields).",created,updated_last)
        values
         ('$custid',".implode(",",$values).",now(),now())");
  }

  function edit_repair($fieldtype,&$repair,$repairid) {
    foreach ($fieldtype as $type=>$fields) {
      foreach ($fields as $fldname) {
        if (isset($_POST['rpr'][$repairid][$fldname])) {
          $posted = $_POST['rpr'][$repairid][$fldname];
          switch ($type) {
            case "date":
              $value = dbdate($posted,DB_DATE_NO_TIME,'');
              if (is_array($repair) && $repair[$fldname] == DB_DATE_NO_TIME)
                $repair[$fldname] = '';
              break 1;
            case "currency":
              $value = str_replace('$','',str_replace(',','',$posted)) + 0;
              $value = ($value <> 0) ? $value : '';
              if (is_array($repair) && $repair[$fldname] == 0)
                $repair[$fldname] = '';
              break 1;
            case "integer":
              $value = str_replace(',','',$posted) + 0;
              $value = ($value <> 0) ? $value : '';
              if (is_array($repair) && $repair[$fldname] == 0)
                $repair[$fldname] = '';
              break 1;
            default:
              $value = $posted;
          }
          $_POST['rpr'][$repairid][$fldname] = $value;
        }
      }
    }
  }

  function insert_unknown_car($custid) {
    do_query("
      insert into
        austin_car
         (cust_id,make,model,created,updated_last)
        values
         ('$custid','Unknown',now(),now(),now())");
    return $GLOBALS['db']->insert_id;
  }
  
  function set_search_url() {
    if (strlen(add_slashes($_POST['search_for_cust'])) > 0) {
      if (strpos($_POST['search_for_cust'],',') > 0)
        $GLOBALS['next_url'] .= "cust:{$_POST['search_for_cust']}/";
      else
        $GLOBALS['next_url'] .= "cust:".str_replace(' ',',',$_POST['search_for_cust'])."/";
    }

    if (strlen(add_slashes($_POST['search_for_car'])) > 0) {
      if (strpos($_POST['search_for_car'],',') > 0)
        $GLOBALS['next_url'] .= "car:{$_POST['search_for_car']}/";
      else
        $GLOBALS['next_url'] .= "car:".str_replace(' ',',',$_POST['search_for_car'])."/";
    }
  }
  
  function update_cust_full_text_data ($id) {
    do_query("
      update
        austin_customer
      set
        state = upper(state),
        cust_id_x = cast(cust_id as char(10)),
        name_full = concat(
          ifnull(name_last,''),
          ifnull(concat(', ',name_first),''),
          ifnull(concat(' & ',name_spouse),'')),
        address_full = concat(
          ifnull(concat(replace(replace(street,char(10),' '),char(13),''),' '),''),
          ifnull(concat(city,' '),''),
          ifnull(concat(state,' '),''),
          ifnull(zip,'')),
        contact_full = concat(
          ifnull(concat(phone_work,' '),''),
          ifnull(concat(phone_home,' '),''),
          ifnull(concat(phone_cell,' '),''),
          ifnull(concat(phone_other,' '),''),
          ifnull(concat(email,' '),''),
          ifnull(email2,''))
      where
        cust_id = '$id'");
  }

  function update_car_full_text_data ($id,$year) {
    $year_code = ($year == "") ? "" : "model_year = '".year_4_digit($year)."',";
    do_query("
      update
        austin_car
      set
        $year_code
        desc_full = concat(
          ifnull(concat(cast(model_year as char(4)),' '),''),
          ifnull(concat(make,' '),''),
          ifnull(concat(model,' '),''),
          ifnull(concat(color,' '),''),
          ifnull(concat(plate,' '),''),
          ifnull(concat(car_no,' '),''),
          cast(cust_id as char(8)),
          ' ',
          cast(id as char(8)))
      where
        id = '$id'");
  }
  
?>