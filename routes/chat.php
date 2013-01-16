<?php
$app->get("/chat", function() {
    return new Template('main', function() {
        return array('content' => array(new Template('chat', null)));
    });
});
?>