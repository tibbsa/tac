<?php
/*
** Generates a .PNG image containing a reproduction of Legge's 
** "Dot" tactile acuity chart. At the baseline level (0 log), 
** the center-to-center spacing of dots is 2.28mm.  In the 
** rows above it, spacing is expanded (by one log unit, or 1.2589x). 
** In the rows below it, spacing is contracted by one log unit, 
** down to the -0.3 log level. Dot size and amplitude are kept 
** constant across the chart.
**
** Copyright (C) 2018 Anthony Tibbs <anthony@tibbs.ca>
**
** This program is free software: you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation, either version 3 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program.  If not, see <http://www.gnu.org/licenses/>.
**
*/

require_once('calcTextBox.php');

// All measurements in inches
$page_width_in = 8.5;
$page_height_in = 11;
$interline_spacing_in = .85;
$top_margin_in = 0.20;
$left_margin_in = 0.20;

// Dot diameter specified in mm.  We used 1mm per Bruns et al.
$dot_diameter_mm = 1;

$label_font_size = 72;
$label_font_file = './FreeSerif.ttf';

// Manual measurement of the resulting production is required to confirm
// that printer variances and tactile chart generating processes have
// not altered the size of the result. This factor allows for fine-
// tuning the results on a chart-wide basis if differences are observed.
//
// Line-by-line adjustments are not currently accounted for here.
//
$spacing_fudge_factor = 1;

//
// The symbols in this chart resemble the braille characters
// d, f, h, and j (unicode: ⠙ ⠋ ⠓ ⠚).  For consistency, each dot
// is 1mm in diameter.  The center-to-center spacing of each
// dot at the baseline size is 2.28mm
//
// In the chart specs array, dotsep specifies the dot-to-dot
// separation distances (manually calculated for each log line).
//
// Letter sequences are per Legge's charts
//
$chart_line_specs = array (
    '+0.5' => array ('dotsep' => 7.2093, 'sequence' => 'jfhdhdfj'),
    '+0.4' => array ('dotsep' => 5.7266, 'sequence' => 'hdjfjdhf'),
    '+0.3' => array ('dotsep' => 4.5489, 'sequence' => 'jdfhdfjh'),
    '+0.2' => array ('dotsep' => 3.6134, 'sequence' => 'fhdjdfdf'),
    '+0.1' => array ('dotsep' => 2.8703, 'sequence' => 'dhfjfhjd'),
    '0'    => array ('dotsep' => 2.2800, 'sequence' => 'fjhddjfh'),
    '-0.1' => array ('dotsep' => 1.8111, 'sequence' => 'fjdhhjdf'),
    '-0.2' => array ('dotsep' => 1.4386, 'sequence' => 'hfjddhfh'),
    '-0.3' => array ('dotsep' => 1.1428, 'sequence' => 'hdjdjfdj'),
);

// ======================================================================

// Prepare basic calculations
$page_dpi = 600;
$page_width_px = $page_dpi * $page_width_in;
$page_height_px = $page_dpi * $page_height_in;
$interline_spacing_px = $page_dpi * $interline_spacing_in;
$character_cell_spacing = $page_width_px / 8 - 25;
$left_margin_px = $left_margin_in * $page_dpi;
$top_margin_px = $top_margin_in * $page_dpi;

// Setup image
$img = imagecreatetruecolor($page_width_px, $page_height_px);
$colorWhite = imagecolorallocate($img, 255, 255, 255);
$colorBlack = imagecolorallocate($img, 0, 0, 0);
$colorRed = imagecolorallocate($img, 200, 0, 0);
imagefill($img, 0, 0, $colorWhite);

// save line heights for later use
$saved_line_heights = array();

$current_y = $top_margin_px;

// Loop through each line to be added to the image
foreach ($chart_line_specs as $clLabel => $clSpecs) {
    $current_x = $left_margin_px;
    $current_line_height = 0;
    $dotspacing = $clSpecs['dotsep'] * $spacing_fudge_factor;
    
    for ($i = 0; $i < strlen($clSpecs['sequence']); $i++) {
        $charImage = generateCharacter ($clSpecs['sequence'][$i], $dotspacing);

        imagecopy($img, $charImage, $current_x, $current_y, 0, 0, imagesx($charImage), imagesy($charImage));

        $current_line_height = max($current_line_height, imagesy($charImage));
        $saved_line_heights [$clLabel] = $current_line_height;
        $current_x += $character_cell_spacing;

        imagedestroy($charImage);
    }

    $current_y += $current_line_height + $interline_spacing_px;
}

