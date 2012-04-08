<?php

  define ('CMS_NAME'         , 'Myo!');
  define ('CMS_VERSION'      , 'v1.0');
  define ('CMS_SUPPORT_LINK' , 'http://www.steveboese.com/contact/');
  define ('SITE_HOSTED_BY'   , 'Steve Boese');
  define ('SITE_HOSTED_LINK' , 'http://www.steveboese.com');
  define ('TITLE_SUFFIX'     , ' - Myo-CMS.com');

  define ('DB_HOST'          , 'localhost');
  define ('DB_PORT'          , '8539');
  define ('DB_USER'          , 'root');
  define ('DB_PASSWORD'      , '255x3794');
  define ('DB_NAME'          , 'cms');
  
  define ('MAX_SESSION_LEN'  , 120);
  
  define ('SITE_FAIL_EMAIL'  , false);
  define ('SITE_TECH_EMAIL'  , 'steve@tidydude.com');
  define ('SITE_EMAIL_FROM'  , 'steve@tidydude.com');
  define ('SITE_EMAIL_TO'    , 'steve@tidydude.com');

  define ('SITE_HTTP'        , 'http://localhost:8081');
  define ('SITE_VERSION'     , '');
  define ('SITE_NAME'        , 'Myo!');
  define ('SITE_PORT'        , 'p'.$_SERVER['SERVER_PORT']);
  
  define ('SITE_FOOTER'      , "
           &copy; ".date('Y')." ".SITE_NAME." |
           Site design and development by <a href='".SITE_HOSTED_LINK."'>".SITE_HOSTED_BY."</a>");
           
  define ('ADMIN_FOOTER'     , "
      <tr id='footer'>
        <td>
          &nbsp;
        </td>
        <td>
          Supported by <a href='".SITE_HOSTED_LINK."'>".SITE_HOSTED_BY."</a>
        </td>
      </tr>");

  define ('SITE_FAIL_MESSAGE', "
           Sorry, but the ".SITE_VERSION." ".SITE_NAME." site has hit a fatal error.
           <br/>
           <br/>
           It's been reported, and will be fixed soon.");

  define ('FORMS_ESCAPED'    , false);
  
  define ('COUNTDOWN_UNTIL'  , '2012-01-01 00:00:00');
  define ('COUNTDOWN_MODULE' , 'plugin/countdown/splash_page.php');
  define ('COUNTDOWN_SERVER' , 'stpeterchamber.com');

  $GLOBALS['query_debug']    = false;
  $GLOBALS['debug_track']    = false;
  $GLOBALS['track']          = "";
  
  define ('_URI_'            , $_SERVER['REQUEST_URI']);
  define ('ZERO_DATE'        , '0000-00-00 00:00:00');
  define ('ZERO_DATE_NO_TIME', '0000-00-00');
  define ('DB_DATE'          , 'Y-m-d H:i:s');
  define ('DB_DATE_NO_TIME'  , 'Y-m-d');

?>