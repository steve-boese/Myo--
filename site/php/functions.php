<?

  function artist_header() {
    echo $GLOBALS['pageInfo']['header'];

  }

  function artist_location() {
    echo "{$GLOBALS['pageInfo']['city']} {$GLOBALS['pageInfo']['state']}";

  }

  function artist_studio() {
    echo nl2br($GLOBALS['pageInfo']['studio']);
  }

  function side_menu() {
    $side = do_query("
      select
        content
      from
        page
      where
        file_name = 'side-menu'");
    $side_rec = $side->fetch_object();
    echo $side_rec->content;
  }

?>