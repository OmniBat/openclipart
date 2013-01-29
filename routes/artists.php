<?php

$app->get('/artists', function() use($app){
  // redirect to page zero
  $app->redirect('/artists/0');
});

$app->get('/artists/:page', function($page) use($app){
  
  $results_per_page = 5; // results per page
  $desc = false;
  
  $orderby = '';
  if(isset($_GET['orderby'])){
    if($_GET['orderby'] === 'uploads'){
      $orderby = 'uploads';
      if(isset($_GET['desc'])) $desc = true;
    }
  }else{
    $orderby = 'uploads';
    $desc = true;
  }
  
  $start = intval( $results_per_page * $page );
  if($orderby){
    $orderby_query = "ORDER BY $orderby";
    if($desc) $orderby_query .= " DESC ";
  }else $orderby_query = '';
  
  $query = "SELECT SQL_CALC_FOUND_ROWS
              openclipart_users.id as id
              , username
              , COUNT(openclipart_clipart.owner) as uploads
              , creation_date
            FROM openclipart_users 
            LEFT JOIN openclipart_clipart ON openclipart_users.id = openclipart_clipart.owner
            GROUP BY username
            $orderby_query
            LIMIT $start, $results_per_page";
  
  $artists = $app->db->get_array($query);
  $total = $app->db->get_value("SELECT FOUND_ROWS()");
  
  return $app->render('artists', array(
    'artists' => $artists
    , 'orderby' => $orderby
    , 'desc' => $desc
    , 'pagination' => array(
      'pages' => round( $total / $results_per_page, 0, PHP_ROUND_HALF_UP)
      , 'current' => $page
    )
  ));
});

?>