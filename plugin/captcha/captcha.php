<?php
if(isset($_GET[why])) {
   ?>
   <h2>Why do I need to enter this code?</h2><br>
   By entering the code you see in the box, you help us prevent automated programs from using this form. This ensures better performance of our services for you.
<br><br/>
If no image appears, please make sure your browser is set to display images and try again. If you are not sure what the code is, make your best guess. If you guess incorrectly, you can try a different code on the next screen.
   <?php
   exit;
}
if(isset($_GET[display])) {
session_start();
if (!$_SESSION['code']) session_register('code');


  //USER CONFIGURATION OF IMAGE
  //See included README.txt for detailed descriptions

  $image_width = 200;
  //heigh of security image

  $image_height = 40;
  //width of security image

  $code_length = 7;
  //how many letters in the code

  $ttf_file = "actionmanitalic.ttf";
  //path to ttf font to use

  $font_size_max = 25;
  //Maximum size of the font

  $font_size_min = 20;
  //Minimum size of the font

  $text_angle_minimum = -10;
  //minimum angle in degress of letter. counter-clockwise direction

  $text_angle_maximum = 10;
  //maximum angle in degrees of letter. clockwise direction

  $text_x_start = 10;
  //position (in pixels) on the x axis where text starts

  $text_minimum_distance = 22;
  //the shortest distance in pixels letters can be from eachother (a very small value will cause overlapping)

  $text_maximum_distance = 30;
  //the longest distance in pixels letters can be from eachother

  $image_bg_color = array("red" => 255, "green" => 255, "blue" => 255);
  //images background color.  set each red, green, and blue to a 0-255 value

  $background_image = "";
  //Images background image

  $text_color = array("red" => 130, "green" => 8, "blue" => 100);
  //the color of the text 6d001e

  $shadow_text = false;
  //draw a shadow for the text (gives a 3d raised bolder effect)

  $use_transparent_text = true;
  //true for the ability to use transparent text, false for normal text

  $text_transparency_percentage = 15;
  //0 to 100, 0 being completely opaque, 100 being completely transparent

  $draw_lines = true;
  //set to true to draw horizontal and vertical lines on the image
  //the following 3 options will have no effect if this is set to false

  $line_color = array("red" => 0x99, "green" => 0x99, "blue" => 0x99);
  //color of the horizontal and vertical lines through the image

  $line_distance = 20;
  //distance in pixels the lines will be from eachother.
  //the smaller the value, the more "cramped" the lines will be, potentially making
  //the text harder to read for people

  $draw_angled_lines = true;
  //set to true to draw lines at 45 and -45 degree angles over the image  (makes x's)

  $draw_lines_over_text = false;
  //set to true to draw the lines on top of the text, otherwise the text will be on the lines

  //END USER CONFIGURATION
  //There should be no need to edit below unless you really know what you are doing
//echo "in captcha program";

if($background_image != "" && is_readable($background_image)) {
   $bgimg = $background_image;
}

if($use_transparent_text == TRUE || $bgimg != "") {
   $im = imagecreatetruecolor($image_width, $image_height);
   $bgcolor = imagecolorallocate($im, $image_bg_color['red'], $image_bg_color['green'], $image_bg_color['blue']);
   imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $bgcolor);
} else { //no transparency
   $im = imagecreate($image_width, $image_height);
   $bgcolor = imagecolorallocate($im, $image_bg_color['red'], $image_bg_color['green'], $image_bg_color['blue']);
}

if($bgimg != "") {
    $dat = @getimagesize($bgimg);
    if($dat == FALSE) { return; }

    switch($dat[2]) {
      case 1: $newim = @imagecreatefromgif($bgimg); break;
      case 2: $newim = @imagecreatefromjpeg($bgimg); break;
      case 3: $newim = @imagecreatefrompng($bgimg); break;
      case 15: $newim = @imagecreatefromwbmp($bgimg); break;
      case 16: $newim = @imagecreatefromxbm($bgimg); break;
      default: exit;
    }

    if(!$newim) exit;

    imagecopy($im, $newim, 0, 0, 0, 0, $image_width, $image_height);
}

//$code = generateCode($code_length);
$code = "";
for($i = 1; $i <= $code_length; ++$i) {
   $thischar = rand(65, 90);
   $svcode[$i] = $thischar * 2;
   $code .= chr($thischar);
}


//if (!$draw_lines_over_text && $draw_lines)
drawLines();

if($use_transparent_text == TRUE) {
   $alpha = floor($text_transparency_percentage / 100 * 127);
   $font_color = imagecolorallocatealpha($im, $text_color['red'], $text_color['green'], $text_color['blue'], $alpha);
} else { //no transparency
   $font_color = imagecolorallocate($im, $text_color['red'], $text_color['green'], $text_color['blue']);
}

$x = $text_x_start;
$strlen = strlen($code);
$y_min = ($image_height / 2) + ($font_size / 2) + 6;
$y_max = ($image_height / 2) + ($font_size / 2) + 12;
for($i = 0; $i < $strlen; ++$i) {
   $angle = rand($text_angle_minimum, $text_angle_maximum);
   $y = rand($y_min, $y_max);
   $font_size = rand($font_size_min, $font_size_max);
   imagettftext($im, $font_size, $angle, $x, $y, $font_color, $ttf_file, $code{$i});
   if($shadow_text == TRUE) {
      imagettftext($im, $font_size, $angle, $x + 2, $y + 2, $font_color, $ttf_file, $code{$i});
   }
   $x += rand($text_minimum_distance, $text_maximum_distance);
}


//if ($draw_lines_over_text && $draw_lines)
drawLines();


$_SESSION['code'] = $svcode;

header("Expires: Sun, 1 Jan 2000 12:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: image/jpeg");
imagejpeg($im);
imagedestroy($im);
}

function drawLines() {
//echo "In DRAW Lines";
global $im, $image_height, $image_width, $line_color, $line_distance, $draw_angled_lines;
   $linecolor = imagecolorallocate($im, $line_color['red'], $line_color['green'], $line_color['blue']);

    //vertical lines
    for($x = 1; $x < $image_width; $x += $line_distance) {
      imageline($im, $x, 0, $x, $image_height, $linecolor);
    }

    //horizontal lines
    for($y = 11; $y < $image_height; $y += $line_distance) {
      imageline($im, 0, $y, $image_width, $y, $linecolor);
    }

    if ($draw_angled_lines == TRUE) {
      for ($x = -($image_height); $x < $image_width; $x += $line_distance) {
        imageline($im, $x, 0, $x + $image_height, $image_height, $linecolor);
      }

      for ($x = $image_width + $image_height; $x > 0; $x -= $line_distance) {
        imageline($im, $x, 0, $x - $image_height, $image_height, $linecolor);
      }
   }
}

class codeChecker {

   function checkCode($code, $user) {
      $svcode = $code;
      for ($x = 1; $x<=count($svcode); $x++) {
         $thisoldchar = $svcode[$x];
         $thischar = $thisoldchar/2;
         $oldcode .= chr($thisoldchar);
         $thiscode .= chr($thischar);
      }
      if(strtolower($user) == strtolower($thiscode) && $user != "" && $thiscode != "") return true;
      else return false;
   }
}

?>
