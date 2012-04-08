<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>Welcome to St. Peter! - StPeterChamber.com</title>
  <meta name="title" content="Welcome to St. Peter! - StPeterChamber.com" />
  <link rel="stylesheet" href="/site/style/reset.css" type="text/css" />
  <link rel="stylesheet" href="/site/style/content.css" type="text/css" />
  <link rel="stylesheet" href="/site/style/layout.css" type="text/css" />
  <link rel="stylesheet" href="/site/style/menu.css" type="text/css" />
  <link rel="stylesheet" href="/site/style/countdown.css" type="text/css" />
  <script type="text/javascript" src="/site/script/site.js"></script>
  <script type="text/javascript" src="/site/script/countdown.js" ></script>
  <link rel="shortcut icon" href="/site/image/layout/st-peter-chamber.ico" type="image/x-icon" />
  <link href='http://fonts.googleapis.com/css?family=Yanone+Kaffeesatz:400,700' rel='stylesheet' type='text/css' />
  <script type="text/javascript">

    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-29670427-1']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();

  </script>
</head>
<body>
<div id="wrapper">
  <div id="header">
    <h1>
      <span class="title">St. Peter Minnesota Area Chamber of Commerce</span>
      <a href="/.">
        <img src="/site/image/layout/st-peter-logo.png"/>
      </a>
    </h1>
  </div>
  <div id="middle">
    <div id="container">
      <div id="content">

<?
        require(ABSPATH.'/plugin/countdown/countdown.class.php');

        $id     = 'c1';
        $today  = WebCountdown::buildDate(false);
        $launch = WebCountdown::buildDate(date(COUNTDOWN_UNTIL));
        $url    = "http://www.stpeterchamber.com/";

?>

        <table id="cdDiv_<?=$id;?>">
          <tr>
            <th colspan="4">
              The Chamber's new site launches<br/>
              Friday, March 2, 2012 at 4:30 p.m.
            </th>
          </tr>
          <tr>
            <td>
              <span id="cdClockDays_<?=$id;?>"></span>
            </td>
            <td>
              <span id="cdClockHours_<?=$id;?>"></span>
            </td>
            <td>
              <span id="cdClockMin_<?=$id;?>"></span>
            </td>
            <td>
              <span id="cdClockSec_<?=$id;?>"></span>
            </td>
          </tr>
          <tr>
            <td>
              Days
            </td>
            <td>
              Hours
            </td>
            <td>
              Min
            </td>
            <td>
              Sec
            </td>
          </tr>
        </table>

        <script>
          var <?=$id;?> = new webcd(<?=$today;?>,<?=$launch;?>,"<?=$id;?>","<?=$url;?>");
          setInterval(<?=$id;?>.actualize,1000);
        </script>

      </div>
    </div>

    <div class="sidebar" id="sideLeft">
      <div id="menu">

        <!-- ul>
          <li class='alt01'><a href='/visitors'>Visitors</a>
          </li>
          <li class='alt02'><a href='/community'>Community</a>
          </li>
          <li class='alt03'><a href='/business'>Business</a>
          </li>
          <li class='alt04'><a href='/events'>Events</a>
          </li>
          <li class='alt01'><a href='/chamber'>Chamber</a>
          </li>
        </ul -->
        <div id="org_id">
          <h1>
            Saint Peter Chamber<br/>
            of Commerce
          </h1>
          <p>
            101 South Front Street<br/>

            Saint Peter, MN 56082<br/>
            507.934.3400<br/>
            spchamb@hickorytech.net<br/>
            Open: 8:00-5:00p: Monday-Friday
          </p>

        </div>
      </div>
    </div>

  </div>

  <div id="footer">
    <img alt='' src='/site/image/layout/city-emblem.png' />
    <div class="text">
           &copy; 2012 St. Peter Chamber of Commerce |
           <a href='/site-map/'>Site map</a> |
           Design and Development by <a href='http://www.thinkenvision.com'>Envision</a></div>

  </div>

</div>


</body>
</html>