<?php

include(dirname(__FILE__) . '/../../libs/config.php');

$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
$mysqli->select_db($config['db_name']);

$query = "SELECT 
  id, upload_tags 
  FROM ocal_files 
  WHERE upload_tags IS NOT NULL 
    AND TRIM(upload_tags) != ''";

$ret = $mysqli->query($query);
if(!$ret) die($mysqli->error);

$clipart_list = array();
while ($clipart = $ret->fetch_assoc()) $clipart_list[] = $clipart;

$ret->close();

foreach ($clipart_list as $clipart) {
    $tags = explode(',', preg_replace("/, *$/", "", $clipart['upload_tags']));
    $tags = array_map(function($tag) use($mysqli){
      return trim($mysqli->real_escape_string($tag));
    }, $tags);
    
    $tags_query = implode("'), ('", $tags);
    $query = "INSERT IGNORE INTO 
      openclipart_tags (name) 
      VALUES ('$tags_query')";
    
    $mysqli->query($query);
    
    if($mysqli->affected_rows == -1){
      echo "first query";
      echo $query . "\n";
      echo $mysqli->error . "\n";
      exit(1);
    }
    
    $clipart_id = $clipart['id'];
    $tags_query = implode("', '", $tags);
    $query = "INSERT IGNORE INTO openclipart_clipart_tags 
      SELECT $clipart_id, id 
      FROM openclipart_tags 
      WHERE name in ('$tags_query')";
    
    $mysqli->query($query);
    
    if($mysqli->affected_rows == -1){
      echo "second query: <br><br>\n\n";
      echo "query: ". $query . "\n<br><br>\n";
      echo "tas: " . $tags . "\n<br><br>\n";
      echo $mysqli->error . "\n";
      exit(1);
    }
}

