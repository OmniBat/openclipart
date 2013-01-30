<?php

include(dirname(__FILE__) . '/../../libs/config.php');

$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
$mysqli->select_db($config['db_name']);

$mysqli->query("DELETE FROM openclipart_clipart_tags");
$mysqli->query("DELETE FROM openclipart_tags WHERE name <> 'nsfw'");

$query = "SELECT id, upload_tags from ocal_files WHERE upload_tags is NOT NULL and trim(upload_tags) <> ''";

$ret = $mysqli->query($query);
if (!$ret) {
    die($mysqli->error);
}
$clipart_list = array();
while ($clipart = $ret->fetch_assoc()) {
    $clipart_list[] = $clipart;
}
$ret->close();
$total1 = 0;
$total2 = 0;
foreach ($clipart_list as $clipart) {
    $tags = explode(',', preg_replace("/, *$/", "", $clipart['upload_tags']));
    $tags = array_map(function($string) use ($mysqli) {
        return $mysqli->real_escape_string(trim($string));
    }, $tags);
    $tag_list = "('" . implode("'), ('", $tags) . "')";
    $query = "INSERT IGNORE INTO openclipart_tags(name) VALUES('" .
        implode("'), ('", $tags) . "')";
    $mysqli->query($query);
    if ($mysqli->affected_rows == -1) {
        echo $query . "\n";
        echo $mysqli->error . "\n";
        break;
    }
    $created_tags = $mysqli->affected_rows;
    $total1 += $created_tags;
    $query = "INSERT IGNORE INTO openclipart_clipart_tags SELECT " .
        $clipart['id'] . ", id FROM openclipart_tags WHERE name in ('" .
        implode("', '", $tags) . "')";
    $mysqli->query($query);
    if ($mysqli->affected_rows == -1) {
        echo $query . "\n";
        echo $mysqli->error . "\n";
        break;
    }
    $total2 += $mysqli->affected_rows;
    //echo "[$total1] " . $created_tags . "/" . count($tags) . " [$total2]\n";
}

