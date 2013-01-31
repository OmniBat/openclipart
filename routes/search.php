<?php

$app->get('/search', function() use($app) {
  // redirect to page zero
  $app->redirect('/search/0', $_GET);
});

$app->get("/search/:page", function($page) use($app) {
    
    if(!isset($_GET['terms'])) return $app->notFound();
    
    
    // pagination
    $results_per_page = 24;
    $start = intval( $results_per_page * $page );
    
    
    $terms = array_map(function($term) use($app) {
      return $app->db->escape($term);
    }, $app->split_tags($_GET['terms']));
    
    $terms = implode("', '", $terms);
    $escaped_terms = $app->db->escape($terms);
    
    if ($app->nsfw())
      $nsfw = " AND openclipart_clipart.id NOT IN (
                SELECT clipart FROM openclipart_clipart_tags 
                INNER JOIN openclipart_tags ON tag = openclipart_tags.id 
                WHERE name = 'nsfw'
              ) ";
    else $nsfw = '';
    
    
    $query = "SELECT title
      , tag_table.id as id
      , filename
      , link
      , created
      , downloads
      , owner
      , username
      -- , GROUP_CONCAT( name ORDER BY name SEPARATOR ', ' ) as tags
      FROM (
        SELECT openclipart_clipart.id as id
        , title
        , filename
        , link
        , created
        , downloads 
        , owner 
        , GROUP_CONCAT( openclipart_tags.name ORDER BY name SEPARATOR '\', \'' ) as search_tags
        FROM openclipart_clipart 
          INNER JOIN openclipart_clipart_tags ON openclipart_clipart.id = openclipart_clipart_tags.clipart
          INNER JOIN openclipart_tags ON openclipart_clipart_tags.tag = openclipart_tags.id
        WHERE openclipart_tags.name IN ('$terms')
        GROUP BY id
      ) AS tag_table 
      INNER JOIN openclipart_users ON openclipart_users.id = tag_table.owner 
      INNER JOIN openclipart_clipart_tags ON tag_table.id = clipart
      INNER JOIN openclipart_tags ON openclipart_clipart_tags.tag = openclipart_tags.id
      WHERE search_tags = '$escaped_terms' 
      GROUP BY clipart
      ORDER BY downloads
      LIMIT $start, $results_per_page";
    
    
    $cliparts = $app->db->get_array($query);
    $app->add_filename($cliparts);
    $total = $app->db->get_value("SELECT FOUND_ROWS()");
    
    $query = http_build_query($_GET);
    return $app->render('search', array(
      'terms' => $terms
      , 'clipart_list' => $cliparts
      , 'pagination' => array(
        'pages' => round( $total / $results_per_page, 0, PHP_ROUND_HALF_UP)
        , 'current' => $page
        , 'query' =>  $query
      )
    ));
});
?>