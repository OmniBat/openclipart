<?php

class main {
    function favorite($clipart) {
        global $app;
        return $app->favorite(intval($clipart));
    }
    function reset_password_link($email) {
        global $app;
        return $app->send_reset_password_link($email);
    }
    function test() {
        global $app;
        return $app->username;
    }
}