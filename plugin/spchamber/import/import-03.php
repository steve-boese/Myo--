<?/*  Import-03.php

      Read the raw HTML of individual business listing pages at
      ChamberMaster already collected in the import_member table,
      extract the data, and insert it into business table.
  */

  set_time_limit(600);
  echo "<title>{$_SERVER['PHP_SELF']}</title><h1>{$_SERVER['PHP_SELF']}</h1>";
  define( 'ABSPATH', dirname(__FILE__) . '/' );

  require(ABSPATH . 'admin/config.php');
  require(ABSPATH . 'admin/functions.php');
  require(ABSPATH . 'simple_html_dom.php');
  $GLOBALS['db'] = db_connect();
  
  do_query("truncate table business");
  do_query("truncate table business_type");

  /*  H&R Block (id 86) is excluded because
      its page doesn't load at Chamber Master.
  */
  $members = do_query("
    select
      *
    from
      import_member
    where
      id <> '86'
    order by
      id");

  echo "<table>";
  $tr = "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>";
  $toll_free = array(800,888,877,866,855,844,833,822,880,881,882,883,884,885,886,887,889);

  while ($mbr = $members->fetch_object()) {
    $html = str_get_html($mbr->html_raw);
    
    $categories = $html->find("span.cm_member_categories");
    $category = $categories[0]->innertext;
    $categories[0]->outertext = "";
    
    $member_name = $html->find('h1');
    $member = $member_name[0]->innertext;

    $info_text = $html->find("td.cm_infotext");
    $address_phone = $info_text[0]->innertext;
    $addr = explode("<br/>",$address_phone);

    $size = count($addr);
    if (count($addr) <> 3 && count($addr) <> 2 && count($addr) <> 4)
      echo "<h1>$size address lines on $member</h1>";
    for ($i=0; $i<$size; $i++)
      $addr[$i] = trim($addr[$i]);
      
    if (strpos($addr[count($addr)-1],'MN') > 0)
      $phones = array();
    else
      $phones = explode(" | ",array_pop($addr));
    list($phone_1,$phone_2,$phone_fax) = array('','','');
    for ($i = 0; $i<count($phones); $i++) {
      if (substr($phones[$i],0,5) == 'Fax: ')
        $phone_fax = substr($phones[$i],5);
      else if ($i == 0)
        $phone_1 = $phones[$i];
      else if ($i == 1)
        $phone_2 = $phones[$i];
      else
        echo "<h1>phone #$i $phones[$i] not loaded</h1>";
    }
    list($phone_1,$phone_2,$phone_fax,$phone_1_desc,$phone_2_desc,$phone_fax_desc) =
      format_phones($toll_free,array($phone_1,$phone_2,$phone_fax));

    $city_state_zip = array_pop($addr);
    list($city,$state_zip) = explode(', ',$city_state_zip);
    $statezip = explode(' ',$state_zip);
    if (count($statezip) > 1)
      list($state,$zip) = $statezip;
    else
      list($state,$zip) = array($statezip[0],'');
      
    $address = implode("\n",$addr);
    
    $url_link = $html->find("td.cm_infotext a");
    $url = $url_link[0]->href;
    if (substr($url,0,4) == 'java') $url = "";
    if (substr($url,0,26) == 'http://stpeterareachamber.') $url = "";
    
    printf($tr,$member,$category,$address_phone,$url);
    
    $member = add_slashes($member);
    $category = add_slashes($category);
    $address_phone = add_slashes($address_phone);
    
    do_query("
        update
          import_member
        set
          member_name = '$member',
          member_categories = '$category',
          member_addr_phone = '$address_phone',
          member_url = '$url'
        where
          id = '{$mbr->id}'");
          
    do_query("
        insert into
          business
           (business_name,
            address,
            city,
            state,
            zip,
            phone_1,
            phone_1_desc,
            phone_2,
            phone_2_desc,
            phone_fax,
            website,
            created)
          values
           ('$member',
            '$address',
            '$city',
            '$state',
            '$zip',
            '$phone_1',
            '$phone_1_desc',
            '$phone_2',
            '$phone_2_desc',
            '$phone_fax',
            '$url',
            now())");
            
    $business_id = $GLOBALS['db']->insert_id;
            
    $catg = explode(" | ",$category);
    foreach($catg as $cat) {
      $cat = str_replace("\\'",'',str_replace('/','/ ',trim($cat)));
      if ($cat == '') break;
      $ct = do_query("
          select
            *
          from
            status
          where
            member_of = 14 and
            deleted = '".ZERO_DATE."' and
            name = '$cat'");
      if ($ct->num_rows <> 1)
        echo "<h3>rows: $ct->num_rows category $cat not found for $member</h3>";
      else {
        $ctg = $ct->fetch_object();
        do_query("
          insert into
            business_type
             (business_id,
              status_id,
              created)
            values
             ('$business_id',
              '$ctg->id',
              now())");
      }
    }

    $html->clear();
    unset($html);
  }
  
  // Lodging -- add to lodging page
  do_query("
    insert into
      business_type
       (business_id,
        status_id,
        created)
      select
        business_id,
        3,
        now()
      from
        business_type
      where
        status_id = 93");
  
  // Restaurants -- add to dining page
  do_query("
    insert into
      business_type
       (business_id,
        status_id,
        created)
      select
        business_id,
        2,
        now()
      from
        business_type
      where
        status_id = 119");

  // Standardize city name
  do_query("
    update
      business
    set
      city = replace(city,'Saint','St.')
    where
      city like 'Saint %'");

  // Standardize St. Peter zip
  do_query("
    update
      business
    set
      zip = '56082'
    where
      city = 'St. Peter'");

  do_query("
    update
      business
    set
      address = replace(address,'PO Box','P.O. Box')
    where
      address like 'PO BOX %'");

  do_query("
    update
      business
    set
      address = replace(address,'  ',' ')");
      
  build_site_content();

  function format_phones($toll_free,$phones) {
    $count = count($phones);
    for ($i=0; $i<$count*2; $i++)
      $out[] = '';
    $i = 0;
    foreach($phones as $thisphone) {
      $thisphone = trim($thisphone);
      if (substr($thisphone,-1,1) == ')' && strpos($thisphone,'(') > 0) {
        $phne = explode('(',$thisphone);
        $desc = array_pop($phne);
        $out[$i+$count] = substr($desc,0,strlen($desc)-1);
        $thisphone = trim(implode('(',$phne));
      }
      $phne = (substr($thisphone,0,2) == '1-') ? substr($thisphone,2) : $thisphone;
      $phne = str_replace('-','',str_replace(') ','-',str_replace('(','',$phne)));
      $this_int = $phne + 0;
      if (is_numeric($phne) && $phne == $this_int && in_array(strlen($phne),array(7,10))) {
        if (strlen($phne) == 7)
          $phne = '507'.$phne;
        else {
          if (in_array(substr($phne,0,3),$toll_free))
            $out[$i+$count] = 'toll-free';
        }
        $out[$i] = substr($phne,0,3).'-'.substr($phne,3,3).'-'.substr($phne,-4);
      } else
        $out[$i] = $thisphone;
      //echo "<tr><td>&nbsp;</td><td style='background:#eea'>$thisphone</td><td style='background:#eea'>$out[$i]</td><td style='background:#eea'>".$out[$i+$count]."</td></tr>";
      $i++;
    }
    return($out);
  }
  
  function build_site_content() {
    do_query("truncate table site_content");
    
    $the_html = file_get_html(SITE_HTTP);
    
    $x = $the_html->find('div#sideLeft a');

    foreach ($x as $i) {
      echo "ready to index content at ".SITE_HTTP."$i->href<br />";
      $i->href = str_replace('.','',$i->href);
      $the_page = file_get_html(SITE_HTTP.$i->href);
      $text = trim(array_shift($the_page->find('div#content'))->plaintext);
      $text = str_replace(chr(9),' ',$text);
      $text = str_replace('&nbsp;',' ',$text);
      while (strpos($text,'  ') > 0)
        $text = str_replace('  ',' ',$text);
      //if (in_array($i->href,array('/visitors','/dining'))) dump_string($text);
      do_query("
        insert into
          site_content
           (url,
            title,
            content_html,
            content_plain)
          values
           ('".add_slashes($i->href)."',
            '".add_slashes(array_shift($the_page->find('title'))->innertext)."',
            '".add_slashes(array_shift($the_page->find('div#content'))->innertext)."',
            '".add_slashes($text)."')");
    }

  }

?>