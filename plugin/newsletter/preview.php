<?
  global $_URL;

  if (!isset($_URL[2]) || $_URL[2] == "") {
    echo "<h2>Newsletter ID not specified</h2>";
    return;
  }
  
  $e_news = do_query("
    select
      *
    from
      e_news
    where
      id = {$_URL[2]}
      and deleted = 0");
  $e_news = $e_news->fetch_object();

  if ($e_news->id == '') {
    echo "<h2>Newsletter ID {$_URL[2]} not available</h2>";
    return;
  }

  /*
  $config = mysql_fetch_array(do_query("
    select
      *
    from
      e_config"));
  */

  echo "<h2>".$e_news->subject."</h2>";

  echo $e_news->content;
  
?>