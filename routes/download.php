<?php

$app->get("/download/svg/:user/:filename", function($user, $filename) use($app) {
  if(isset($app->config->svg_debug) && $app->config->svg_debug){
    $user = 'rejon';
    $filename = 'rejon_Supergirl.svg';
  }
  $clipart = new Clipart($user, $filename);
  if(!$clipart->exists() || $clipart->size() == 0){
    // old OCAL have some 0 size files
    $app->notFound();
  }else{
    
    $response = $app->response()->header('Content-Type', 'application/octet-stream');
    if($app->track()) $clipart->inc_download();
    $nsfw_image = $app->config->nsfw_image;
    
    if($app->nsfw() && $clipart->nsfw()){
      $filename = clipart_path($nsfw_image['user'], $nsfw_image['filename']);
    }else if($clipart->have_pd_issue()){
      $filename = clipart_path($nsfw_image['user'], $nsfw_image['filename']);
    }else{
      $filename = $clipart->full_path();
    }
    echo file_get_contents($filename);
  }
});

$app->get("/download/:size/:user/:filename", function($size, $user, $filename) use($app){
  
});

$app->get("/download/collection/:name", function($name) use($app){
    // TODO:
    // name exists
    // check last count in field
    // check count using join    - can be in one query
    // if different create new archive
    $zip = new ZipArchive();
    // SQL for tag_collection info along with max date (JOIN GROUP BY)
    //$last_date =
    $collection = $app->db->get_row($query);
    $base = $app->config->root_directory . '/collections/' . $name . '-';
    // remove old collection archive
    if($collection['last_archive_date'] != $collection['last_date']){
        unlink($base . $collection['last_archive_date'] . '.zip');
        $zip_filename = $base . $collection['last_date'] . '.zip';
        $res = $zip->open($zip_filename, ZipArchive::CREATE);
        if (!$res) {
            throw new Exception("Can't create zip archive");
        }
        $zip->setArchiveComment("Open Clipart Library '$name' collection.");
        $archive = array();
        $dirs = array();
        foreach ($app->db->get_array($query) as $row) {
            $dir = $row['tag'];
            $local_filename =  $dir . '/' . $row['filename'];
            if (!in_array($dir, $dirs)) {
                if (!$zip->addEmptyDir($dir)) {
                    throw new Exception("Couldn't create directory '$dir' in".
                                        " zip file");
                }
                $dirs[] = $row['tag'];
            }
            if (array_key_exists($row['filename'], $archive)) {
                $i = ++$archive[$row['filename']];
                $local_filename = preg_replace("/\.svg$/",
                                               "_$i.svg",
                                               $local_filename);
            } else {
                $archive[$row['filename']] = 1;
            }
            $in_archive[] = $row['filename'];
            if(!isset($app->config->svg_debug) || !$app->config->svg_debug){
              $filename = $app->config->root_directory 
                . '/people/' . $row['user'] . '/' . $row['filename'];
            }else{
              $filename = $app->config->root_directory
                . $app->config->example_svg;
            }
            if (!$zip->addFile($filename, $local_filename)) {
                throw new Exception("Couldn't add file '$local_filename' to ".
                                    "the archive");
            }
        }
        $zip->close();
    } else {
        $zip_filename = $base . $collection['last_archive_date'] . '.zip';
    }
    if (!file_exists($zip_filename)) {
        $app->notFound();
    } else {
        // stream the archive
        $app->response()->header('Content-Type', 'application/octet-stream');
        echo file_get_contents($zip_filename);
    }
});

?>