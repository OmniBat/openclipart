<?php
$app->get("/clipart/:id", function($id) use ($app) {
    $id = intval($id);
    /*
    $query = "SELECT openclipart_clipart.id, title, filename, link, created, 
            username, count(DISTINCT user) as favs, created, downloads, description 
        FROM openclipart_clipart 
        INNER JOIN openclipart_users ON owner = openclipart_users.id 
        INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id 
        WHERE openclipart_clipart.id = $id";
    */
    $query = "SELECT openclipart_clipart.id, title, filename, link, created, 
        username, created, downloads, description 
        FROM openclipart_clipart 
        INNER JOIN openclipart_users ON owner = openclipart_users.id 
        WHERE openclipart_clipart.id = $id";
    
    $row = $app->db->get_row($query);
    var_dump($row);
    if (empty($row)) return $app->notFound();
    $editable = false;
    if (isset($app->username)) {
        if ($app->username == $row['username'] || $app->is('librarian')) {
            $editable = true;
        }
    }
    return new Template('main', array(
        'login-dialog' => new Template('login-dialog', null)
        , 'editable' => $editable
        , 'content' => new Template('clipart_detail', function() use ($id, $row, $app) {
            
            // TAGS
            $query = "SELECT name FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = id WHERE clipart = $id";
            $tags = $app->db->get_column($query);
            
            $tag_rank = $app->tag_counts($tags);
            $best_term = $tag_rank[0]['name'];
            
            // COMMENTS
            $query = "select openclipart_comments.id, username, comment, date, openclipart_clipart.filename as avatar from openclipart_comments inner join openclipart_users on user = openclipart_users.id LEFT OUTER JOIN openclipart_clipart ON avatar = openclipart_clipart.id where clipart = $id";
            $comments = $app->db->get_array($query);
            
            $svg = 'people/' . $row['username'] . '/' . $row['filename'];
            
            // COLLECTIONS
            $query = "SELECT * FROM openclipart_collections INNER JOIN openclipart_users ON user = openclipart_users.id INNER JOIN openclipart_collection_clipart ON collection = openclipart_collections.id WHERE clipart = $id";
            $collections = $app->db->get_array($query);
            
            // REMIXES
            $query = "SELECT openclipart_clipart.id, filename, title, link, username FROM openclipart_remixes INNER JOIN openclipart_clipart ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON owner = openclipart_users.id WHERE original = $id";
            $remixes = array_map(function($remix) {
                $remix['filename'] = preg_replace("/\.svg$/", ".png", $remix['filename']);
                return $remix;
            }, $app->db->get_array($query));
            
            
            $system_tags = array('nsfw', 'clipart_issue', 'pd_issue');
            return array_merge($row, array(
                'filename_png' => preg_replace('/.svg$/', '.png', $row['filename']),
                'remixes' => $remixes,
                'remix_count' => count($remixes),
                'tags' => array_map(function($tag) use($system_tags) {
                    return array(
                        'name' => $tag,
                        'system' => in_array($tag, $system_tags)
                    );
                }, $tags),
                'comments' => array_map(function($comment) {
                    $avatar = preg_replace('/.svg$/', '.png', $comment['avatar']);
                    $comment['avatar'] = $avatar;
                    // owner of the comment
                    if (isset($app->username) && $coment['username'] == $app->username) {
                        $comment['editable'] = true;
                    }
                    return $comment;
                }, $comments),
                'file_size' => human_size(filesize($svg)),
                'collection_count' => count($collections),
                'collections' => array_map(function($row) {
                    $row['human_date'] = human_date($row['date']);
                    return $row;
                }, $collections),
                'nsfw' => in_array('nsfw', $tags),
                /*
                'shutterstock' => new Template('shutterstock', function() use ($best_term) {
                    global $app;
                    return array(
                        'list' => array_map(function($image) {
                            return array(
                                'thumbnail' => $image->thumb_small->img,
                                'description' => $image->description,
                                'url' => $image->web_url
                            );
                        }, $app->shutterstock($best_term)),
                        'term' => $best_term
                    );
                 }) */
            ));
        }),
        'social-box' => new Template('social_boxes', null)/*,
        'sidebar' => new Template('clipart_detail_sidebar', function() use ($id, $editable) {
            global $app;
            $query = "SELECT * FROM openclipart_collections INNER JOIN openclipart_users ON user = openclipart_users.id INNER JOIN openclipart_collection_clipart ON collection = openclipart_collections.id WHERE clipart = $id";
            $collections = $app->db->get_array($query);

            return array(
                'id' => $id,
                'editable' => $editable,
                'collection_count' => count($collections),
                'collections' => array_map(function($row) {
                    $row['human_date'] = human_date($row['date']);
                    return $row;
                }, $collections)
            );
         })*/
    ));
});
?>