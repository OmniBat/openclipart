<?php

$app->get("/", function() use($app){
  // tags
  
  // tags, by downloads
  $query = "SELECT name, COUNT(downloads) as downloads FROM openclipart_clipart_tags 
            INNER JOIN openclipart_tags ON openclipart_tags.id = openclipart_clipart_tags.tag
            LEFT JOIN openclipart_clipart ON openclipart_clipart.id = openclipart_clipart_tags.clipart
            GROUP BY name
            ORDER BY downloads
            DESC LIMIT 30";
  
  $tags = $app->db->get_array($query);
  
  $last_week = "(SELECT WEEK(max(date)) FROM openclipart_favorites) = " 
      . "WEEK(date) AND YEAR(NOW()) = YEAR(date)";
  
  echo $app->render('landing', array(
      'editable' => false // librarian functions
      , 'clipart_list' => $app->list_clipart($last_week, 'last_date')
      , 'new_clipart' => $app->list_clipart(null, 'created')
      , 'user' => $app->user()
      , 'tags' => array_map(function($tag) {
          return array(
              'name' => $tag['name']
              , 'size' => $tag['downloads']
          );
      }, $tags)
  ));
});

?>