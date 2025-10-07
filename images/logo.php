<?php
// Create a 300x100 image
$im = imagecreatetruecolor(300, 100);

// Define colors
$white = imagecolorallocate($im, 255, 255, 255);
$blue = imagecolorallocate($im, 74, 144, 226);
$darkBlue = imagecolorallocate($im, 53, 122, 189);

// Fill background
imagefilledrectangle($im, 0, 0, 300, 100, $white);

// Draw graduation cap
$points = array(
    50, 50,  // Center point
    20, 40,  // Left point
    80, 40,  // Right point
    50, 30   // Top point
);
imagefilledpolygon($im, $points, 4, $blue);

// Draw text
$text = "ScholarHub";
$font = 'C:\Windows\Fonts\arial.ttf';  // Default Windows font
imagettftext($im, 24, 0, 100, 60, $darkBlue, $font, $text);

// Save as PNG
imagepng($im, 'logo.png');
imagedestroy($im);

// Redirect to the image
header('Content-Type: image/png');
readfile('logo.png');
?> 