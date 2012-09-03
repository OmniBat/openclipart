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

require_once('System.php');

// Main class extend System to OCAL specific functions
class OCAL extends System {
    function __construct($settings) {
        $config = json_decode(file_get_contents('config.json'), true);
        $protocol = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
        $config['root'] = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $config['root_directory'] = $_SERVER['DOCUMENT_ROOT'];
        System::__construct(array_merge($config, $settings));
    }
    function nsfw() {
        return $this->config->get('nsfw', true);
        if ($this->is_logged()) {
            return $this->config->get('nsfw', $this->nfsw);
        } else {
            return true;
        }
    }
    function favorite($clipart) {
        if (!$this->is_logged()) {
            throw new Exception("You can't favorite a clipart if you are not logged in");
        }
    }
    function create_thumbs($where, $order_by) {
         if ($this->nsfw()) {
            $nsfw = "AND openclipart_clipart.id not in (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = openclipart_tags.id WHERE name = 'nsfw')";
        } else {
            $nsfw = '';
        }
        if ($this->is_logged()) {
            $fav_check = $this->get_user_id() . ' in '.
                '(SELECT user FROM openclipart_favorites'.
                ' WHERE openclipart_clipart.id = clipart)';
        } else {
            $fav_check = '0';
        }
        if ($where != '' && $where != null) {
            $where = "AND $where";
        }
        $query = "SELECT openclipart_clipart.id, title, filename, link, created, username, count(DISTINCT user) as num_favorites, created, date, $fav_check as user_favm, downloads FROM openclipart_clipart INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON openclipart_users.id = owner WHERE openclipart_clipart.id NOT IN (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag WHERE clipart = openclipart_clipart.id AND openclipart_tags.name = 'pd_issue') $nsfw $where GROUP BY openclipart_clipart.id ORDER BY $order_by DESC LIMIT " . $this->config->home_page_thumbs_limit;
        $clipart_list = array();
        foreach ($this->db->get_array($query) as $row) {
            $filename_png = preg_replace("/.svg$/",
                                         ".png",
                                         $row['filename']);
            $human_date = human_date($row['created']);
            $data = array(
                'filename_png' => $filename_png,
                'human_date' => $human_date
                //TODO: check when close this query
                //'user_fav' => false
            );
            $clipart_list[] = array_merge($row, $data);
        }
        return array('cliparts' => $clipart_list);
    }
}