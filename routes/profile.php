<?php

function get_profile($username){
    global $app;
    $username = $app->db->escape($username);
    $query = "SELECT * FROM openclipart_users WHERE username = '$username'";
    $results = $app->db->get_array($query);
    if(!sizeof($results)) return NULL;
    $profile = $results[0];
    $id = $profile['id'];
    // TODO: use twig filter in template instead
    $profile['roles'] = $app->get_user_roles($profile['id']);
    $profile['last_modified'] = $app->get_user_last_modified_clipart($id);
    $profile['uploads'] = $app->get_user_uploads($id);
    $profile['num_comments'] = $app->get_user_num_comments($id);
    $profile['num_tags'] = $app->get_user_tags($id);
    return $profile;
}

$app->get("/profile", function() use($app){
    if(!$app->is_logged()) return $app->notFound();
    $user = $app->user();
    $username = $user['username'];
    $app->redirect("/profile/$username");
});

$app->get("/profile/:username", function($username) use($app) {
    $profile = get_profile($username);
    if(!$profile) return $app->pass();
    if(isset($app->config->userid)) $userid = $app->config->userid;
    else $userid = -1;
    return $app->render('profile/show', array(
        'profile' => $profile
        , 'is_owner' => $profile['id'] == $userid
        , 'cliparts' => $app->user_recent_clipart($username, 12)
        , 'remixes' => $app->user_num_remixes($profile['id'])
        , 'remixed' => $app->user_num_remixed($profile['id'])
    ));
});

$app->get("/profile/edit", function() use($app){
  $user = $app->user();
  if(!$user) return $app->notFound();
  $username = $user['username'];
  return $app->redirect("/profile/$username/edit");
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
    
    // only users can edit their own profile
    $user = $app->user();
    if($_POST['id'] !== $user['id']) return $app->notFound();
    
    $id             = $_POST['id'];
    $full_name      = $_POST['full_name'];
    $email          = $_POST['email'];
    $password       = $_POST['password'];
    $password_again = $_POST['password_again'];
    $homepage       = $_POST['homepage'];
    $twitter        = $_POST['twitter'];
    $about          = $_POST['about'];
    
    $errors = $app->validate(array(
      'full_name' => array( $full_name => 'fullname')
      , 'email' => array( $email => 'email')
      , 'homepage' => array( $homepage => 'homepage')
      , 'twitter' => array( $twitter => 'twitter')
    ));

    if(!empty($password)) {
      if(empty($password_again) || $password !== $password_again)
        $errors['password_again'] = "Passwords don't match.";
      $validator = $app->validators['password'];
      if(!$validator($password))
        $errors['password'] = 'passwords must be at least 6 characters long';
    }
    
    if($app->user_email_exists($email)) $errors['email'] = "Sorry, that email 
      address is already taken.";
    
    if(sizeof($errors)){
      $profile = get_profile($username);
      if(!$profile) return $app->pass();
      return $app->render('profile/edit', array(
          'profile' => array_merge($profile, $_POST)
          , 'back' => "/profile/" . $user['username']
          , 'errors' => $errors
      ));
    }
    
    $e = function($str) use($app){ return $app->db->escape($str); };
    
    $id = $e($id);
    $full_name = $e($full_name);
    $email = $e($email);
    $homepage = $e($homepage);
    $twitter = $e($twitter);
    $about = $e($about);
    if($password) $password = $app->hash_pw($e($password));
    $query = "UPDATE openclipart_users SET 
        full_name = '$full_name'
        , email = '$email'
        , homepage = '$homepage'
        , twitter = '$twitter'
        , about = '$about'";
    if(!empty($password)) $query .= ", password = '$password' ";
    $query .= " WHERE id=$id";
    if ($app->db->query($query)) 
        return $app->redirect("/profile/" . $username );
    $app->flash('error','Unable to save you edits. If this problem continues, '
      . 'please submit a bug request');
    return $app->redirect("/profile/" . $username . "/edit");
});

$app->get("/profile/:username/clipart", function($username) use($app){
  return $app->redirect("/profile/$username/clipart/0");
});
$app->get("/profile/:username/clipart/:page", function($username, $page) use($app){
  $results_per_page = 24; // results per page
  $total = $app->num_user_clipart($username);
  $cliparts = $app->user_clipart($username, $page, $results_per_page);
  return $app->render("profile/clipart", array(
    'cliparts' => $cliparts
    , 'username' => $username
    , 'pagination' => array(
      'pages' => round( $total / $results_per_page, 0, PHP_ROUND_HALF_UP)
      , 'current' => $page
    )
  ));
});

?>
