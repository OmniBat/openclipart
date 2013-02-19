<?php

class Svg{
  public function raster($svg, $width, $height, $output){
    // Scaling FROM AIKI
    global $app;
    
    $newvalue = $width;
    $svgfile = file_get_contents($svg);
    
    $header = get_string_between($svgfile, "<svg", ">");
    $or_width = get_string_between($header, 'width="', '"');
    $width = str_replace("px", "", $or_width );
    $width = str_replace("pt", "", $width );
    $width  = intval($width);
    
    $or_height = get_string_between($header, 'height="', '"');
    $height  = str_replace("px", "", $or_height);
    $height  = str_replace("pt", "", $height);
    $height = intval($height);
    
    if($width < $height){
      $width = round(($newvalue * $width) / $height);
      $height = $newvalue;
    }elseif($width == $height){
      $height = $newvalue;
      $width = $newvalue;
    }else{
      $height = round(($newvalue * $height) / $width);
      $width = $newvalue;
    }
    
    $output_dir = dirname($output);
    
    // make sure the directory exists
    @mkdir($output_dir, 0777, true);
    
    $width = escapeshellarg($width);
    $height = escapeshellarg($height);
    
    $svg = escapeshellarg($svg);
    $png_escaped = escapeshellarg($output);
    if( isset($app->config->svg_converter) && $app->config->svg_converter === "svg2png"){
      // used for local development using MAMP on OS X with svg2png installed via `brew install svg2png`
      // the DYLD_LIBRARY_PATH bit is to prevent svg2png from linking against MAMPs outdated libs
      $cmd = "DYLD_LIBRARY_PATH=\"\" /usr/local/bin/svg2png -w $width -h $height $svg $png_escaped";
    }else{
      $cmd = "rsvg --width $width --height $height $svg $png_escaped";
    }
    if(!exec($cmd)) error_log('error running command: $cmd');
  }
}

?>