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

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 'On');

define('DEBUG', true);

require_once('libs/utils.php');
require_once('libs/Template.php');
require_once('libs/System.php');
require_once('libs/Clipart.php');

/* TODO: logs (using slim) - same as apacha with gzip and numbering
 *                           cache all exceptions and log them
 *       cache in Template::render
 *          {{%cache_time:week}}  mustache pragma
 *
 *
 */

$app = new System(function() {
    /*
     *  System config need those variables
     *  db_user, db_host, db_pass, db_name (they are taken from config.json
     *  the file is disabled by .htaccess)
     */
    $config = get_object_vars(json_decode(file_get_contents('config.json')));
    $protocol = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
    return array_merge($config, array(
        'root' => $protocol . '://' . $_SERVER['HTTP_HOST'],
        'root_directory' => $_SERVER['DOCUMENT_ROOT'],
        'db_prefix' => 'openclipart',
        'tag_limit' => 100,
        'top_artist_last_month_limit' => 10,
        'home_page_thumbs_limit' => 8,
        'home_page_collections_limit' => 5,
        'home_page_news_limit' => 3,
        'bitmap_resolution_limit' => 3840, // number from old javascript
        'google_analytics' => false,
        // permission to functions in
        'permissions' => array(
            // JSON-RPC permissions
            'rpc' => array(
                'Admin' => array('admin')
            ),
            'access' => array(
                'disguise' => array('admin', 'developer'),
                'add_to_group' => array('admin'),
            ),
            // disguise fun is silent by default - executed in System constructor
            'silent' => array(),
            'disabled' => array()
        ),
        'show_facebook' => false,
        'debug' => true,
        // user     disguise as this user
        // track    initialy to disable download count in edit button
        //          can be use in different places
        // size     thumbail_size
        // token    you can browse site without cookies and php sessions
        //          using token in url, token will be send for users that forget
        //          passwords
        //          if token_expiration in database is null the time is infinite
        // sort     download, favorites, date
        // desc     for sort true or false
        // lang     for translation system
        'forward_query_list' => array(
            // TODO strip tags. or if Regex not match then ignore
            // nsfw = /true|false/
            'nsfw', 'track', 'user', 'size', 'token', 'sort', 'desc', 'lang'
        ),
        /*
        'forward_query_list' => array(
            'nsfw' => '/^(true|false)$/i',
            'track' => '/^(true|false)$/i',
            'user' => '/^[0-9]+$/',
            'size' => '/^[0-9]+(px|%)?$/i',
            'token' => '/^[0-9a-f]+$/i',
            'sort' => '/^(name|date|download|favorites)$/i',
            'desc' => '/^(true|false)$/i',
            'lang' => '/^(pl|es|js|de|zh)$/i'
        ),
        */
        'nsfw_image' => array(
            'user' => 'h0us3s',
            'filename' => 'h0us3s_Signs_Hazard_Warning_1'
        ),
        'pd_issue_image' => array(
            'user' => 'h0us3s',
            'filename' => 'h0us3s_Signs_Hazard_Warning_1'
        ),
    ));
});


function get_trace($exception) {
    $i = 0;
    return array_map(function($array) use (&$i) {
        global $app;
        $args = implode(', ', array_map(function($arg) {
            $type = gettype($arg);
            return $type == 'object' ? get_class($arg) : $type;
        }, $array['args']));
        $result = sprintf('%3d: ', $i++);
        if (isset($array['class']) && isset($array['type'])) {
            $result .= $array['class'] . $array['type'];
        }
        $result .= $array['function'] . '(' . $args . ')';
        if (isset($array['file'])) {
            $result .= ' in ' . str_replace($app->config->root_directory,
                                            '',
                                            $array['file']);
        }
        if (isset($array['line'])) {
            $result .= ' at ' . $array['line'];
        }
        return $result;
    }, $exception->getTrace());
}

function exception_string($exception) {
    global $app;
    return get_class($exception) . " " . $exception->getMessage() . " in file " .
        str_replace($app->config->root_directory, '', $exception->getFile()) .
        ' at ' . $exception->getLine();

}


class NiceExceptions extends Slim_Middleware_PrettyExceptions {
    protected function renderBody(&$env, $exception) {
        global $app;
        $main = new Template('main', function() use ($exception) {
            return array('content' => new Template('exception', function() use ($exception) {
                global $app;
                return array(
                    'name' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => str_replace($app->config->root_directory,
                                          '',
                                          $exception->getFile()),
                    'line' => $exception->getLine(),
                    'trace' => implode("\n", get_trace($exception)) //->getTraceAsString()
                );
            }));
        });
        return $main->render();
    }
}



