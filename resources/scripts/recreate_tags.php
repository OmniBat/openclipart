<?php

include(dirname(__FILE__) . '/../../libs/config.php');

global $mysqli;
$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
$mysqli->select_db($config['db_name']);


function str_to_tags($str){
  global $mysqli;
  $tags = preg_split("/[\s,\-]+/", $str);
  $tags = array_map(function($tag) use($mysqli){
    $tag = trim($mysqli->real_escape_string($tag));
    $tag = preg_replace("/[^a-zA-Z0-9]/","", $tag); // remove all non character entities
    return strtolower($tag);
  }, $tags);
  return array_filter($tags, function($tag){
    return preg_match("/^\s*$/", $tag) !== 1;
  });
}

function insert_tags($tags){
  global $mysqli;
  $tags = implode("'), ('", $tags);
  $query = "INSERT IGNORE INTO 
    openclipart_tags (name) 
    VALUES ('$tags')";
  $mysqli->query($query);
  if($mysqli->affected_rows == -1){
    echo "query: ". $query . "<br><br>\n\n";
    echo "tags: " . $tags . "<br><br>\n\n";
    echo $mysqli->error . "<br><br>\n\n";
    exit(1);
  }
}

function insert_clipart_tags($id, $tags){
  global $mysqli;
  $tags = implode("', '", $tags);
  $query = "INSERT IGNORE INTO openclipart_clipart_tags 
    SELECT $id, id 
    FROM openclipart_tags 
    WHERE name in ('$tags')";
  
  $mysqli->query($query);
  
  if($mysqli->affected_rows == -1){
    echo "insert clipart tags failed: <br><br>\n\n";
    echo "query: ". $query . "<br><br>\n\n";
    echo "tags: " . $tags . "<br><br>\n\n";
    echo $mysqli->error . "<br><br>\n\n";
    exit(1);
  }
}

// allow the script to run longer
set_time_limit(0);


// $mysqli->query("DELETE FROM openclipart_tags");
// $mysqli->query("DELETE FROM openclipart_clipart_tags");

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
  $tags = str_to_tags($clipart['upload_tags']);
  insert_tags($tags);
  insert_clipart_tags($clipart['id'], $tags);
}

unset($clipart_list);


$query = "SELECT image_id as clipart, set_title as name
  FROM set_list_titles 
  INNER JOIN set_list_contents ON set_list_titles.id = set_list_id
  INNER JOIN openclipart_clipart ON openclipart_clipart.id = image_id";

$ret = $mysqli->query($query);
if(!$ret) die($mysqli->error);
$collections = array();
while ($collection = $ret->fetch_assoc()) $collections[] = $collection;
$ret->close();

foreach($collections as $col){
  $tags = str_to_tags($col['name']);
  insert_tags($tags);
  insert_clipart_tags($col['clipart'], $tags);
}
