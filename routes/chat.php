<?php
$app->get("/chat", function() use($twig) {
    return $twig->render('chat.template');
});
?>