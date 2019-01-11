<?php
/*
** Generates a .PNG image containing a reproduction of Legge's 
** "Landolt C" tactile acuity chart. At the baseline level (0 log), 
** there is a 2.28mm gap in the letter "C". 
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

require_once ('calcTextBox.php');

// All measurements in inches
$page_width_in = 8.5;
$page_height_in = 11;
$interline_spacing_in = 0.6;
$top_margin_in = .65;
$left_margin_in = 0.2;

// Manual measurement of the resulting production is required to confirm
// that printer variances and tactile chart generating processes have
// not altered the size of the result. This factor allows for fine-
// tuning the results on a chart-wide basis if differences are observed.
//
// Line-by-line adjustments are not currently accounted for here.
//
$fontsize_fudge_factor = 1;

//
// Through some trial and error and measurement with Gimp/Photoshop to
// confirm sizes, we determined that a baseline font size of 102 would
// result in a 2.28mm gap in the Landolt C character. The rest of the
// font sizes were calculated based on 0.1 log units (+/- 1.2589x)
//
// For each line, a pseudorandom order of orientation (Left, Right, Up, Down)
// has been generated. Where available, existing patterns used in other
// studies have been used.
//
$chart_line_specs = array (
    '+0.3' => array ('fontsize' => 203, 'sequence' => 'rlulddru'),
    '+0.2' => array ('fontsize' => 162, 'sequence' => 'ludrrlud'),
    '+0.1' => array ('fontsize' => 128, 'sequence' => 'rduuldrl'),
    '0' => array ('fontsize' => 102, 'sequence' => 'ulrdrlud'),
    '-0.1' => array ('fontsize' => 81, 'sequence' => 'dludrurl'),
    '-0.2' => array ('fontsize' => 64, 'sequence' => 'rudlurld'),
    '-0.3' => array ('fontsize' => 51, 'sequence' => 'rldrlduu'),
    '-0.4' => array ('fontsize' => 41, 'sequence' => 'lrulddru'),
    '-0.5' => array ('fontsize' => 32, 'sequence' => 'rlurduld'),
    '-0.6' => array ('fontsize' => 25, 'sequence' => 'rddulurl'),
    '-0.7' => array ('fontsize' => 20, 'sequence' => 'ludrrlud')
);

// ======================================================================
$font_file = 'ecfonts/Sloan.otf';

// Labelling
$label_font_size = 48;
$label_font_file = './FreeSerif.ttf';

// Prepare basic calculations
$page_dpi = 300;
$page_width_px = $page_dpi * $page_width_in;
$page_height_px = $page_dpi * $page_height_in;
$interline_spacing_px = $page_dpi * $interline_spacing_in;
$character_cell_spacing = $page_width_px / 8 - 10;
$left_margin_px = $left_margin_in * $page_dpi;
$top_margin_px = $top_margin_in * $page_dpi;

// Setup image
$img = imagecreatetruecolor($page_width_px, $page_height_px);
$colorWhite = imagecolorallocate($img, 255, 255, 255);
$colorBlack = imagecolorallocate($img, 0, 0, 0);
$colorRed = imagecolorallocate($img, 200, 0, 0);
imagefill($img, 0, 0, $colorWhite);

// store height of each line so that we can add labels later
$saved_line_heights = array();

$current_y = $top_margin_px;

// Loop through each line to be added to the image
foreach ($chart_line_specs as $clLabel => $clSpecs) {
    $current_x = $left_margin_px;
    $current_line_height = 0;
    $fontsize = $clSpecs['fontsize'] * $fontsize_fudge_factor;
    
    for ($i = 0; $i < strlen($clSpecs['sequence']); $i++) {
        // By default the Landolt 'C' character appears with the
        // opening to the RIGHT.  Determine the rotation angle needed
        // to effect the sequencing.
        $angle = 0;
        switch ($clSpecs['sequence'][$i]) {
        case 'u' :
            $angle = 90; break;
            
        case 'l' :
            $angle = 180; break;
            
        case 'r':
            $angle = 0; break;
            
        case 'd':
            $angle = 270; break;

        default:
            die ('Error in sequence for ' . $clLabel . ': unrecognized direction "' . $clSpecs['sequence'][$i] . '" (pos ' . $i . ')');
        }

        // Determine bounding box
        $box = calculateTextBox($fontsize, $angle, $font_file, 'C');
        $pos_x = $current_x + $box['left'];
        $pos_y = $current_y + $box['top'];
        $current_line_height = max($current_line_height, $box['height']);
        $saved_line_heights [$clLabel] = $current_line_height;
        imagettftext($img, $fontsize, $angle, $pos_x, $pos_y, $colorBlack,
                     $font_file, 'C');

        $current_x += $character_cell_spacing;
    }
    
    $current_y += $current_line_height + $interline_spacing_px;
}

imagepng($img, 'landolt.png', 6, PNG_NO_FILTER);

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

imagepng($img, 'landolt_labelled.png', 6, PNG_NO_FILTER);
imagedestroy($img);



