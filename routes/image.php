<?php

$serve_image_func = function($width, $id, $filename = null) use($app) {
  $width = intval($width);
  $svg = $app->get_clipart_path($id);
  if(!$svg) return $app->end("no clipart found in db with id $id", 404);
  $dir = $app->config->root_directory;
  $png = $dir . "/rendered-images/$width/$id";
  $response = $app->response();
  $max_res = $app->config->bitmap_resolution_limit;
  if ($width > $max_res)
    return $app->end("Resolution cant be higher then $max_res px."
      . "Please download the SVG and reproduce large versions locally.",400);
  if ( !file_exists($svg) || filesize($svg) == 0 )
    return $app->status("the clipart exists in the db but the coordisponding"
    . "svg image could not be found at $svg",404);
  // NOTE: it should be up to the view to decide to show the nsfw overlay
  if (file_exists($png)) {
    if($filename){
      $response->header('Content-Disposition: attachment; filename=' 
        . $filename);
      $response->header('Content-Type', 'application/octet-stream');
    }else $response->header('Content-Type', 'image/png');
    echo file_get_contents($png);
    return;
  }else{
    $height = $width;
    $app->svg->raster($svg, $width, $height, $png);
    if(!file_exists($png)) 
      return $app->end("unable to generate png for svg $svg",404);
    if($filename){
      $response->header('Content-Disposition: attachment; filename='
        . $filename);
      $response->header('Content-Type', 'application/octet-stream');
    }else $response->header('Content-Type', 'image/png');
    echo file_get_contents($png);
    return;
  }
};

$app->get("/rendered-images/:width/:id", function($width, $id) use($app, $serve_image_func){
  call_user_func($serve_image_func, $width, $id);
});

// Download the file as an attachment //
$app->get("/rendered-images/download/:width/:id/:filename", function($width, $id, $filename) use($app, $serve_image_func){
  $filename = preg_replace("/.svg$/", ".png", $filename);
  call_user_func($serve_image_func, $width, $id, $filename);
});
$app->get("/rendered-images/download/:width/:id", function($width, $id) use($app, $serve_image_func){
  if(isset($_GET['filename'])) $filename = $_GET['filename'];
  else $filename = 'image.png';
  $filename = preg_replace("/.svg$/", ".png", $filename);
  call_user_func($serve_image_func, $width, $id, $filename);
});

?>
