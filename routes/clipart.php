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
    
    // TAGS
    $query = "SELECT name FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = id WHERE clipart = $id";
    $tags = $app->db->get_column($query);
    
    $tag_rank = $app->tag_counts($tags);
    $best_term = $tag_rank[0]['name'];
    
    // COMMENTS
    $query = "select openclipart_comments.id, username, comment, date, openclipart_clipart.filename as avatar from openclipart_comments inner join openclipart_users on user = openclipart_users.id LEFT OUTER JOIN openclipart_clipart ON avatar = openclipart_clipart.id where clipart = $id";
    $comments = $app->db->get_array($query);
    
    $svg = 'public/people/' . $row['username'] . '/' . $row['filename'];
    
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
    
    try{
      return $app->render('clipart/detail', array_merge($row, array(
          'editable' => $editable
          , 'filename_png' => preg_replace('/.svg$/', '.png', $row['filename'])
          , 'remixes' => $remixes
          , 'remix_count' => count($remixes)
          , 'tags' => array_map(function($tag) use($system_tags) {
              return array(
                  'name' => $tag,
                  'system' => in_array($tag, $system_tags)
              );
          }, $tags)
          , 'comments' => array_map(function($comment) {
              $avatar = preg_replace('/.svg$/', '.png', $comment['avatar']);
              $comment['avatar'] = $avatar;
              // owner of the comment
              if (isset($app->username) && $coment['username'] == $app->username) {
                  $comment['editable'] = true;
              }
              return $comment;
          }, $comments)
          , 'file_size' => human_size(filesize($svg))
          , 'collection_count' => count($collections)
          , 'collections' => array_map(function($row) {
              $row['human_date'] = human_date($row['date']);
              return $row;
          }, $collections),
          'nsfw' => in_array('nsfw', $tags)
      )));
    }catch(Exception $e){
      return $app->notFound();
    }
});
?>