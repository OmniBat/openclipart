<?php
$app->get("/image/:width/:user/:filename", function($width, $user, $file) use($app) {

    $width = intval($width);
    $dir = $app->config->root_directory;
    $svg_filename = preg_replace("/.png$/", '.svg', $file);
    $png = $dir . "/people/$user/${width}px-$file";
    if(!$app->config->svg_debug)
      $svg = $dir . "/people/$user/" . $svg_filename;
    else $svg = $dir . $app->config->example_svg;
    $response = $app->response();
    
    /*
    //speed up loading - problem: nsfw can change and this may display old generated image
    $maybe_nsfw = preg_replace('/.png$/', '-nsfw.png', $png);
    if (file_exists($maybe_nsfw)) {
        return file_get_contents($maybe_nsfw);
    } else if (file_exists($png)) {
        return file_get_contents($png);
    }
    */
    $max_res = $app->config->bitmap_resolution_limit;
    if ($width > $max_res) {
        $response->status(400);
        // TODO: Generate Error Image
        echo "Resolution couldn't be higher then $max_res px! Please download SVG and " .
            "produce the bitmap locally.";
    } else if ( !file_exists($svg) || filesize($svg) == 0 ){
      return $app->notFound();
    } else {
        $file = $app->db->escape($file);
        $user = $app->db->escape($user);
        $query = "SELECT count(*) 
          FROM openclipart_clipart 
          INNER JOIN openclipart_users 
          ON owner = openclipart_users.id 
          INNER JOIN openclipart_clipart_tags ON clipart = openclipart_clipart.id 
          INNER JOIN openclipart_tags ON tag = openclipart_tags.id 
          WHERE filename = '$file' 
            AND username = '$user' 
            AND name = 'nsfw'";
        if ($app->nsfw() && $app->db->get_value($query) != 0) {
            $user = $app->config->nsfw_image['user'];
            $filename = $app->config->nsfw_image['filename'];
            $png = $dir . "/people/$user/${width}px-$file-nsfw.png";
            if(!$app->config->svg_debug){
              $svg = $dir . "/people/$user/$filename.svg";
            }else{
              // for develoment so we dont have to rsync ALL the images, just always load the same one
              $svg = $dir . $app->config->example_svg;
            }
        }


        if (file_exists($png)) {
            $response->header('Content-Type', 'image/png');
            echo file_get_contents($png);
        } else {
            // Scaling FROM AIKI
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
              $newheight = $newvalue;
              $newwidth = round(($newvalue * $width) / $height);
            }elseif($width == $height){
              $newheight = $newvalue;
              $newwidth = $newvalue;
            }else{
              $newwidth = $newvalue;
              $newheight = round(($newvalue * $height) / $width);
            }
            
            $output_dir = dirname($png);
            // make sure the directory exists
            exec("mkdir -p $output_dir");
            
            if($app->config->svg_converter === "svg2png"){
              // used for local development using MAMP on OS X with svg2png installed via `brew install svg2png`
              // the DYLD_LIBRARY_PATH bit is to prevent svg2png from linking against MAMPs outdated libs
              $cmd = "DYLD_LIBRARY_PATH=\"\" /usr/local/bin/svg2png -w $newwidth -h $newheight $svg $png";
            }else{
              $cmd = "rsvg --width $newwidth --height $newheight $svg $png";
            }
            exec($cmd);
            if(!file_exists($png)){
              $app->pass();
            }else{
              $response->header('Content-Type', 'image/png');
              echo file_get_contents($png);
            }
        }
    }
});
?>