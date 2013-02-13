<?php

$app->get("/news/edit", $app->ware->is('admin'), function() use($app){
  return $app->render('news/index', array(
    'news' => $app->get_news()
  ));
});

$app->get("/news/add", $app->ware->is('admin'), function() use($app){
  return $app->render('news/edit');
});

$app->post("/news/add", $app->ware->is('admin'), function() use($app){
  $errors = array();
  if(empty($_POST['title'])) $errors['title'] = "Title is empty";
  if(empty($_POST['link'])) $errors['link'] = "Link is empty";
  if(empty($_POST['content'])) $errors['content'] = "Content is empty";
  if(empty($_POST['title']) || empty($_POST['link']) && empty($_POST['content']))
    return $app->render('/news/edit'
      , array( 
        'errors' => $errors 
        , 'news' => array(
          'title' => $_POST['title']
          , 'link' => $_POST['link']
          , 'content' => $_POST['content']
        )
      )
    );
  $app->add_news($_POST);
  return $app->redirect('/news/edit');
});

$app->get("/news/:id/edit", $app->ware->is('admin'), function($id) use($app){
  $item = $app->get_news($id);
  if(empty($item)) return $app->notFound();
  return $app->render('news/edit', array(
    'news' => $item
  ));
});

$app->post("/news/:id/edit", $app->ware->is('admin'), function($id) use($app){
  $errors = array();
  if(empty($_POST['title'])) $errors['title'] = "Title is empty";
  if(empty($_POST['link'])) $errors['link'] = "Link is empty";
  if(empty($_POST['content'])) $errors['content'] = "Content is empty";
  if(empty($_POST['title']) || empty($_POST['link']) && empty($_POST['content']) ){
    $item = $app->get_news($id);
    if(empty($item)) return $app->notFound();
    return $app->render("news/edit", array(
      'errors' => $errors
      , 'news' => array(
        'title' => $_POST['title']
        , 'link' => $_POST['link']
        , 'content' => $_POST['content']
      )
    ));
  }
  $app->edit_news($id, $_POST);
  return $app->redirect("/news/edit");
});

$app->get("/news/:id/remove", $app->ware->is('admin'), function($id) use($app){
  $app->remove_news($id);
  return $app->redirect("/news/edit");
});

$app->get("/news/:id", function($id) use($app){
  $item = $app->get_news($id);
  if(!$item) return $app->notFound();
  if(!empty($item->link)) $app->redirect($item->link);
  return $app->render("news/show", array(
    'news' => $item
  ));
});

?>