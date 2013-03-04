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
require_once('config.php');
require_once('Svg.php');

// Main class extend System to OCAL specific functions
class OCAL extends System{

    private $shutterstock_api_url = "http://api.shutterstock.com/images/search.json?all=0&page_number=1&category_id=29";
    private $shutterstock_api_login = "openclipart";
    private $shutterstock_api_key = "75f6916e802d2969ce255ad03f0316a817535922";
    
    public $svg;
    
    function __construct($settings){
        global $config;
        $this->svg = new Svg();
        $protocol = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
        $config['root'] = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $config['root_directory'] = $_SERVER['DOCUMENT_ROOT'] . '/public';
        System::__construct(array_merge($config, $settings));
    }
    
    function nsfw() {
      if(isset($this->config->nsfw)) return $this->config->nsfw;
      else if(isset($this->nsfw)) return $this->nsfw;
      return false;
    }
    function is_clipart_favorite($clipart, $userid){
      $clipart = intval($clipart);
      $userid = intval($userid);
      $query = "SELECT COUNT(*)
        FROM openclipart_favorites
        WHERE clipart = $clipart AND user = $userid";
      return $this->db->get_value($query);
    }
    function favorite($clipart, $userid){
      $clipart = intval($clipart);
      $userid = intval($userid);
      $query = "INSERT INTO 
        openclipart_favorites 
        VALUES($clipart, $userid, NOW()
      )";
      return $this->db->query($query);
    }
    
    function unfavorite($clipart, $userid) {
      $clipart = intval($clipart);
      $userid = intval($userid);
      $query = "DELETE FROM 
        openclipart_favorites 
        WHERE clipart = $clipart 
        AND user = $userid";
      return $this->db->query($query);
    }
    function user_num_favorites($userid){
      $userid = intval($userid);
      $query = "SELECT COUNT(*) 
        FROM openclipart_favorites
        WHERE user = $userid";
      return $this->db->get_value($query);
    }
    
    function and_not_nsfw(){
      return " AND openclipart_clipart.id 
      NOT IN (
        SELECT clipart 
        FROM openclipart_clipart_tags 
        INNER JOIN openclipart_tags ON tag = openclipart_tags.id 
        WHERE name = 'nsfw'
      ) ";
    }
    
    // ---------------------------------------------------------------------------------
    function list_clipart($where, $order_by) {
        
        if ($this->nsfw())
            $nsfw = " AND openclipart_clipart.id 
            NOT IN (
              SELECT clipart 
              FROM openclipart_clipart_tags 
              INNER JOIN openclipart_tags ON tag = openclipart_tags.id 
              WHERE name = 'nsfw'
            ) ";
        else $nsfw = '';
        
        $userid = $this->config->userid;
        
        if ($this->is_logged())
            $fav_check =  " $userid IN (
              SELECT user 
              FROM openclipart_favorites 
              WHERE openclipart_clipart.id = clipart
            )";
        else $fav_check = '0';
        
        if ( $where !== '' && $where !== null) $where = "AND $where";
        
