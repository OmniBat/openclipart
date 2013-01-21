-- DATABASE CREATION FILE (WITH MIGRATION CODE FROM OLD AIKI+CCHOST DATABASE)

SET default_storage_engine=MYISAM;
SET character_set_server=utf8;
SET character_set_database=utf8;
SET character_set_results=utf8;
SET character_set_connection=utf8;
SET collation_database=utf8_general_ci;
SET collation_server=utf8_general_ci;

TRUNCATE openclipart_clipart;
TRUNCATE openclipart_users;
TRUNCATE openclipart_remixes;
TRUNCATE openclipart_favorites;
TRUNCATE openclipart_comments;
TRUNCATE openclipart_clipart_issues;
TRUNCATE openclipart_tags;
TRUNCATE openclipart_clipart_tags;
TRUNCATE openclipart_groups;
TRUNCATE openclipart_user_groups;
TRUNCATE openclipart_file_usage;
TRUNCATE openclipart_links;
TRUNCATE openclipart_messages;
TRUNCATE openclipart_contests;
TRUNCATE openclipart_collections;
TRUNCATE openclipart_collection_clipart;
TRUNCATE openclipart_log_type;
TRUNCATE openclipart_logs;
TRUNCATE openclipart_log_meta_type;
TRUNCATE openclipart_log_meta;

INSERT INTO openclipart_clipart(
  id, 
  filename, 
  title, 
  description, 
  owner, 
  sha1, 
  downloads, 
  hidden, 
  created
) SELECT 
  ocal_files.id, 
  filename, 
  upload_name, 
  upload_description, 
  users.userid, 
  sha1, 
  file_num_download, 
  not upload_published, 
  upload_date 
FROM ocal_files 
LEFT JOIN aiki_users users ON users.username = ocal_files.user_name 
INNER JOIN (
  SELECT MIN(userid) as userid 
  FROM aiki_users 
  GROUP by username
) minids ON minids.userid = users.userid;

-- copy non duplicate aiki_users
-- i commented this out for now because it was throwing errors during import -- vicapow
INSERT INTO openclipart_users(
  id, 
  username, 
  password, 
  full_name, 
  country, 
  email, 
  avatar, 
  homepage, 
  creation_date, 
  notify, 
  nsfw_filter
) SELECT minids.userid, 
         username, 
         password, 
         full_name, 
         country, 
         email, 
         clip.id as avatar, 
         homepage, 
         first_login, 
         notify, 
         nsfwfilter 
  FROM aiki_users users 
    -- use only the min user id, incase there are two usrs with the same user id
    INNER JOIN ( SELECT MIN(userid) as userid 
       FROM aiki_users 
       GROUP by username ) minids 
     ON minids.userid = users.userid 
    LEFT OUTER JOIN openclipart_clipart clip 
      ON clip.owner = users.userid 
      AND RIGHT (users.avatar, 3) = 'svg' 
      AND clip.filename = users.avatar;


-- REMIXES

INSERT INTO openclipart_remixes 
  SELECT distinct tree_child, tree_parent 
  FROM cc_tbl_tree;

-- FAVORITES

INSERT IGNORE INTO openclipart_favorites SELECT DISTINCT openclipart_clipart.id, openclipart_users.id, fav_date FROM ocal_favs LEFT JOIN openclipart_clipart ON openclipart_clipart.id = clipart_id LEFT JOIN openclipart_users ON ocal_favs.username = openclipart_users.username;

-- COMMENTS

INSERT INTO openclipart_comments SELECT topic_id, topic_upload, openclipart_users.id, topic_text, topic_date FROM cc_tbl_topics left join openclipart_users on openclipart_users.username = cc_tbl_topics.username WHERE topic_deleted = 0 AND topic_upload != 0;

SELECT topic_id, topic_upload, openclipart_users.id, topic_text, topic_date FROM cc_tbl_topics left join openclipart_users on openclipart_users.username = cc_tbl_topics.username where topic_id = 2213;

-- TODO: USER == null - anonymous issues (unlogged captcha)

-- TODO: CLOSED or STATE and another table openclipart_issue_states

-- NSFW TAG

INSERT INTO openclipart_tags(name) VALUES('nsfw');

INSERT IGNORE INTO openclipart_clipart_tags SELECT id, (SELECT id FROM openclipart_tags WHERE name = 'nsfw') FROM ocal_files where nsfw = 1;


-- GROUPS

INSERT INTO openclipart_groups VALUES(1, 'admin'), (2, 'librarian'), (3, 'banned'), (4, 'designer');

-- LINKS

INSERT INTO openclipart_links(title, url, user) SELECT url_title, url, userid FROM aiki_user_links;

-- MESSAGES

INSERT INTO openclipart_messages(id, sender, receiver, date, title, content) SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = written_by), (SELECT min(userid) FROM aiki_users WHERE username = written_to), written_on, msg_title, msg_text FROM ocal_msgs;

-- CONTESTS

INSERT INTO openclipart_contests(user, name, title, content, create_date, deadline) SELECT contest_user, contest_short_name, contest_friendly_name, contest_description, contest_created, contest_deadline from cc_tbl_contests;

-- COLLECTIONS

INSERT INTO openclipart_collections SELECT id, '', set_title, date_added, (SELECT min(userid) FROM aiki_users WHERE aiki_users.username = set_list_titles.username) FROM set_list_titles;

INSERT INTO openclipart_collection_clipart SELECT DISTINCT image_id, set_list_id FROM set_list_contents;

-- LOGS

INSERT INTO openclipart_log_type VALUES (1, 'Login'), (2, 'Upload'), (3, 'Comment'), (4, 'Send Message'), (5, 'Delete Clipart'), (6, 'Modify Clipart'), (7, 'Report Issue'), (8, 'Vote'), (9, 'Favorite Clipart'), (10, 'Edit Button'), (11, 'Collection Create'), (12, 'Collection Delete'), (13, 'Add To Collection'), (14, 'Remove from Collection'), (15, 'Edit Profile'), (16, 'Change Avatar'), (17, 'Add Url'), (18, 'Register');

-- META

INSERT INTO openclipart_log_meta_type VALUES (1, 'User'), (2, 'Clipart'), (3, 'Collection'), (4, 'Message'), (5, 'Collection Item');

-- messages
INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 4 FROM ocal_logs WHERE log_type = 1;

INSERT INTO openclipart_log_meta SELECT id, 4, msg_id FROM ocal_logs WHERE log_type = 1;

-- comments
INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 3 FROM ocal_logs WHERE log_type = 2;

INSERT INTO openclipart_log_meta SELECT id, 2, image_id FROM ocal_logs WHERE log_type = 2;

-- urls
INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 17 FROM ocal_logs WHERE log_type = 3;

-- new collection

INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 11 FROM ocal_logs WHERE log_type = 5;

INSERT INTO openclipart_log_meta SELECT id, 3, set_id FROM ocal_logs WHERE log_type = 5;

-- add clipart to collection
INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 13 FROM ocal_logs WHERE log_type = 6;

INSERT INTO openclipart_log_meta SELECT id, 5, set_content_id FROM ocal_logs WHERE log_type = 6;

-- favorites

INSERT INTO openclipart_logs SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = created_by), created_at, 9 FROM ocal_logs WHERE log_type = 7;

INSERT INTO openclipart_log_meta SELECT id, 2, image_id FROM ocal_logs WHERE log_type = 7;

-- NEWS

INSERT INTO openclipart_news(link, title, date, content) SELECT link, title, pubDate, content FROM apps_planet_posts;
