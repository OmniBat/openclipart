<?php

$app->get("/", function() use($app){
    // tags
    $query = "SELECT COUNT(openclipart_tags.id) AS tag_count 
        FROM openclipart_clipart_tags 
        INNER JOIN openclipart_tags ON openclipart_tags.id = tag 
        GROUP BY tag 
        ORDER BY tag_count 
        DESC LIMIT 1";
    $max = $app->db->get_value($query);
    
    $query = "SELECT openclipart_tags.name, count(openclipart_tags.id) as tag_count 
        FROM openclipart_clipart_tags 
        INNER JOIN openclipart_tags ON openclipart_tags.id = tag 
        GROUP BY tag 
        ORDER BY tag_count 
        DESC LIMIT 30";
    $result = array();
    $tags = $app->db->get_array($query);
    shuffle($tags);
    $normalize = size('20', $max);
    
    $last_week = "(SELECT WEEK(max(date)) FROM openclipart_favorites) = " 
        . "WEEK(date) AND YEAR(NOW()) = YEAR(date)";
    
    echo $app->render('landing', array(
        'editable' => false // librarian functions
        , 'clipart_list' => $app->list_clipart($last_week, 'last_date')
        , 'new_clipart' => $app->list_clipart(null, 'created')
        , 'user' => $app->user()
        , 'tags' => array_map(function($row) use ($normalize) {
            return array(
                'name' => $row['name']
                , 'size' => $normalize($row['tag_count'])
            );
        }, $tags)
    ));
});

?>