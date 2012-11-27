<?php

class main {
    function test() {
        global $app;
        return $app->is_logged();
    }
    function favorite($clipart) {
        global $app;
        return $app->favorite(intval($clipart));
    }

    function unfavorite($clipart) {
        global $app;
        return $app->unfavorite(intval($clipart));
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

    function add_tag($clipart_id, $tag_name) {
        global $app;
        $clipart = $this->clipart($clipart_id);
        if (empty($clipart)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        if ($clipart['owner'] == $app->userid || $app->is('librarian')) {
            $id = $this->get_tag_id($tag_name);
            if (!$id) {
                $id = $this->new_tag($tag_name);
            }
            $query = "INSERT INTO openclipart_clipart_tags VALUES($clipart_id, $id)";
            return $app->db->query($query);
        } else {
            throw new Exception("You are not authorize to do that");
        }
    }

    function new_tag($tag_name) {
        global $app;
        // TODO: should we filter names?
        $tag_name = $app->db->escape($tag_name);
        $query = "INSERT INTO openclipart_tags(name) VALUES('$tag_name')";
        $ret = $app->db->query($query);
        return $ret ? $ret->insert_id : null;
    }

    function clipart($clipart_id) {
        global $app;
        $clipart_id = intval($clipart_id);
        $query = "SELECT * FROM openclipart_clipart WHERE id = $clipart_id";
        return $app->db->get_assoc($query);
    }

    function remove_tag($clipart_id, $tag) {
        global $app;
        $clipart = $this->clipart($clipart_id);
        if (empty($clipart)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        if ($clipart['owner'] == $app->userid || $app->is('librarian')) {
            $tag = $app->db->escape($tag);
            $query = "DELETE FROM openclipart_clipart_tags WHERE clipart = $clipart_id AND tag = (SELECT id FROM openclipart_tags WHERE name = '$tag')";
            return $app->db->query($query);
        } else {
            throw new Exception("You are not authorized to remove tags");
        }
    }

    function set_description($clipart_id, $description) {
        global $app;
        $clipart = $this->clipart($clipart_id);
        if (empty($clipart)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        if ($clipart['owner'] == $app->userid || $app->is('librarian')) {
            $description = $app->db->escape($description);
            $query = "UPDATE openclipart_clipart SET description = '$description' WHERE id = $clipart_id";
            return $app->db->query($query);
        } else {
            throw new Exception("You are not authorized set title");
        }
    }

    function set_title($clipart_id, $title) {
        global $app;
        $clipart = $this->clipart($clipart_id);
        if (empty($clipart)) {
            throw new Exception("Clipart $clipart_id don't exist");
        }
        if ($clipart['owner'] == $app->userid || $app->is('librarian')) {
            $title = $app->db->escape($title);
            $query = "UPDATE openclipart_clipart SET title = '$title' WHERE id = " .
                $clipart['id'];
            return $app->db->query($query);
        } else {
            throw new Exception("You are not authorized to set title");
        }
    }

    function comment($comment) {
        global $app;
        $comment = intval($comment);
        $query = "SELECT * FROM openclipart_comments WEHRE id = $comment";
        return $app->db->get_assoc($query);
    }

    function delete_comment($comment_id) {
        global $app;
        $comment = $this->comment($comment_id);
        if (empty($comment)) {
            throw new Exception("Comment $comment_id don't exist");
        }
        if ($comment['user'] == $app->userid || $app->is('librarian')) {
            $query = "DELETE FROM openclipart_comments WHERE id = " . $comment->id;
            if (!$app->db->query($query)) {
                throw new Exception("Can't remove this comment");
            }
        } else {
            throw new Exception("You are not authorized to delete this comment");
        }
    }

    function edit_comment($comment_id, $text) {
        global $app;
        $comment = $this->comment($comment_id);
        if (empty($comment)) {
            throw new Exception("Comment $comment_id don't exist");
        }
        if ($comment['user'] == $app->userid || $app->is('librarian')) {
            $text = $app->db->escape($text);
            $query = "UPDATE openclipart_comments SET comment = '$text' id = " . $comment->id;
            if (!$app->db->query($query)) {
                throw new Exception("Cound't edit this comment");
            }
        } else {
            throw new Exception("You are not authorized to delete this comment");
        }
    }
    function login($login, $password) {
        global $app;
        try {
            $app->login($login, $password);
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    function logout() {
        global $app;
        $app->logout();
    }
}