$app->add(new NiceExceptions());


$app->get("/throw-exception", function() use ($app) {
    $array = array();
    return $array['x'];
});

$app->get("/foo", function() {
    return new Template('main', function() {
        return array('content' => 'hello');
    });
});


$app->notFound(function () use ($app) {
    $response = $app->response();
    $response['Content-Type'] = 'text/html'; // handlers can change it like /image
    return new Template('main', function() {
        return array('content' => new Template('error_404', null));
    });
});

$app->error(function(Exception $e) {
    echo 'error';
});


//TODO: Wrapp echo $main->render();

$app->map('/login', function() use ($app) {
    $error = null;
    if (isset($_POST['login']) && isset($_POST['password'])) {
        $redirect = isset($app->GET->redirect) ? $app->GET->redirect : $app->config->root;
        // TODO: redirect don't work
        try {
            $app->login($_POST['login'], $_POST['password']);
            $app->redirect($redirect);
            return;
        } catch (LoginException $e) {
            $error = $e->getMessage();
        }
    }
    $main = new Template('main', function() use ($error) {
        return array(
            'content' => array(new Template('login', function() use ($error) {
                return array(
                    // fill login on second attempt
                    'login' => isset($_POST['login']) ? $_POST['login'] : '',
                    'error' => $error
                );
            }))
        );
    });
    echo $main->render();
})->via('GET', 'POST');

$app->get("/logout", function() {
    global $app;
    $app->logout();
    if (isset($app->GET->redirect)) {
        $app->redirect($app->GET->redirect);
    } else {
        $main = new Template('main', function() {
            return array(
                'content' => '<p>You are now logged out</p>'
            );
        });
        echo $main->render();
    }
});

$app->get("/chat", function() {
    $main = new Template('main', function() {
        return array('content' => array(new Template('chat', null)));
    });
    echo $main->render();
});

$app->get("/detail/:id/:link", function($id, $link) use ($app) {

});

$app->get("/user-detail/:username", function($username) use ($app) {

});

