<?php

function get_profile($username){
    global $app;
    $username = $app->db->escape($username);
    $query = "SELECT * FROM openclipart_users WHERE username = '$username'";
    $results = $app->db->get_array($query);
    if(!sizeof($results)) return NULL;
    $profile = $results[0];
    $profile['creation_date'] = date('Y.n.j', strtotime($profile['creation_date']) );
    return $profile;
}

$app->get("/profile", function() use($app){
    if(!$app->is_logged()) return $app->pass();
    $user = $app->user();
    $username = $user['username'];
    $app->redirect("/profile/$username");
});

$app->get("/profile/:username", function($username) use($app) {
    $profile = get_profile($username);
    if(!$profile) return $app->pass();
    return $app->render('profile/profile', array(
        'profile' => $profile
        , 'userid' => $app->config->userid
    ));
});

$app->get("/profile/:username/edit", function($username) use($app){
    $profile = get_profile($username);
    $user = $app->user();
    if(!$profile || $profile['id'] !== $user['id']) 
        return $app->notFound();
    return $app->render('profile/edit', array(
        'profile' => $profile
        , 'back' => "/profile/" . $user['username']
    ));
});

$app->post("/profile/:username/edit", function($username) use($app){
    
    // users can edit their own profile
    $user = $app->user();
    if($_POST['id'] !== $user['id']) return $app->notFound();
    
    $id         = $_POST['id'];
    $username   = $_POST['username'];
    $full_name  = $_POST['full_name'];
    $email      = $_POST['email'];
    $homepage   = $_POST['homepage'];
    $twitter    = $_POST['twitter'];
    $about      = $_POST['about'];
    
    $errors = $app->validate(array(
      'username' => array( $username => 'username')
      , 'full_name' => array( $full_name => 'fullname')
      , 'email' => array( $email => 'email')
      , 'homepage' => array( $homepage => 'homepage')
      , 'twitter' => array( $twitter => 'twitter')
    ));
    
    if(sizeof($errors)){
      $profile = get_profile($username);
      if(!$profile) return $app->pass();
      return $app->render('profile/edit', array(
          'profile' => array_merge($profile, $_POST)
          , 'back' => "/profile/" . $user['username']
          , 'errors' => $errors
      ));
    }
    
    $e = function($str) use($app){
        return $app->db->escape($str);
    };
    
    $id = $e($id);
    $username = $e($username);
    $full_name = $e($full_name);
    $email = $e($email);
    $homepage = $e($homepage);
    $twitter = $e($twitter);
    $about = $e($about);
    
    $query = "UPDATE openclipart_users SET 
        username = '$username'
        , full_name = '$full_name'
        , email = '$email'
        , homepage = '$homepage'
        , twitter = '$twitter'
        , about = '$about' WHERE id=$id";
    
    if ($app->db->query($query)) 
        return $app->redirect("/profile/" . $username );
    $app->flash('error','Unable to save you edits. If this problem continues, '
      . 'please submit a bug request');
    return $app->redirect("/profile/" . $username . "/edit");
});

?>
