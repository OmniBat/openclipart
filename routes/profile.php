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
    $app->redirect("/profile/" . $app->user()['username']);
});

$app->get("/profile/:username", function($username) use($app, $twig) {
    $profile = get_profile($username);
    if(!$profile) return $app->pass();
    return $twig->render('profile/profile.template', array(
        'profile' => $profile
        , 'userid' => $app->config->userid
    ));
});

$app->get("/profile/:username/edit", function($username) use($app, $twig){
    $profile = get_profile($username);
    if(!$profile || $profile['id'] !== $app->user()['id']) 
        return $app->response()->isForbidden();
    return $twig->render('profile/profile-edit.template', array(
        'profile' => $profile
    ));
});

$app->post("/profile/:username/edit", function($username) use($app, $twig){
    $id = $_POST['id'];
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $homepage = $_POST['homepage'];
    $twitter = $_POST['twitter'];
    $about = $_POST['about'];
    
    // users can edit their own profile
    if($id !== $app->user()['id']) return $app->pass();
    
    return;
});

?>