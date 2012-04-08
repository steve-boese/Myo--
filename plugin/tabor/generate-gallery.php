<?

  $folder = (!isset($GLOBALS['_TAG']['folder'])) ? '' : $GLOBALS['_TAG']['folder'];
  if ($folder == '') {
    echo "<h3>Folder not defined</h3>";
    return;
  }

  $images = do_query("
    select
      name
    from
      xk_files
    where
      directory in
     (select
        d2.id
      from
        xk_directories d1
      join
        xk_directories d2
          on d2.parent =  d1.id
      where
        d1.parent = 1 and
        d1.name = '$folder')
    group by
      name
    having
      count(*) = 2
    order by
      name");

  if ($images->num_rows == 0) {
    echo "<h3>No images found in $folder</h3>";
    return;
  }

  $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/lightbox.css";
  $GLOBALS['pageInfo']['scripts']     .=
      "\n/xtool/lightbox/js/prototype.js".
      "\n/xtool/lightbox/js/scriptaculous.js?load=effects,builder".
      "\n/xtool/lightbox/js/lightbox.js";

  $link_code = "
    <a href='/site/image/%s/full/%s' rel='lightbox[%s]'
       ><img alt='' src='/site/image/%s/thumb/%s' /></a>";
  while ($img = $images->fetch_object())
    $item[] = sprintf($link_code,$folder,$img->name,$folder,$folder,$img->name);
    
  echo "
  <div class='gallery'>".implode('',$item)."
  </div>";

?>