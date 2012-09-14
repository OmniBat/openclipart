<?php

class main {
    function favorite($clipart) {
        global $app;
        return $app->favorite(intval($clipart));
    }
    function reset_password_link($email) {
        global $app;
        return $app->send_reset_password_link($email, $this->config->token_expiration);
    }
    function test() {
        global $app;
        return $app->username;
    }
}