<?php

$app->get("/clipart/latest", function() use($app){
  $app->redirect("/clipart/latest/0");
});

$app->get("/clipart/latest/:page", function($page) use($app){
  $results_per_page = 24; // results per page
  $total = $app->num_clipart();
  
  $start = $page * $results_per_page;
  $cliparts = $app->new_clipart(false, $start, $results_per_page);
  return $app->render("clipart/list", array(
    'cliparts' => $cliparts
    , 'title' => 'Latest Clipart'
    , 'pagination' => array(
      'pages' => round( $total / $results_per_page, 0, PHP_ROUND_HALF_UP)
      , 'current' => $page
    )
  ));
});

$app->get("/clipart/popular", function() use($app){
  $app->redirect("/clipart/popular/0");
});

$app->get("/clipart/popular/:page", function($page) use($app){
  $results_per_page = 24; // results per page
  $total = $app->num_clipart();
  $start = $page * $results_per_page;
  $cliparts = $app->popular_clipart(false, $start, $results_per_page);
  return $app->render("clipart/list", array(
    'cliparts' => $cliparts
    , 'title' => 'Clipart By Popularity'
    , 'pagination' => array(
      'pages' => round( $total / $results_per_page, 0, PHP_ROUND_HALF_UP)
      , 'current' => $page
    )
  ));
});



$app->get("/clipart/:id", function($id) use ($app) {
    $id = intval($id);
    $clipart = $app->get_clipart($id);
    if(empty($clipart)) return $app->notFound();
    $editable = false;
    if (isset($app->username)) {
        if ($app->username == $clipart['username'] || $app->is('librarian')) {
            $editable = true;
        }
    }
    
    // TAGS
    $query = "SELECT name FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = id WHERE clipart = $id";
    $tags = $app->db->get_column($query);
    
    $tag_rank = $app->tag_counts($tags);
    $best_term = $tag_rank[0]['name'];
    
    // COMMENTS
    
    $comments = $app->get_clipart_comments($id);
    
    if(!isset($app->config->svg_debug) || !$app->config->svg_debug){
      $svg = $app->clipart_path($clipart['username'], $clipart['filename']);
    }else{
      // use this file for dev/debugging so we dont have to always download
      // the entire set of svgs for testing locally
      $svg = $app->config->root_directory . $app->config->example_svg;
    }
    
    if(!file_exists($svg)){
      error_log("missing expected svg at $svg");
      return $app->notFound();
    }
    
    // REMIXES
    $query = "SELECT openclipart_clipart.id, filename, title, link, username FROM openclipart_remixes INNER JOIN openclipart_clipart ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON owner = openclipart_users.id WHERE original = $id";
    $remixes = array_map(function($remix) {
        $remix['filename'] = preg_replace("/\.svg$/", ".png", $remix['filename']);
        return $remix;
    }, $app->db->get_array($query));
    
    $system_tags = array('nsfw', 'clipart_issue', 'pd_issue');
    
    return $app->render('clipart/detail', array_merge($clipart, array(
        'editable' => $editable
        , 'filename_png' => preg_replace('/.svg$/', '.png', $clipart['filename'])
        , 'remixes' => $remixes
        , 'remix_count' => count($remixes)
        , 'tags' => array_map(function($tag) use($system_tags) {
            return array(
                'name' => $tag,
                'system' => in_array($tag, $system_tags)
            );
        }, $tags)
        , 'comments' => $comments
        , 'file_size' => human_size(filesize($svg))
        , 'nsfw' => in_array('nsfw', $tags)
        , 'comment_count' => sizeof($comments)
    )));
});




$app->get("/clipart/:id/edit", function($id) use($app){
  $id = intval($id);
  $owner = $app->config->userid;
  $query = "SELECT * FROM openclipart_clipart WHERE id = $id";
  if(!$app->is('librarian')) $query .= " AND owner = $owner";
  
  $username = $app->username_from_id($owner);
  
  $clipart = $app->db->get_array($query);
  if(!sizeof($clipart)) return $app->notFound();
  $clipart = $clipart[0];
  $tags = $app->get_clipart_tags($id);
  $nsfw = in_array('nsfw',$tags);
  foreach($tags as $key => $tag){
    if($tag === 'nsfw') unset($tags[$key]);
  }
  $app->render("/clipart/edit", array(
    'back' => "/clipart/$id"
    , 'clipart' => $clipart
    , 'filename_png' => $app->clipart_filename_png($clipart['filename'])
    , 'tags' => implode(', ', $tags)
    , 'username' => $username
    , 'nsfw' => $nsfw
  ));
});





$app->post("/clipart/:id/edit", function($id) use($app){
  $id = intval($id);
  $title = $_POST['title'];
  $author = $_POST['author'];
  $description = $_POST['description'];
  $tags = $_POST['tags'];
  
  $e = function($str) use($app){
      return $app->db->escape($str);
  };
  
  $title = $e($title);
  $author = $e($author);
  $description = $e($description);
  $tags = $e($tags);
  $owner = intval($app->config->userid);
  
  $query = "UPDATE openclipart_clipart SET 
      title = '$title'
      , original_author = '$author'
      , description = '$description'
      WHERE id = $id AND owner = $owner";
  
  // TODO: handle update error
  $app->db->query($query);
  
  // TODO: handle update error
  $tags = $app->split_tags($tags);
  if(isset($_POST['nsfw'])) array_push($tags, 'nsfw');
  $app->set_clipart_tags( $id, $tags );
  
  return $app->redirect("/clipart/" . $id );
  
});

$app->post("/clipart/:id/comments", function($clipart) use($app){
  if(!isset($_POST['text']) || !$app->is_logged()) return $app->notFound();
  $app->add_clipart_comment($clipart, $app->config->userid, $_POST['text']);
  $app->redirect("/clipart/$clipart");
});

$app->get("/clipart/:id/comments/:comment/delete", function($clipart, $comment) use($app){
  if(!$app->is_logged()) return $app->notFound();
  $app->remove_clipart_comment($clipart, $app->config->userid, $comment);
  $app->redirect("/clipart/$clipart");
});

?>