<?php
/**
 *  This file is part of Open Clipart Library <http://openclipart.org>
 *
 *  Open Clipart Library is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Open Clipart Library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Open Clipart Library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *  author: Jakub Jankiewicz <http://jcubic.pl>
 */

class Clipart {
    static function by_name($user, $filename) {
        global $app;
        $user = $app->db->escape($user);
        $filename = $app->db->escape($filename);
        if (preg_match("/\.png|\.jpg$/", $filename)) {
            // so we don't need to care about the extension
            $filename = preg_replace("/\.png|\.jpg$/", ".svg", $filename);
        }
        $query = "SELECT openclipart_tags.name FROM openclipart_clipart INNER JOIN openclipart_users ON owner = openclipart_users.id INNER JOIN openclipart_clipart_tags ON clipart = openclipart_clipart.id INNER JOIN openclipart_tags ON tag = openclipart_tags.id WHERE filename = '$filename' AND username = '$user'";
        
        $clipart = new Clipart();
    }
    static function by_id($id) {
        global $app;
        $id = intval($id);
        $query = "SELECT * FROM openclipart_clipart";
        $query = "SELECT openclipart_tags.name FROM openclipart_tags INNER JOIN openclipart_clipart_tags ON tag = openclipart_tags.id WHERE clipart = $id";
        $tags = $app->db->get_column($query);
        $clipart = new Clipart($tags);
        $clipart->fetch_tags($id);
        
    }
    private function __construct($clipart, $tags) {
        $this->clipart = $clipart;
        $this->tags = $tags;
        $this->full_path = $app->config->root_directory . "/people/$user/$filename";
    }
    function __isset($name) {
        return isset($this->clipart);
    }
    function __get($name) {
        return $this->clipart[$name];
    }
    function exists() {
        return file_exists($this->full_path());
    }
    function size() {
        return filesize($this->full_path());
    }
    function nsfw() {
        return in_array('nsfw', $this->tags);
    }
    function have_pd_issue() {
        return in_array('pd_issue', $this->tags);
    }
    function have_issues() {
    }
    function inc_download() {
        global $app;
        $query = "UPDATE openclipart_clipart SET downloads = downloads + 1 WHERE owner = (SELECT id FROM openclipart_users WHERE $id = " . $this->id;
        $app->db->query($query);
    }
}