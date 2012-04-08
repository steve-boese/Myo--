<?

  require(ABSPATH . 'plugin/spchamber/functions.php');
  
  $cols = (isset($GLOBALS['_TAG']['cols'])) ? $GLOBALS['_TAG']['cols'] : 1;
  $ctg  = (isset($GLOBALS['_TAG']['category'])) ? $GLOBALS['_TAG']['category'] : '';
  
  if (count(explode(',',$ctg)) == 1) {
    list($cat,$error) = retrieve_category($ctg);
    if (count($error) == 0)
      echo generate_one_cat_list($cat,false,$cols);
  } else if (count(explode("|",$ctg)) > 1)
    generate_defined_columns($ctg,$cols);
  else
    generate_flowed_columns($ctg,$cols);

  function generate_flowed_columns($categs,$cols) {
    //echo("<h1>generate_flowed $categs</h1>");
    $cats = explode(',',$categs);
    //echo "<pre>".print_r($cats,true)."</pre>";
    foreach ($cats as $c) {
      list($cat,$error) = retrieve_category($c);
      //echo "<h3>ready to generate $c</h3>";
      
      list($busns,$error) = retrieve_businesses($cat);
      if (count($error) == 0)
        list($lines,$busns_text) = retrieve_business_text($cat,$busns);
      if (count($error) > 0) {
        echo implode("<br />",$error);
        return;
      }
      //echo "<pre>".print_r($busns_text,true)."</pre>";exit;
      
      $ct[] = array(
            "catname"=>$cat->name,
            "lines"  =>$lines,
            "text"   =>$busns_text);
      $tot_lines += 2 + $lines;

    }
    //echo "<h1>$tot_lines print lines</h1>";
    //echo "<pre>".print_r($ct,true)."</pre>";

    if ($cols == 1) {
      $col_height = 9999;
      $col_style = "style='width:100%'";
    } else {
      $col_height = ceil($tot_lines / $cols) - 1;
      $col_style = "style='float:left;width:".floor(98/$cols)."%;'";
    }
    //echo "<h3>lines: $lines</h3><h3>height: $col_height</h3><h3>style: $col_style</h3>";
    $lines_printed = 0;
    $ptext .=  "
        <div $col_style class='col'>";
    if ($hdr)
      $ptext .=  "
        <h2>{$cat->name}</h2>";


    $col_split = "
        </div>
        <div $col_style class='col'>";
    foreach ($ct as $c) {
      if ($lines_printed > $col_height) {
        $ptext .= $col_split;
        $lines_printed = 0;
      }
      $ptext .=  "
        <h2>{$c['catname']}</h2>";
      foreach($c['text'] as $text) {
        if ($lines_printed > $col_height) {
          $ptext .= $col_split;
          $lines_printed = 0;
          $ptext .=  "
        <h2>{$c['catname']} (continued)</h2>";
        }
        $ptext .=  $text['print'];
        $lines_printed += $text['lines'];
      }
    }
    $ptext .=  "
        </div>";
    echo $ptext;
  }
    
  function generate_defined_columns($categs,$cols) {
    //exit("<h1>generate_defined</h1>");
    $cols = explode("|",$categs);
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
          echo generate_one_cat_list($cat,true,$cols);
      }
      echo "
          </td>";
    }
    echo "
        </tr>
      </table>";
  }

  function generate_one_cat_list($cat,$hdr,$cols) {
    if (count($error) == 0)
      list($busns,$error) = retrieve_businesses($cat);
    if (count($error) == 0)
      list($lines,$busns_text) = retrieve_business_text($cat,$busns);
    //echo "<pre>".print_r($busns_text,true)."</pre>";exit;
    if (count($error) > 0) {
      echo implode("<br />",$error);
      return;
    }
    if ($cols == 1) {
      $col_height = 9999;
      $col_style = "style='width:100%'";
    } else {
      $col_height = ceil($lines / $cols) - 1;
      $col_style = "style='float:left;width:".floor(98/$cols)."%;'";
    }
    //echo "<h3>lines: $lines</h3><h3>height: $col_height</h3><h3>style: $col_style</h3>";
    $lines_printed = 0;
    $ptext .=  "
        <div $col_style class='col'>";
    if ($hdr)
      $ptext .=  "
        <h2>{$cat->name}</h2>";
    foreach($busns_text as $text) {
      if ($lines_printed > $col_height) {
        $ptext .=  "
        </div>
        <div $col_style class='col'>";
        $lines_printed = 0;
      }
      $ptext .=  $text['print'];
      $lines_printed += $text['lines'];
    }
    $ptext .=  "
        </div>";
    return $ptext;
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
            and b.id not in
              (select bt.business_id
                 from business_type bt
                 join status sc
                   on bt.status_id = sc.id
                where sc.is_closed = 1
                  and bt.deleted = '".ZERO_DATE."'
                  and sc.deleted = '".ZERO_DATE."')
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
      
    $ct = count($busns);
    if ($ct > 0)
      $busns[$ct-1]['print'] = str_replace(
          "class='business_lstg'",
          "class='business_lstg last'",
          $busns[$ct-1]['print']);
      
    return array($lines,$busns);
    
  }
  
?>