        $query = "SELECT 
          openclipart_clipart.id
          , title
          , filename
          , link
          , created
          , username
          , (
            SELECT count(DISTINCT user) 
            FROM openclipart_favorites 
            WHERE clipart = openclipart_clipart.id
          ) as num_favorites
          , (
            SELECT max(date) 
            FROM openclipart_favorites 
            WHERE clipart = openclipart_clipart.id
          ) as last_date
          , date
          , $fav_check as user_fav
          , downloads 
        FROM openclipart_clipart 
        INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id 
        INNER JOIN openclipart_users ON openclipart_users.id = owner 
        WHERE openclipart_clipart.id NOT 
          IN (
            SELECT clipart 
            FROM openclipart_clipart_tags 
            INNER JOIN openclipart_tags ON openclipart_tags.id = tag 
            WHERE clipart = openclipart_clipart.id 
            AND openclipart_tags.name = 'pd_issue'
          ) $nsfw $where 
        GROUP BY openclipart_clipart.id 
        ORDER BY $order_by 
        DESC LIMIT 9";
        
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
        return $clipart_list;
    }
    
    function shutterstock_json($terms = null) {
        $auth_code = base64_encode($this->shutterstock_api_login . ":" .
                                   $this->shutterstock_api_key);
        $headers = array();
        $headers[] = "Authorization: Basic $auth_code";
        
        $terms = trim($terms);
        $terms = preg_replace('/\s+/','+',$terms);
        $url = $this->shutterstock_api_url;
        if ($terms != null) {
            $url .= '&searchterm='. $terms;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( $response_code != '200' ) {
            return null;
        } else {
            return json_decode($resp);
        }
    }
    
    function shutterstock($terms = null) {
        $shutter = $this->shutterstock_json($terms);
        if (!$shutter || $shutter->count == 0) {
            $shutter = $this->shutterstock_json();
        }
        if ($shutter->count > 6) {
            return array_slice($shutter->results, 0, 6);
        } else {
            return array();
        }
    }
    
    function get_user_last_modified_clipart($id){
      $id = intval($id);
      $query = "SELECT modified 
        FROM openclipart_clipart 
        WHERE owner = $id
        ORDER BY modified DESC
        LIMIT 1";
      return $this->db->get_value($query);
    }
    
    function get_user_uploads($id){
      $id = intval($id);
      $query = "SELECT COUNT(*)
        FROM openclipart_clipart
        WHERE owner = $id";
      return $this->db->get_value($query);
    }
    
    function get_user_roles($id){
      $id = intval($id);
      $query = "SELECT openclipart_groups.id as id, openclipart_groups.name as name 
        FROM openclipart_users 
        INNER JOIN openclipart_user_groups ON user = openclipart_users.id
        INNER JOIN openclipart_groups ON user_group = openclipart_groups.id
        WHERE openclipart_users.id = $id";
      return $this->db->get_array($query);
    }
    
    function user_set_avatar($username, $clipartid){
      $username = $this->db->escape($username);
      $clipartid = intval($clipartid);
      $query = "UPDATE openclipart_users SET avatar = $clipartid WHERE username = '$username'";
      $this->db->query($query);
    }
    
    function get_user_num_comments($id){
      $id = intval($id);
      $query = "SELECT COUNT(*) 
        FROM openclipart_comments 
        WHERE user = $id";
      return $this->db->get_value($query);
    }
    
    function get_user_tags($id){
      $id = intval($id);
      $query = "SELECT COUNT(*) 
        FROM openclipart_clipart 
        INNER JOIN openclipart_clipart_tags ON clipart = id 
        WHERE owner = $id";
      return $this->db->get_value($query);
    }
    
    function get_user($id){
      $query = "SELECT * FROM openclipart_users WHERE id = $id";
      return $this->db->get_row($query);
    }
    function get_user_by_name($username){
      $username = $this->db->escape($username);
      $query = "SELECT * FROM openclipart_users WHERE username = '$username'";
      return $this->db->get_row($query);
    }
    
    function num_user_clipart($username){
      $username = $this->db->escape($username);
      $query = "SELECT COUNT(*) 
                FROM openclipart_clipart 
                INNER JOIN openclipart_users
                WHERE owner = openclipart_users.id 
                AND openclipart_users.username = '$username'";
      return $this->db->get_value($query);
    }
    
    function num_clipart($nsfw = false){
      $join_username_clipart = $this->join_username_clipart();
      return $this->db->get_value("SELECT COUNT(*) FROM $join_username_clipart");
    }
    
    function join_username_clipart(){
      return "openclipart_clipart
        INNER JOIN openclipart_users
        WHERE owner = openclipart_users.id";
    }
    
    function new_clipart($nsfw = false, $start = null, $num = null){
      // optional args...
      if(empty($start)) $start = 9; //default number of results
      if(empty($num)){
        $num = $start;
        $start = 0;
      }
      
      $join_username_clipart = $this->join_username_clipart();
      
      if(!$nsfw) $and_nsfw = $this->and_not_nsfw();
      else $and_nsfw = '';
      
      $query = "SELECT 
        openclipart_clipart.id as id
        , title
        , filename
        , link
        , created
        , username
        FROM $join_username_clipart
        $and_nsfw
        ORDER BY created
        DESC
        LIMIT $start, $num";
      
      $cliparts = $this->db->get_array($query);
      return $this->add_filename($cliparts);
    }
    
    function popular_clipart($nsfw = false, $start = null, $num = null){
      if(empty($start)) $start = 9; //default number of results
      if(empty($num)){
        $num = $start;
        $start = 0;
      }
      if(!$nsfw) $and_nsfw = $this->and_not_nsfw();
      else $and_nsfw = '';
      
      $query = "SELECT
        openclipart_clipart.id as id
        , title
        , filename
        , link
        , created
        , username
        , (
          -- count the favorites younger than one week
          SELECT COUNT(*) 
          FROM openclipart_favorites 
          WHERE clipart = openclipart_clipart.id
          AND date > NOW() - INTERVAL 1 WEEK
        ) as num_favorites_this_week
        -- total count of favorites
        , (
          SELECT COUNT(*) 
          FROM openclipart_favorites
          WHERE clipart = openclipart_clipart.id
        ) as num_favorites
        FROM openclipart_clipart
        INNER JOIN openclipart_users
        WHERE owner = openclipart_users.id
        $and_nsfw
        ORDER BY num_favorites_this_week
        DESC
        LIMIT $start, $num";
      $cliparts = $this->db->get_array($query);
      return $this->add_filename($cliparts);
    }
    
    function user_clipart($username, $page, $results_per_page){
      $username = $this->db->escape($username);
      $start = $page * $results_per_page;
      $end = $start + $results_per_page;
      $query = "SELECT openclipart_clipart.id as id
                  , title
                  , filename
                  , link
                  , created
                  , username
                  FROM openclipart_clipart 
                  INNER JOIN openclipart_users 
                  WHERE owner = openclipart_users.id 
                  AND openclipart_users.username = '$username'
                  LIMIT $start, $end";
      $cliparts = $this->db->get_array($query);
      // set the filename_png
      return $this->add_filename($cliparts);
    }
    
    function username_from_id($id){
      $id = $this->db->escape($id);
      if(isset($this->user) && $this->user->id == $id) 
        return $this->user->username;
      $query = "SELECT username FROM openclipart_users WHERE id = '$id'";
      return $this->db->get_value($query);
    }
    
    function user_recent_clipart($username, $limit){
      $username = $this->db->escape($username);
      $query = "SELECT openclipart_clipart.id as id
                  , title
                  , filename
                  , link
                  , created
                  , username
                FROM openclipart_clipart
                INNER JOIN openclipart_users
                WHERE owner = openclipart_users.id 
                AND openclipart_users.username = '$username'
                ORDER BY created 
                DESC LIMIT $limit";
      $cliparts = $this->db->get_array($query);
      return $this->add_filename($cliparts);
    }
    
    function clipart_path($username, $filename){
      return $this->config->root_directory . '/people/' . $username . '/' . $filename;
    }
    
    function clipart_create($owner, $clipart){
      
      $app = $this;
      $e = function($str) use($app){
        return $app->db->escape($str);
      };
      
      $filename =     $e($clipart['filename']);
      $title =        $e($clipart['title']);
      $description =  $e($clipart['description']);
      $owner =        $e($owner);
      $author =       $e($clipart['author']);
      $filesize =     $e($clipart['filesize']);
      
      $query = "INSERT INTO openclipart_clipart ( 
                  filename
                  , title
                  , description
                  , owner
                  , filesize
                  , original_author
                  , created
                  , modified
                ) VALUES (
                  '$filename'
                  , '$title'
                  , '$description'
                  , $owner
                  , $filesize
                  , '$author'
                  , NOW()
                  , NOW()
                )";
      $ret = $this->db->query($query);
      $username = $this->username_from_id($owner);
      $path = $this->clipart_path( $username, $clipart['filename'] );
      
      //mkdirp 
      @mkdir(dirname($path),0777, true);
      $move_result = move_uploaded_file( $clipart['tmp_name'],  $path);
    }
    
    function clipart_filename_png($filename){
      return preg_replace("/.svg$/",".png", $filename);
    }
     
    function set_remix($clipart, $original){
      $clipart = intval($clipart);
      $original = intval($original);
      $query = "INSERT INTO openclipart_remixes(clipart, original) VALUES ($clipart, $original)";
      return $this->db->query($query);
    }
    function delete_clipart($id, $owner){
      $id = intval($id);
      $owner = intval($owner);
      $query = "UPDATE openclipart_clipart SET deleted = true WHERE id = $id AND $owner = $owner";
      $this->db->query($query);
    }
    function get_clipart($id){
      $id = intval($id);
      $query = "SELECT openclipart_clipart.id, title, filename, link, created,
          username, created, downloads, description 
          FROM openclipart_clipart 
          INNER JOIN openclipart_users ON owner = openclipart_users.id 
          WHERE openclipart_clipart.id = $id";
      return $this->db->get_row($query);
    }
    function user_num_remixes($id){
      $id= intval($id);
      $query = "SELECT COUNT(*) 
        FROM openclipart_remixes 
        INNER JOIN openclipart_clipart 
          ON openclipart_clipart.id = original 
        WHERE openclipart_clipart.owner = $id";
      return $this->db->get_value($query);
    }
    function user_num_remixed($id){
      $id = intval($id);
      $query = "SELECT COUNT(*)
        FROM openclipart_remixes
        INNER JOIN openclipart_clipart
          ON openclipart_clipart.id = clipart
        WHERE openclipart_clipart.owner = $id";
      return $this->db->get_value($query);
    }
    function clipart_by_tag($tag){
      
      $tag = $this->db->escape($tag);
      
      $query = "SELECT username, clipart.id as id, filename, title
                FROM openclipart_users
                INNER JOIN
                (
                  SELECT openclipart_clipart.id as id, filename, title, owner
                  FROM openclipart_clipart
                  INNER JOIN
                    ( -- all of the clipart ids with the tag $tag
                      SELECT * FROM openclipart_tags
                      INNER JOIN openclipart_clipart_tags 
                        ON openclipart_tags.id = openclipart_clipart_tags.tag
                        WHERE name = '$tag'
                    ) tags
                  ON tags.clipart = openclipart_clipart.id
                  ORDER BY downloads
                  LIMIT 10
                ) clipart
                ON clipart.owner = openclipart_users.id";
      
      $cliparts = $this->db->get_array($query);
      return $this->add_filename($cliparts);
    }
    
    function add_filename(&$cliparts){
      foreach($cliparts as $index => $clipart){
        $cliparts[$index]['filename_png'] = $this->clipart_filename_png($clipart['filename']);
      }
      return $cliparts;
    }
    
    // takes a string (ie., ' tag1,    tag2,tag3, tag4')
    // and splits it put into an array of tags 
    // (ie., ['tag1','tag2','tag3','tag4'])
    function split_tags($tag_str){
      $tags = preg_split("/[\s]*([,]|[\s]+)[\s]*/", $tag_str);
      foreach($tags as $key => $tag){
        $tag = $tags[$key] = preg_replace("/[^a-zA-Z0-9]/", "", $tag);
        if($tag === "") unset($tags[$key]);
      }
      return $tags;
    }
    
    function set_clipart_tags($clipid, $tags){
      
      $clipid = intval($clipid);
      $tag_ids = array();
      
      // ensure that these tags exists in `openclipart_tags`
      
      if(sizeof($tags)){
        $query = "INSERT IGNORE INTO openclipart_tags(name) 
                  VALUES ";
        $size = sizeof($tags);
        foreach($tags as $i => $tag){
          $tag = $this->db->escape($tag);
          $query .= " ('$tag') ";
          if( $i + 1 < $size) $query .= ", \n";
        }
        $this->db->query($query);
      
        // get the tag ids from the tag strings
        $query = "SELECT id FROM openclipart_tags WHERE ";
        foreach($tags as $i => $tag){
          $tag = $this->db->escape($tag);
          $query .= " name = '$tag' ";
          if( $i + 1 < $size ) $query .= " OR ";
        }
      
        $tag_ids = $this->db->get_array($query);
      }
      
      // remove old tags for this clipart
      $query = "DELETE IGNORE FROM openclipart_clipart_tags 
                WHERE clipart = $clipid";
      
      $this->db->query($query);
      
      if($tag_ids){
        // add these (clipartid, tagid) combinations
        $query = " INSERT IGNORE INTO openclipart_clipart_tags(clipart, tag)
                  VALUES ";
      
        $size = sizeof($tag_ids);
        foreach($tag_ids as $i => $tag){
          $tag_id = $tag['id'];
          $query .= " ('$clipid', '$tag_id') ";
          if( $i + 1 < $size ) $query .= ", \n ";
        }
        $this->db->query($query);
      }
    }
    
    function get_clipart_tags($clipartid){
      $clipartid = $this->db->escape($clipartid);
      $query = "SELECT name FROM openclipart_clipart_tags 
        INNER JOIN openclipart_tags ON tag = id WHERE clipart = $clipartid";
      $tags = $this->db->get_column($query);
      return $tags;
    }
    function get_users_by_group($group, $limit = 20){
      $group = $this->db->escape($group);
      $limit = intval($limit);
      $query = "SELECT
        users.id as id
        , username
        , full_name
        , country
        , email
        , avatar
        , homepage
        , twitter
        , creation_date
        , about
        , notify
        , nsfw_filter
        , token
        , token_expiration 
        FROM openclipart_users users 
        INNER JOIN openclipart_user_groups ON user = id
        INNER JOIN openclipart_groups ON openclipart_groups.id = user_group
        WHERE openclipart_groups.name = '$group'";
      if($limit !== 0) $query .= " LIMIT $limit";
      return $this->db->get_array($query);
    }
    function top_artists(){
      // just users by all time downloads, for now
      $query = "SELECT username, COUNT(downloads) as downloads
        FROM openclipart_users
        INNER JOIN openclipart_clipart ON openclipart_clipart.owner = openclipart_users.id
        GROUP BY username
        ORDER BY downloads
        DESC LIMIT 8";
      $artists = $this->db->get_array($query);
      return $artists;
    }
    
    function tags_by_downloads(){
      $query = "SELECT name, COUNT(downloads) as downloads 
        FROM openclipart_clipart_tags 
        INNER JOIN openclipart_tags ON openclipart_tags.id = openclipart_clipart_tags.tag
        LEFT JOIN openclipart_clipart ON openclipart_clipart.id = openclipart_clipart_tags.clipart
        GROUP BY name
        ORDER BY downloads
        DESC LIMIT 30";
      
      $tags = $this->db->get_array($query);
      return $tags;
    }
    
    function tag_counts($tags) {
        $db = $this->db;
        if(!is_array($tags) || sizeof($tags) === 0 ) return;
        $tags = implode(", ", array_map(function($tag) use ($db) {
            return "'". $db->escape($tag) . "'";
        }, $tags));
        $query = "SELECT name, COUNT(name) as count 
            FROM openclipart_clipart_tags 
            INNER JOIN openclipart_tags 
            ON tag = id 
            WHERE name IN ($tags) 
            GROUP BY name 
            ORDER BY count 
            DESC";
        return $db->get_array($query);
    }
    
    function get_clipart_comments($id){
      $id = intval($id);
      $query = "SELECT 
        openclipart_comments.id as id
        , username
        , user
        , clipart
        , comment
        , date FROM openclipart_comments 
        INNER JOIN openclipart_users 
          ON openclipart_users.id = openclipart_comments.user 
        WHERE clipart = $id";
      return $this->db->get_array($query);
    }
    
    function add_clipart_comment($clipart, $id, $text){
      $id = intval($id);
      $clipart = intval($clipart);
      $text = $this->db->escape($text);
      $query = "INSERT INTO openclipart_comments (clipart, user, comment, date) VALUES($clipart, $id, '$text', NOW() )";
      return $this->db->query($query);
    }
    
    function remove_clipart_comment($clipart, $user, $comment){
      $clipart = intval($clipart);
      $user = intval($user);
      $comment = intval($comment);
      $query = "DELETE FROM openclipart_comments WHERE clipart = $clipart AND user = $user AND id = $comment";
      return $this->db->query($query);
    }
    
    function get_news($id = null, $limit = 40){
      if(empty($id)){
        $query = "SELECT * FROM openclipart_news ORDER BY date DESC LIMIT $limit";
        return $this->db->get_array($query);
      }else{
        $id = intval($id);
        $ret = $this->db->get_array("SELECT * 
          FROM openclipart_news 
          WHERE id = $id");
        if(!empty($ret)) return $ret[0];
      }
    }
    
    function edit_news($id, $item){
      $title = $this->db->escape($item['title']);
      $link = $this->db->escape($item['link']);
      $content = $this->db->escape($item['content']);
      $id = intval($id);
      
      $query = "UPDATE openclipart_news
        SET title = '$title'
          , link = '$link'
          , content = '$content' 
        WHERE id = $id";
      return $this->db->query($query);
    }
    
    function add_news($item){
      $link = $this->db->escape($item['link']);
      $title = $this->db->escape($item['title']);
      $query = "INSERT INTO openclipart_news (link, title, date) VALUES ('$link', '$title', NOW() )";
      return $this->db->query($query);
    }
    
    function remove_news($id){
      $id = intval($id);
      return $this->db->query("DELETE FROM openclipart_news WHERE id = $id");
    }
}
