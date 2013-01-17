<?php

$app->get("/download/svg/:user/:filename", function($user, $filename) use($app) {

    // TODO: code should look like this: classes User and Clipart System Operate on Users
    //       OCALUser extend User and OCAL overwrite method that get user, so it return
    //       OCALUser instead of User (the later will have methods to operate on Clipart)
    /*
    $user = $app->user_by_name($username);
    if (!$user) {
        $app->notFound();
    } else {
        $clipart = $user->clipart_by_name($filename);
        if (!$clipart || $clipart->size() == 0) {
            $app->notFound();
        } else {

        }
    }
    */

    $clipart = Clipart::by_name($user, $filename);
    if (!$clipart->exists($filename) || $clipart->size() == 0) {
        // old OCAL have some 0 size files
        $app->notFound();
    } else {
        $response = $app->response()->header('Content-Type', 'application/octet-stream');
        if ($app->track()) {
            $clipart->inc_download();
        }
        if ($app->nsfw() && $clipart->nsfw()) {
            $filename = $app->config->root_directory . "/people/" .
                $app->config->nsfw_image['user'] . "/" .
                $app->config->nsfw_image['filename'] . ".svg";
        } else if ($clipart->have_pd_issue()) {
            $filename = $app->config->root_directory . "/people/" .
                $app->config->pd_issue_image['user'] . "/" .
                $app->config->pd_issue_image['filename'] . ".svg";
        } else {
            $filename = $clipart->full_path();
        }
        echo file_get_contents($filename);
    }
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
    if ($collection['last_archive_date'] != $collection['last_date']) {
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
            $filename = $app->config->root_directory . '/people/' .
                $row['user'] . '/' . $row['filename'];
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