function create_thumbs($where, $order_by) {
    global $app;
    if ($app->nsfw()) {
        $nsfw = "AND openclipart_clipart.id not in (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON tag = openclipart_tags.id WHERE name = 'nsfw')";
    } else {
        $nsfw = '';
    }
    if ($app->is_logged()) {
        $fav_check = $app->get_user_id() . ' in '.
            '(SELECT user FROM openclipart_favorites'.
            ' WHERE openclipart_clipart.id = clipart)';
    } else {
        $fav_check = '0';
    }
    if ($where != '' && $where != null) {
        $where = "AND $where";
    }
    $query = "SELECT openclipart_clipart.id, title, filename, link, created, username, count(DISTINCT user) as num_favorites, created, date, $fav_check as user_favm, downloads FROM openclipart_clipart INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON openclipart_users.id = owner WHERE openclipart_clipart.id NOT IN (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag WHERE clipart = openclipart_clipart.id AND openclipart_tags.name = 'pd_issue') $nsfw $where GROUP BY openclipart_clipart.id ORDER BY $order_by DESC LIMIT " . $app->config->home_page_thumbs_limit;
    $clipart_list = array();
    foreach ($app->db->get_array($query) as $row) {
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



$app->get('/', function() {
    global $app;
    $main = new Template('main', function() {
        return array(
            'content' => array(new Template('wellcome', null),
                  new Template('most_popular_thumbs', function() {
                      return array(
                          'content' => new Template('clipart_list', function() {
                              $last_week = "(SELECT WEEK(max(date)) FROM ".
                                  "openclipart_favorites) = WEEK(date) AND ".
                                  "YEAR(NOW()) = YEAR(date)";
                              return create_thumbs($last_week, "num_favorites");
                          })
                      );
                  }),
                  new Template('new_clipart_thumbs', function() {
                      return array(
                          'content' => new Template('clipart_list', function() {
                              return create_thumbs(null, "created");
                          })
                      );
                  }),
                  new Template('top_download_thumbs', function() {
                      return array(
                          'content' => new Template('clipart_list', function() {
                              $top_download = "YEAR(created) = YEAR(CURRENT_".
                                  "DATE) AND MONTH(created) = MONTH(CURRENT_".
                                  "DATE)";
                              return create_thumbs($top_download, "downloads");
                          })
                      );
                  })
            ),
            'sidebar' => array(
                new Template('join', null),
                new Template('facebook_box', null),
                new Template('follow_us_box', null),
                new Template('news_box', function() {
                    global $app;
                    $query = "SELECT link, title FROM openclipart_news ORDER by date DESC LIMIT " . $app->config->home_page_news_limit;
                    return array('news' =>
                                 array_reverse($app->db->get_array($query)));
                }),
                new Template('tag_cloud', function() {
                    global $app;
                    $query = "SELECT count(openclipart_tags.id) as tag_count  FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT 1";
                    $max = $app->db->get_value($query);
                    $query = "SELECT openclipart_tags.name, count(openclipart_tags.id) as tag_count FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT " . $app->config->tag_limit;
                    $result = array();
                    $rows = $app->db->get_array($query);
                    shuffle($rows);
                    $normalize = size('20', $max);
                    return array('tags' =>
                                 array_map(function($row) use ($normalize) {
                                     return array(
                                         'name' => $row['name'],
                                         'size' => $normalize($row['tag_count'])
                                     );
                                 }, $rows)
                    );
                }),
                new Template('top_artists_last_month', function() {
                    global $app;
                    $query = "SELECT full_name, username, count(filename) AS num_uploads FROM openclipart_clipart INNER JOIN openclipart_users ON owner = openclipart_users.id  WHERE date_format(created, '%Y-%c') = date_format(now(), '%Y-%c') GROUP BY openclipart_users.id ORDER BY num_uploads DESC LIMIT " . $app->config->top_artist_last_month_limit;
                    return array('artists' => $app->db->get_array($query));
                }),
                new Template('latest_collections_box', function() {
                    global $app;
                    $query = "SELECT openclipart_collections.id, name, title, username, date FROM openclipart_collections INNER JOIN openclipart_users ON user = openclipart_users.id ORDER BY date DESC LIMIT " . $app->config->home_page_collections_limit;
                    return array('collections' => array_map(function($row) {
                        return array_merge($row, array(
                            'human_date' => human_date($row['date'])
                        ));
                    }, $app->db->get_array($query)));
                })
            )
        ); //array('content'
    }); // new Template('main'
    echo $main->render();
});



$app->get('/clipart/:id/:link', function($id, $link) {
    $main = new Template('main', function() {
        return array('content' => array(
            new Template('clipart_detail', function() {
                
                $tags = "SELECT ";
                //editable - librarian or clipart owner
            })
        ));
    });
    return $main->render();
});


// routing /people/*.svg
$app->get('/download/svg/:user/:filename', function($user, $filename) {
    global $app;
    $clipart = new Clipart($user, $filename);
    if (!$clipart->exists($svg) || $clipart->size() == 0) {
        // old OCAL have some 0 size files
        $app->notFound();
    } else {
        $response = $app->response();
        $response['Content-Type'] = 'application/octet-stream';
        if ($app->track()) {
            $clipart->inc_download();
        }
        if ($app->nsfw() && $clipart->nsfw()) {
            $filename = $app->config->root_directory . "/people/" .
                $app->config->nsfw_image['user'] . "/" .
                $app->config->nsfw_image['filename'] . ".svg";
        } else if ($clipart->have_pd_issue()) {
            $filename = $app->config->root_directory . "/people/" .
                $app->config->pd_issue_image['user'] . "/" .
                $app->config->pd_issue_image['filename'] . ".svg";
        } else {
            $filename = $clipart->full_path();
        }
        echo file_get_contents($filename);
    }
});

$app->get('/image/:width/:user/:filename', function($w, $user, $file) {
    global $app;
    $width = intval($w);
    $svg_filename = preg_replace("/.png$/", '.svg', $file);
    $png = $app->config->root_directory . "/people/$user/${width}px-$file";
    $svg = $app->config->root_directory . "/people/$user/" . $svg_filename;
    $response = $app->response();
    $clipart = new Clipart($user, $file);
    if ($width > $app->config->bitmap_resolution_limit) {
        $response->status(400);
        // TODO: error template
        echo "Resolution couldn't be higher then 3840px! Please download SVG and " .
            "produce such huge bitmap locally.";
    } else if (!$clipart->exists() || $clipart->size() == 0) {
        // NOTE: you don't need to check user and file for script injection because
        //       file_exists will prevent this
        $app->notFound();
    } else {
        // NSFW check
        if ($app->nsfw() && $clipart->nsfw()) {
            $user = $app->config->nsfw_image['user'];
            $filename = $app->config->nsfw_image['filename'];
            $png = $app->config->root_directory . "/people/$user/${width}px-$file.png";
            $svg = $app->config->root_directory . "/people/$user/$filename.svg";
        }
        $response['Content-Type'] = 'image/png';
        if (file_exists($png)) {
            echo file_get_contents($png);
        } else {
            exec("rsvg --width $width $svg $png");
            if (!file_exists($png)) {
                $response['Content-Type'] = "text/html";
                $app->pass();
            } else {
                echo file_get_contents($png);
            }
        }
    }
});

$app->error(function($msg) use ($app) {
    $response = $app->response();
    $response['Content-Type'] = 'text/plain';
    $main = new Template('main', function() {
        return array('content' => $msg);
    });
    return $main->render();
});
/*
  $response = $app->response();
  $response->body($main->render());
 */
$app->get('/test', function() {
    global $app;
    $app->xx();
    echo isset($_GET['lang']) ? $_GET['lang'] : 'undefined';
    $response = $app->response();
    $response['Content-Type'] = 'text/plain';
    print_r($_SERVER) . "\n";
    echo $_SERVER['REQUEST_URI'] . "\n";
    echo 'nsfw: ' . $app->nsfw() ? 'true' : 'false';
    echo "\n";
    echo (empty($_GET) ? 'true' : 'false') . "\n";
    return "xxx";
    $main = new Template('test', function() {
        return array('foo' => function($query) {
            global $app;
            $array = $app->db->get_array($query);
            return implode(' | ', $array[0]);
        });
    });
    echo $main->render();
}); //->conditions(array('name' => '[0-9]*'));

$app->post("/notify-librarians-admins", function() {
    
});

// TODO: rpc permission system
/*
class Foo extends LibrarianPermission {

}
*/

$app->get('/download/collection/:name', function($name) {
    global $app;
    // name exists
    // check last count in field
    // check count using join    - can be in one query
    // if different create new archive
    $zip = new ZipArchive();
    // SQL for tag_collection info along with max date (JOIN GROUP BY)
    //$last_date =
    $collection = $app->db->get_row($query);
    $base = $app->config->root_directory . '/collections/' . $name . '-';
    // remove old collection archive
    if ($collection['last_archive_date'] != $collection['last_date']) {
        unlink($base . $collection['last_archive_date'] . '.zip');
        $zip_filename = $base . $collection['last_date'] . '.zip';
        $res = $zip->open($zip_filename, ZipArchive::CREATE);
        if (!$res) {
            throw new Exception("Can't create zip archive");
        }
        $zip->setArchiveComment("Open Clipart Library '$name' collection.");
        $archive = array();
        $dirs = array();
        foreach ($app->db->get_array($query) as $row) {
            $dir = $row['tag'];
            $local_filename =  $dir . '/' . $row['filename'];
            if (!in_array($dir, $dirs)) {
                if (!$zip->addEmptyDir($dir)) {
                    throw new Exception("Couldn't create directory '$dir' in".
                                        " zip file");
                }
                $dirs[] = $row['tag'];
            }
            
            if (array_key_exists($row['filename'], $archive)) {
                $i = ++$archive[$row['filename']];
                $local_filename = preg_replace("/\.svg$/",
                                               "_$i.svg",
                                               $local_filename);
            } else {
                $archive[$row['filename']] = 1;
            }
            $in_archive[] = $row['filename'];
            $filename = $app->config->root_directory . '/people/' .
                $row['user'] . '/' . $row['filename'];
            if (!$zip->addFile($filename, $local_filename)) {
                throw new Exception("Couldn't add file '$local_filename' to ".
                                    "the archive");
            }
        }
        $zip->close();
    } else {
        $zip_filename = $base . $collection['last_archive_date'] . '.zip';
    }
    if (!file_exists($zip_filename)) {
        $app->notFound();
    } else {
        // stream the archive
        $response = $app->response();
        $response['Content-Type'] = 'application/octet-stream';
        echo file_get_contents($zip_filename);
    }
});



$app->run();

?>
