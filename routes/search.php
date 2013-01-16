<?php
$app->get("/search", function() use($app) {
    return new Template('main', function() use($app) {
        return array(
            'class' => 'search',
            'content' => new Template('search', function() use($app) {
                // TODO: handle error when no query param
                if(!isset($_GET['query'])) return;
                $term = $app->db->escape($_GET['query']);
                // TODO: idk what this is supposed to do but it's broken so 
                // I'm commenting it out for now. - vicapow
                // if ($app->is_logged()) {
                //     $fav_check = $app->get_user_id() . ' in ' 
                //      . '(SELECT user_error FROM openclipart_favorites' 
                //      . ' WHERE openclipart_clipart.id = clipart)';
                // } else {
                //     $fav_check = '0';
                // }
                $fav_check = '0';
                
                if ($app->nsfw()) {
                    $nsfw = "AND openclipart_clipart.id not in (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = openclipart_tags.id WHERE name = 'nsfw')";
                } else {
                    $nsfw = '';
                }
                $order_by = "date";
                $query = " SELECT openclipart_clipart.id, title, filename, link, 
                    created, username, count(DISTINCT user) as num_favorites, 
                    date, downloads 
                    FROM openclipart_clipart 
                        INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id 
                        INNER JOIN openclipart_users ON openclipart_users.id = owner 
                        WHERE openclipart_clipart.id NOT IN (
                            SELECT clipart 
                            FROM openclipart_clipart_tags 
                                INNER JOIN openclipart_tags ON openclipart_tags.id = tag 
                            WHERE clipart = openclipart_clipart.id AND openclipart_tags.name = 'pd_issue'
                        ) $nsfw AND (title rlike '^$term$|^$term | $term | $term$' or '$term' in (
                            SELECT name 
                            FROM openclipart_tags 
                            INNER JOIN openclipart_clipart_tags ON id = tag 
                            WHERE clipart = openclipart_clipart.id)) 
                            GROUP BY openclipart_clipart.id 
                            ORDER BY $order_by 
                            DESC LIMIT 42";
                $result = $app->db->get_array($query);
                var_dump($result);
                return array(
                    'clipart_list' => array_map(function($result) {
                        $png = preg_replace('/.svg$/', '.png', $result['filename']);
                        return array_merge($result, array(
                            'filename_png' => $png,
                            'human_date' => human_date($result['date'])
                        ));
                    }, $result)
                );
            })
        );
    });
});
?>