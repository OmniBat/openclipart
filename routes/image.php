<?php
$app->get("/image/:width/:user/:filename", function($width, $user, $file) use($app) {
    
    $width = intval($width);
    $svg_filename = preg_replace("/.png$/", '.svg', $file);
    $png = $app->config->root_directory . "/people/$user/${width}px-$file";
    $svg = $app->config->root_directory . "/people/$user/" . $svg_filename;
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
    } else if (!file_exists($svg) || filesize($svg) == 0) {
        // NOTE: you don't need to check user and file for script injection because
        //       file_exists will prevent this
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
            $png = $app->config->root_directory . "/people/$user/${width}px-$file-nsfw.png";
            $svg = $app->config->root_directory . "/people/$user/$filename.svg";
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

            if ($width < $height) {
                $newhight = $newvalue;
                $newwidth = round(($newvalue * $width) / $height);
            } elseif ($width == $height) {
                $newhight = $newvalue;
                $newwidth = $newvalue;
            } else {
                $newwidth = $newvalue;
                $newhight = round(($newvalue * $height) / $width);
            }

            exec("rsvg --width $newwidth --height $newhight $svg $png");
            if (!file_exists($png)) {
                $app->pass();
            } else {
                $response->header('Content-Type', 'image/png');
                echo file_get_contents($png);
            }
        }
    }
});
?>