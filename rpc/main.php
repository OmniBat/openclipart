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
    function get_tag_id($tag_name) {
        global $app;
        $query = "SELECT id FROM openclipart_tags WHERE name = '$tag_name'";
        $row = $app->db->get_assoc($query);
        return empty($row) ? null : $row['id'];
    }
    function clipart_exist($clipart_id) {
        global $app;
        $clipart_id = $app->db->escape($clipart_id);
        $query = "SELECT count(*) FROM openclipart_clipart WHERE id = $clipart_id";
        return $app->db->get_value($query) !== 0;
    }
    function add_tag($clipart_id, $tag_name) {
        global $app;
        $clipart_id = intval($clipart_id);
        if (!$this->clipart_exist($clipart_id)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        $id = $this->get_tag_id($tag_name);
        if (!$id) {
            $id = $this->new_tag($tag_name);
        }
        $query = "INSERT INTO openclipart_clipart_tags VALUES($clipart_id, $id)";
        return $app->db->query($query);
    }

    function new_tag($tag_name) {
        global $app;
        // TODO: should we filter names?
        $tag_name = $app->db->escape($tag_name);
        $query = "INSERT INTO openclipart_tags(name) VALUES('$tag_name')";
        $ret = $app->db->query($query);
        return $ret ? $ret->insert_id : null;
    }
    function remove_tag($clipart_id, $tag) {
        global $app;
        $clipart_id = intval($clipart_id);
        if (!$this->clipart_exist($clipart_id)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        $tag = $app->db->escape($tag);
        $query = "DELETE FROM openclipart_clipart_tags WHERE clipart = $clipart_id AND tag = (SELECT id FROM openclipart_tags WHERE name = '$tag')";
        return $app->db->query($query);
    }
    function editable($clipart_id) {
        global $app;
        $clipart_id = intval($clipart_id);
        if (!$this->clipart_exist($clipart_id)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        if ($app->is('librarian')) {
            return true;
        } else if (isset($app->id)) {
            $query = "SELECT count(*) FROM openclipart_clipart WHERE owner = " . $app->id;
            return $app->db->get_value($query) !== 0;
        } else {
            return false;
        }
    }
    function set_description($clipart_id, $description) {
        global $app;
        $clipart_id = intval($clipart_id);
        $description = $app->db->escape($description);
        $query = "UPDATE openclipart_clipart SET description = '$description' WHERE id = $clipart_id";
        return $app->db->query($query);
    }
    function set_title($clipart_id, $title) {
        global $app;
        $clipart_id = intval($clipart_id);
        $title = $app->db->escape($title);
        $query = "UPDATE openclipart_clipart SET title = '$title' WHERE id = $clipart_id";
        return $app->db->query($query);
    }
}