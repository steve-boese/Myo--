<?

  if (count(explode(',',$GLOBALS['_TAG']['category'])) == 1) {
    list($cat,$error) = retrieve_category($GLOBALS['_TAG']['category']);
    if (count($error) == 0)
      generate_one_cat_list($cat,false);
  } else {
    $cols = explode("|",$GLOBALS['_TAG']['category']);
    if (count($cols) > 1)
      $col_style = "style='width:".floor(98/count($cols))."%;'";
    echo "
      <table>
        <tr>";
    foreach ($cols as $col) {
      echo "
          <td $col_style>";
      $cats = explode(",",$col);
      foreach ($cats as $ct) {
        list($cat,$error) = retrieve_category($ct);
        if (count($error) == 0)
          generate_one_cat_list($cat,true);
      }
      echo "
          </td>";
    }
    echo "
        </tr>
      </table>";

  }

  function generate_one_cat_list($cat,$hdr) {
    if (count($error) == 0)
      list($busns,$error) = retrieve_businesses($cat);
    if (count($error) == 0)
      list($lines,$busns_text) = retrieve_business_text($cat,$busns);
    //echo "<pre>".print_r($busns_text,true)."</pre>";exit;
    if (count($error) > 0) {
      echo implode("<br />",$error);
      return;
    }
    $col_count = ($GLOBALS['_TAG']['cols'] > 0) ? $GLOBALS['_TAG']['cols'] : 1;
    if ($col_count == 1) {
      $col_height = 9999;
      $col_style = "style='width:100%'";
    } else {
      $col_height = ceil($lines / $col_count) - 1;
      $col_style = "style='float:left;width:".floor(98/$col_count)."%;'";
    }
    //echo "<h3>lines: $lines</h3><h3>height: $col_height</h3><h3>style: $col_style</h3>";
    $lines_printed = 0;
    echo "
        <div $col_style>";
    if ($hdr)
      echo "
        <h3>{$cat->name}</h3>";
    foreach($busns_text as $text) {
      if ($lines_printed > $col_height) {
        echo "
        </div>
        <div $col_style>";
        $lines_printed = 0;
      }
      echo $text['print'];
      $lines_printed += $text['lines'];
    }
    echo "
        </div>";
  }

  function retrieve_category($cat_id) {
    list($error,$row) = array(array(),null);
    if ($cat_id == '')
      $error = array('A category="#" code is required','but none was defined');
    else {
      $result_cat = do_query("
        select
          *
        from
          status
        where
          id = '$cat_id' and
          deleted = '".ZERO_DATE."'");
      if ($result_cat->num_rows == 0)
        $error = array("Category #$cat_id is not valid");
      else
        $row = $result_cat->fetch_object();
    }
    return array($row,$error);
  }

  function retrieve_businesses($cat) {
    $error = array();
    //$GLOBALS['query_debug'] = true;
    $result_busns = do_query("
      select
        b.*
      from
        business b
      join
        business_type t
          on b.id = t.business_id
      where
        t.status_id = '$cat->id' and
        t.deleted   = '".ZERO_DATE."' and
        b.deleted   = '".ZERO_DATE."'
      order by
        business_name");
    if ($result_busns->num_rows == 0)
      $errors = array("No businesses found.","Category: #$cat->id, $cat->name");
    return array($result_busns,$errors);
  }

  function retrieve_business_text($cat,$result_busns) {
    list($error,$busns,$lines) = array(array(),array(),0);

    while ($row = $result_busns->fetch_object())
      list($lines,$busns[]) = print_one_business($lines,$row);
      
    return array($lines,$busns);
    
  }
  
  function print_one_business($lines,$busns) {
    //echo "<pre>".print_r($lines,true)."</pre>";
    //echo "<pre>".print_r($busns,true)."</pre>";exit;
    $code = array('business_name'=>"
      <h4>%s</h4>",
                  'business_name_2'=>"
      %s<br />",
                  'address_nl2br'=>"
      %s<br />",
                  'city_state_zip'=>"
      %s %s %s<br />",
                  'phone_1_and_desc'=>"
      %s %s<br />",
                  'phone_2_and_desc'=>"
      %s %s<br />",
                  'phone_fax_and_desc'=>"
      %s %s<br />",
                  'blurb_nl2br'=>"
      <div class='blurb'>%s</div>");
    $wrapper = "
    <div class='business_lstg'>%s
    </div>\n";
      
    $busns_lines = 0;
    foreach ($code as $field=>$code) {
      if (isset($busns->$field) and $busns->$field <> '') {
        $print[] = sprintf($code,$busns->$field);
        $busns_lines = $busns_lines + 1 + floor(strlen($busns->$field/40));
      } else {
        $fld_seg = explode('_',$field);
        $fld_suff = array_pop($fld_seg);
        $fld_name = implode('_',$fld_seg);
        if ($fld_suff == 'nl2br' && isset($busns->$fld_name) && $busns->$fld_name <> '') {
          $prt_lines = nl2array($busns->$fld_name);
          $print[] = sprintf($code,implode('<br />',$prt_lines));
          $busns_lines = $busns_lines + count($prt_lines);
        } else if ($fld_seg[0] == 'phone') {
          if ($fld_seg[1] == 'fax' && $busns->phone_fax <> '') {
            $desc = 'fax';
            $phone_fld = 'phone_fax';
          } else {
            array_pop($fld_seg);
            $phone_fld = implode('_',$fld_seg);
            $phone_dsc = $phone_fld."_desc";
            if (isset($busns->$phone_dsc) && $busns->$phone_dsc <> '')
              $desc = $busns->$phone_dsc;
          }
          if (isset($desc))
            $desc = "($desc)";
          if ($busns->$phone_fld <> '') {
            $print[] = sprintf($code,$busns->$phone_fld,$desc);
            $busns_lines++;
          }
        } else if ($field == 'city_state_zip') {
          if ($busns->city <> '' && $busns->state <> '' && $busns->zip <> '') {
            $print[] = sprintf($code,$busns->city,$busns->state,$busns->zip);
            $busns_lines++;
          }
        }
      }
    }
    //$busns_lines++;
    $lines = $lines + $busns_lines;
    return array($lines,array('lines'=>$busns_lines,'print'=>sprintf($wrapper,implode('',$print))));
  }

?>