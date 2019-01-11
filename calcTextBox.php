<?php
/*
** Text box image size calculator
**
** Borrowed from http://php.net/manual/en/function.imagettfbbox.php
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

function calculateTextBox($font_size, $font_angle, $font_file, $text) {
    $box   = imagettfbbox($font_size, $font_angle, $font_file, $text);
    if( !$box )
        return false;
    $min_x = min( array($box[0], $box[2], $box[4], $box[6]) );
    $max_x = max( array($box[0], $box[2], $box[4], $box[6]) );
    $min_y = min( array($box[1], $box[3], $box[5], $box[7]) );
    $max_y = max( array($box[1], $box[3], $box[5], $box[7]) );
    $width  = ( $max_x - $min_x );
    $height = ( $max_y - $min_y );
    $left   = abs( $min_x ) + $width;
    $top    = abs( $min_y ) + $height;
    // to calculate the exact bounding box i write the text in a large image
    $img     = @imagecreatetruecolor( $width << 2, $height << 2 );
    $white   =  imagecolorallocate( $img, 255, 255, 255 );
    $black   =  imagecolorallocate( $img, 0, 0, 0 );
    imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $black);
    // for sure the text is completely in the image!
    imagettftext( $img, $font_size,
    $font_angle, $left, $top,
    $white, $font_file, $text);
    // start scanning (0=> black => empty)
    $rleft  = $w4 = $width<<2;
    $rright = 0;
    $rbottom   = 0;
    $rtop = $h4 = $height<<2;
    for( $x = 0; $x < $w4; $x++ )
        for( $y = 0; $y < $h4; $y++ )
            if( imagecolorat( $img, $x, $y ) ){
                $rleft   = min( $rleft, $x );
                $rright  = max( $rright, $x );
                $rtop    = min( $rtop, $y );
                $rbottom = max( $rbottom, $y );
            }
    // destroy img and serve the result
    imagedestroy( $img );
    return array( "left"   => $left - $rleft,
    "top"    => $top  - $rtop,
    "width"  => $rright - $rleft + 1,
    "height" => $rbottom - $rtop + 1 );
}