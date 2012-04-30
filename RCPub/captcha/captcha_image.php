<?php
session_start();
include("captcha_config.php"); // file with configuration data

// create random character code for the captcha image
$text = "";
$key_chars = 'ABCDEFHJKLMNPQRTUVWXY34789'; // 0 1 O I removed to avoid confusion
$rand_max  = strlen($key_chars) - 1;
for ($i = 0; $i < $length; $i++) {
    $rand_pos  = rand(0, $rand_max);
    $text.= $key_chars{$rand_pos};
}
$_SESSION['captcha'] = $text; // save what we create

// center text in the 'box' regardless of rotation
function imagettftext_cr(&$img, $size, $angle, $x, $y, $content_color, $font, $text) {
    // retrieve boundingbox
    $bbox = imagettfbbox($size, $angle, $font, $text);
    // calculate deviation
    $dx = ($bbox[2]-$bbox[0])/2.0 - ($bbox[2]-$bbox[4])/2.0; // deviation left-right
    $dy = ($bbox[3]-$bbox[1])/2.0 + ($bbox[7]-$bbox[1])/2.0; // deviation top-bottom
    // new pivotpoint
    $px = $x-$dx;
    $py = $y-$dy;
    return imagettftext($img, $size, $angle, $px, $py, $content_color, $font, $text);
}

// get background image dimensions
$imgsize = getimagesize($background); 
$height = $imgsize[1];
$width = $imgsize[0];
$xmax = $width - $sizex;
$ymax = $height - $sizey;

// create the background in memory so we can grab chunks for each random image
$copy = imagecreatefromjpeg($background);

// create the image
$img = imagecreatetruecolor($sizex,$sizey);
$content_color = imagecolorallocate($img, $red, $green, $blue); 
	
// choose a random block (right size) of the background image
$x0 = rand(0,$xmax); $x1 = $x0 + $sizex;
$y0 = rand(0,$ymax); $y1 = $y0 + $sizey;

imagecopy($img,$copy, 0, 0, $x0, $y0, $x1, $y1);
$angle = $random * (5*rand(0,8) - 20); // random rotation -20 to +20 degrees

// add text to image once or twice (offset one pixel to emulate BOLD text if needed)
imagettftext_cr($img, $size, $angle, $sizex/2, $sizey/2-$yofs, $content_color, $font, $text);
if ($bold==1) {
    imagettftext_cr($img, $size, $angle, $sizex/2+1, $sizey/2-$yofs, $content_color, $font, $text);
}
header ("content-type: image/png");
imagepng ($img);
imagedestroy ($img);
?>