imagepng($img, 'dotchart.png', 6, PNG_NO_FILTER);

// add labels
$current_y = $top_margin_px;
foreach ($chart_line_specs as $clLabel => $clSpecs) {
    $box = calculateTextBox($label_font_size, 0, $label_font_file, $clLabel);
    $pos_x = $left_margin_px + ($character_cell_spacing / 2) + 40 + $box['left'];
    $pos_y = $current_y + $box['top'];

    imagettftext(
        $img, $label_font_size, 0, $pos_x, $pos_y, $colorRed,
        $label_font_file, $clLabel);

    $current_y += $saved_line_heights [$clLabel] + $interline_spacing_px;
}

imagepng($img, 'dotchart_labelled.png', 6, PNG_NO_FILTER);
imagedestroy($img);


/*
** Generates an image (black on white) of the top 4 dots in a braille 
** cell.
**
**  Layout    d       f        h       j
**  o  o      + +     + +      + o     o +
**  o  o      o +     + o      + +     + +
**
** Each "dot" is a 1mm diameter circle. The space between dots is set by
** dotSpacing and is measured from the center of each circle.
** 
*/
function generateCharacter ($characterName, $dotSpacing)
{
    global $page_dpi;
    global $dot_diameter_mm;
        
    // The required image size is square, and is determined in part by
    // the amount of dot spacing that is used. The radius of each dot is
    // 0.5mm (diameter 1mm), meaning that we need a width/height that
    // allows for:
    //
    // 0.5mm                       + dotSpacing     + 0.5mm
    // (left half of left dots)                     (right half of right dots)
    //
    // Add 1mm to avoid calculation rounding resulting in slight clipping
    // of the edge of the dot circles
    $cellDimensions_mm = 2 * $dot_diameter_mm + $dotSpacing + 1;
    
    // Convert to pixels by converting mm to inches, and inches to pixels
    $cellDimensions_in = $cellDimensions_mm * 0.0393701;
    $cellDimensions_px = $cellDimensions_in * $page_dpi;

    // Setup image
    $img = imagecreatetruecolor($cellDimensions_px, $cellDimensions_px);
    $colorWhite = imagecolorallocate($img, 255, 255, 255);
    $colorBlack = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $colorWhite);

    // Calculate dot diameter size in terms of pixels
    $dotDiameter_px = $dot_diameter_mm * 0.0393701 * $page_dpi;

    // Calculate dot spacing in pixels (given in mm)
    $dotSpacing_px = $dotSpacing * 0.0393701 * $page_dpi;


    $dotPattern = array();
    switch ($characterName) {
    case 'd' : $dotPattern = array (1, 0, 1, 1); break;
    case 'f' : $dotPattern = array (1, 1, 1, 0); break;
    case 'h' : $dotPattern = array (1, 1, 0, 1); break;
    case 'j' : $dotPattern = array (0, 1, 1, 1); break;
    default: die('Unknown dot pattern character "' . $characterName . '"!');
    }
    

    // Draw the dots
    
    // Dot 1 - top left
    if ($dotPattern[0]) {
        imagefilledellipse (
            $img,
            $dotDiameter_px/2, $dotDiameter_px/2,
            $dotDiameter_px, $dotDiameter_px,
            $colorBlack
        );
    }

    // Dot 2 - bottom left
    if ($dotPattern[1]) {
        imagefilledellipse (
            $img,
            $dotDiameter_px/2,
            ($dotDiameter_px/2)+$dotSpacing_px,
            $dotDiameter_px, $dotDiameter_px,
            $colorBlack
        );
    }


    // Dot 3 - top right
    if ($dotPattern[2]) {
        imagefilledellipse (
            $img,
            ($dotDiameter_px/2)+$dotSpacing_px,
            $dotDiameter_px/2,
            $dotDiameter_px, $dotDiameter_px,
            $colorBlack
        );
    }

    // Dot 4 - bottom right
    if ($dotPattern[3]) {
        imagefilledellipse (
            $img,
            ($dotDiameter_px/2)+$dotSpacing_px,
            ($dotDiameter_px/2)+$dotSpacing_px,
            $dotDiameter_px, $dotDiameter_px,
            $colorBlack
        );
    }

    return $img;
}


