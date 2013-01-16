<?php

$app->get("/", function() use($app){
    return new Template('main', function() use($app){
        // tags
        $query = "SELECT count(openclipart_tags.id) as tag_count FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT 1";
        $max = $app->db->get_value($query);
        $query = "SELECT openclipart_tags.name, count(openclipart_tags.id) as tag_count FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT " . $app->config->tag_limit;
        $result = array();
        $rows = $app->db->get_array($query);
        shuffle($rows);
        $normalize = size('20', $max);
        
        return array(
            'editable' => false, // librarian functions
            'login-dialog' => new Template('login-dialog', null),
            'content' => new Template('home-page-content', array(
                'popular_clipart' => new Template('clipart_list', function() use($app) {
                    $last_week = "(SELECT WEEK(max(date)) FROM openclipart_favorites) = " .
                        "WEEK(date) AND YEAR(NOW()) = YEAR(date)";
                    return array(
                        'clipart_list' => $app->list_clipart($last_week, "last_date")
                    );
                }),
                'new_clipart' => new Template('clipart_list', function() {
                    global $app;
                    return array(
                        'clipart_list' => $app->list_clipart(null, "created")
                    );
                }),
                'tags' =>
                     array_map(function($row) use ($normalize) {
                         return array(
                             'name' => $row['name'],
                             'size' => $normalize($row['tag_count'])
                         );
                     }, $rows)
            )),
            'social-box' => new Template('social_boxes', null)
        );
    });
});

?>