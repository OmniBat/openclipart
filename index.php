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
require_once('libs/OCAL.php');

/* TODO: logs (using slim) - same as apacha with gzip and numbering
 *                           cache all exceptions and log them
 *       cache in Template::render
 *          {{%cache_time:week}}  mustache pragma
 *
 *
 */


$app = new OCAL(array(
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
    /*'forward_query_list' => array(
      'nsfw' => '/^(true|false)$/i',
      'track' => '/^(true|false)$/i',
      'user' => '/^[0-9]+$/',
      'size' => '/^[0-9]+(px|%)?$/i',
      'token' => '/^[0-9a-f]+$/i',
      'sort' => '/^(name|date|download|favorites)$/i',
      'desc' => '/^(true|false)$/i',
      'lang' => '/^(pl|es|js|de|zh)$/i'
      ),*/
    'nsfw_image' => array(
        'user' => 'h0us3s',
        'filename' => 'h0us3s_Signs_Hazard_Warning_1'
    ),
    'pd_issue_image' => array(
        'user' => 'h0us3s',
        'filename' => 'h0us3s_Signs_Hazard_Warning_1'
    )
));

$app->error(function($exception) {
    global $app;
    return new Template('main', function() use ($exception) {
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
});


$app->notFound(function () use ($app) {
    return new Template('main', function() {
        return array('content' => new Template('error_404', null));
    });
});



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
    return new Template('main', function() use ($error) {
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
})->via('GET', 'POST');

$app->get("/logout", function() {
    global $app;
    $app->logout();
    if (isset($app->GET->redirect)) {
        $app->redirect($app->GET->redirect);
    } else {
        return new Template('main', function() {
            return array(
                'content' => '<p>You are now logged out</p>'
            );
        });
    }
});


$app->map('/register', function() use ($app) {
    // TODO: try catch that show json on ajax and throw exception so it will be cached
    //       by main error handler
    if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
        $msg = null;
        $success = false;
        $username = null;
        if (strip_tags($_POST['username']) != $_POST['username'] ||
            preg_match('/^[0-9A-Za-z_]+$/', $_POST['username']) == 0) {
            $msg = 'Sorry, but the username is invalid (you can use only letters, numbers'.
                ' and underscore)';
        } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $msg = 'Sorry, but email is invalid';
        } else if ($app->user_exist($_POST['username'])) {
            $msg = "Sorry, but this user already exist";
        } else if (!isset($_POST['picatcha']['r'])) {
            $msg = "Sorry, but you need to solve picatcha to prove that you're human";
            $username = $_POST['username'];
        } else {
            require('libs/picatchalib.php');
            $response = picatcha_check_answer($app->config->picatcha['private_key'],
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['HTTP_USER_AGENT'],
				$_POST['picatcha']['token'],
				$_POST['picatcha']['r']);
            if ($response->error == "incorrect-answer") {
                $msg = 'You give wrong anwser to Picatcha';
                $username = $_POST['username'];
            } else {
                if ($app->register($_POST['username'], $_POST['password'], $_POST['email'])) {
                    $msg = 'Your account has been created';
                    $success = true;
                } else {
                    $msg = 'Sorry, but something wrong happen and we couldn\'t create '.
                        'your account';
                    $username = $_POST['username'];
                }
            }
        }
        if ($success) {
            $url = $app->config->root . "/login";
            $subject = 'Welcome to Open Clipart Library';
            $username = $_POST['username'];
            $message = "Dear $username:\n\nYour registration at Open Clipart Library was " .
                "successful.\nPlease visit our site to sign in and get started:\n$url";
            $app->system_email($_POST['email'], $subject, $message);
        }
        if ($app->request()->isAjax()) {
            return json_encode(array('message' => $msg, 'status' => $success));
        } else {
            if ($success) {
                return new Template('main', array(
                    'content' => $msg
                ));
            } else {
                return new Template('main', array(
                    'content' => new Template('register', array(
                        'error' => $msg,
                        'email' => $_POST['email'], // so users don't need to type it twice
                        'username' => $username     // if user fail or forget picatcha
                    ))
                ));
            }
        }
    } else {
        return new Template('main', array(
            'content' => new Template('register', null)
        ));
    }
})->via('GET', 'POST');



$app->get("/chat", function() {
    return new Template('main', function() {
        return array('content' => array(new Template('chat', null)));
    });
});

$app->get("/detail/:id/:link", function($id, $link) use ($app) {

});

$app->get("/user-detail/:username", function($username) use ($app) {

});





$app->get('/', function() {
    global $app;
    return new Template('main', function() {
        return array(
            'content' => array(new Template('wellcome', null),
                  new Template('most_popular_thumbs', function() {
                      return array(
                          'content' => new Template('clipart_list', function() {
                              global $app;
                              $last_week = "(SELECT WEEK(max(date)) FROM ".
                                  "openclipart_favorites) = WEEK(date) AND ".
                                  "YEAR(NOW()) = YEAR(date)";
                              return $app->create_thumbs($last_week, "num_favorites");
                          })
                      );
                  }),
                  new Template('new_clipart_thumbs', function() {
                      return array(
                          'content' => new Template('clipart_list', function() {
                              global $app;
                              return $app->create_thumbs(null, "created");
                          })
                      );
                  }),
                  new Template('top_download_thumbs', function() {
                      /*
                      return array(
                          'content' => new Template('clipart_list', function() {
                              global $app;
                              $top_download = "YEAR(created) = YEAR(CURRENT_".
                                  "DATE) AND MONTH(created) = MONTH(CURRENT_".
                                  "DATE)";
                              return $app->create_thumbs($top_download, "downloads");
                          })
                      );
                      */
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
});



$app->get('/clipart/:id/:link', function($id, $link) {
    return new Template('main', function() {
        return array('content' => array(
            new Template('clipart_detail', function() {
                $tags = "SELECT ";
                //editable - librarian or clipart owner
            })
        ));
    });
});


// routing /people/*.svg
$app->get('/download/svg/:user/:filename', function($user, $filename) {
    global $app;
    $clipart = new Clipart($user, $filename);
    if (!$clipart->exists($svg) || $clipart->size() == 0) {
        // old OCAL have some 0 size files
        $app->notFound();
    } else {
        $response = $app->response()->header('Content-Type', 'application/octet-stream');
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
        $response->header('Content-Type', 'image/png');
        if (file_exists($png)) {
            echo file_get_contents($png);
        } else {
            exec("rsvg --width $width $svg $png");
            if (!file_exists($png)) {
                $app->pass();
            } else {
                echo file_get_contents($png);
            }
        }
    }
});


$app->post("/notify-librarians-admins", function() {

});


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
        $app->response()->header('Content-Type', 'application/octet-stream');
        echo file_get_contents($zip_filename);
    }
});

// -------------------------------------------------------------------------------
// :: TEST CODE


$app->get("/throw-exception", function() use ($app) {
    $array = array();
    return $array['x'];
});

$app->get("/foo", function() {
    return new Template('main', function() {
        return array('content' => 'hello');
    });
});

$app->get('/test', function() {
    global $app;
    $app->xx();
    echo isset($_GET['lang']) ? $_GET['lang'] : 'undefined';
    $app->response()->header('Content-Type', 'text/plain');
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


$app->run();
?>
