<?php

class utils {
    function _echo($a) { return $a; }
    function nsfw() {
		global $db, $membership;
		if ($membership->userid) {
			$query = "select nsfwfilter from aiki_users where userid = ".
				$membership->userid;
			return $db->get_var($query);
		} else {
			return "0";
		}
    }

	function delete_file($filename) {
		// on new aiki trim can be removed
		$filename = trim($filename);
		if (preg_match('/^people.*\.svg$/', $filename) &&
			!preg_match('/\.\./', $filename)) {
			unlink($filename);
		}
	}

	function sphinx_ocal_search($term, $page, $num_per_page, $nsfw) {
		return $this->search($term, $page, $num_per_page, $nsfw, 'ocal_dev');
	}

	function sphinx_ocal_tags($term, $page, $num_per_page, $nsfw) {
		return $this->search($term, $page, $num_per_page, $nsfw, 'ocal_dev_tags');
	}

	function search($term, $page, $num_per_page, $nsfw, $query) {
		global $db, $aiki;
		if ($page > 0) {
			$page = $page-1;
		}
		require('sphinxapi.php');
		$cl = new SphinxClient();
		$cl->SetServer("localhost", 9312);
		$cl->SetMatchMode(SPH_MATCH_ALL);
		$cl->SetLimits($page*$num_per_page, (int)$num_per_page);
		if ($nsfw) {
			$cl->SetFilter("nsfw", array(0) );
		}
		$result = $cl->Query($term, $query);
		$num_of_results = $result['total'];
		if ($result === false) {
			return "<div>Query failed: " . $cl->GetLastError() . "</div>";
		} else {
			if ($cl->GetLastWarning()) {
				return "<div>WARNING: " . $cl->GetLastWarning() . "</div>";
			} else {
				if (!isset($result["matches"]) || $result["matches"] == null) {
					return "<p>Sorry, no matching clipart was found.</p>";
				} else {
					$ids = implode(', ', array_keys($result["matches"]));
					$query = "SELECT id, upload_date, filename, user_name, ".
						"upload_name, file_num_download, full_path, link, (".
						"SELECT count(DISTINCT ocal_favs.username) FROM oca".
						"l_favs WHERE ocal_files.id = ocal_favs.clipart_id)".
						" as favs FROM ocal_files WHERE id in ($ids);";
					$results = $db->get_results($query);
					if (!$results) {
						return "<div>" . mysql_error() . "</div>";
					} elseif(count($results) == 0) {
						return "<p>Sorry, no matching clipart was found.</p>";
					} else {
						$html = '<h2>Clipart search results for "'.$term .
							'" <a href="/media/feed/rss/GET[query]"><i'.
							'mg src="/assets/images/images/rss-sm.png"'.
							'></a></h2><br />';
						$aiki->load('web2date');
						foreach($results as $file) {
					
							$date = $aiki->web2date->parseweb2date($file->upload_date);
							$filename = str_replace('svg', 'png', $file->filename);

							$html .= '<div class="r-img"><div class="r-img-i">'.
								'<a href="/detail/'.$file->id.'/'.$file->link.
								'"><img alt="#" src="/image/90px/svg_to_png/'.
								$file->id.'/'.$filename.'"/></a></div>'.
            
								'<h4><a href="/detail/'.$file->id.'/'.$file->link.
								'">'.$file->upload_name.'</a></h4><p>by <a hr'.
								'ef="/user-detail/'.$file->user_name.'">'.
								$file->user_name.'</a><br />'.$date.'</p>'.

								'<p class="thumbnail_info_downloaded"><a clas'.
								's="download_hook" href="'.$file->full_path.
								$file->filename.'"><img src="/assets/images/d'.
								'ownload-icon.png" height="12px" alt="'.
								$file->upload_name.'" /></a> '.$file->file_num_download.
								'</p>'.
    
								'<div id="favorite_icon_'.$file->id.'" class="'.
								'favorite-icon"><div id="make_clipart_fav_'.
								$file->id.'" class="favorite-add '.$file->id.'">'.
								'<img src="/image/12px/svg_to_png/OCAL_Favori'.
								'tes_Icon_Unselected.png" alt="#"></div>'.
								'<div class="clipart_favs_num">'.$file->favs.
								'</div></div></div>';
						}
						if ($num_of_results < $num_per_page) {
							return $html;
						}
						//pagination
						$html .= '<p class="pagination">Move to page:<br>';
						if ($page>1) {
							$html .= '<a style="letter-spacing:0px;" href'.
								'="?query='.$term.'&amp;page='.($page-1).'">'.
								'<b>&lt; Previous</b></a>';
						}
						$num_of_pages = ceil($num_of_results/$num_per_page);
						$page = $page+1;
						$end = $page + 9;
						if ($end > $num_of_pages) {
							$end = $num_of_pages;
						}
						for ($i=1; $i<=$end; ++$i) {
							if ($i == $page) {
								$html .= '<b> '.$i.' </b>';
							} else {
								$html .= '<b> <a href="?query='.$term.'&amp;p'.
									'age='.$i.'">'.$i.'</a> </b>';
							}
						}
						if ($page < $num_of_pages) {
							$html .= '<a style="letter-spacing:0px;" href="?q'.
							'uery='.$term.'&amp;page='.($page+1).'"><b>Next &'.
							'gt;</b></a>';
						}
						$html .= '<br/>';
						if ($page > 1) {
							$html .= '<a style="letter-spacing:0px;" href="?q'.
								'uery='.$term.'&amp;page=1"><small>&lt;&lt; F'.
								'irst page</small></a>';
						}
						if ($page < $num_of_pages) {
							$html .= ' <a style="letter-spacing:0px;" href="?'.
								'query='.$term.'&amp;page='.$num_of_pages.'">'.
								'<small>Last page &gt;&gt;</small></a>';
						}
						$html .= '</p>';
						return $html;
					}
				}
			}
		}
	}

}

?>
. $args . ')';
        if (isset($array['file'])) {
            $result .= ' in ' . str_replace($_SERVER['DOCUMENT_ROOT'],
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
    return get_class($exception) . " " . $exception->getMessage() . " in file " .
        str_replace($_SERVER['DOCUMENT_ROOT'], '', $exception->getFile()) .
        ' at ' . $exception->getLine();

}

function full_exception_string($exception, $separator="\n") {
    return exception_string($exception) . $separator .
        implode($separator, get_trace($exception));
}

// copied from AIKI
function get_string_between($string, $start, $end) {
    $ini = strpos($string,$start);
    if ($ini === false) {
        return "";
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}