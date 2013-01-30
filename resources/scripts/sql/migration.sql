-- DATABASE CREATION FILE (WITH MIGRATION CODE FROM OLD AIKI+CCHOST DATABASE)

SET character_set_server = utf8;
SET character_set_database = utf8;
SET character_set_results = utf8;
SET character_set_connection = utf8;
SET collation_database = utf8_general_ci;
SET collation_server = utf8_general_ci;


SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE openclipart_users;
TRUNCATE openclipart_clipart;
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
SET FOREIGN_KEY_CHECKS = 1;

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
    -- use only the min user id, incase there are two usrs with the same username
    INNER JOIN ( SELECT MIN(userid) as userid 
       FROM aiki_users 
       GROUP by username ) minids 
     ON minids.userid = users.userid 
    LEFT OUTER JOIN openclipart_clipart clip 
      ON clip.owner = users.userid 
      AND RIGHT (users.avatar, 3) = 'svg' 
      AND clip.filename = users.avatar;

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


-- REMIXES

INSERT INTO openclipart_remixes 
  SELECT distinct tree_child AS clipart, tree_parent AS original
  FROM cc_tbl_tree
  -- these inner joins are to make sure the tree_parent and tree_child exist and
  -- dont violate any foreign key constraints
  INNER JOIN openclipart_clipart AS clipart1 ON clipart1.id = tree_child
  INNER JOIN openclipart_clipart AS clipart2 ON clipart2.id = tree_parent;

-- FAVORITES

INSERT IGNORE INTO openclipart_favorites 
  SELECT DISTINCT openclipart_clipart.id, openclipart_users.id, fav_date 
  FROM ocal_favs 
  INNER JOIN openclipart_clipart ON openclipart_clipart.id = clipart_id 
  INNER JOIN openclipart_users ON ocal_favs.username = openclipart_users.username;

-- COMMENTS

INSERT INTO openclipart_comments 
  SELECT topic_id, topic_upload
    , openclipart_users.id
    , topic_text
    , topic_date 
  FROM cc_tbl_topics 
  INNER JOIN openclipart_users ON openclipart_users.username = cc_tbl_topics.username 
  INNER JOIN openclipart_clipart ON openclipart_clipart.id = topic_upload
  WHERE topic_deleted = 0;

SELECT topic_id
  , topic_upload
  , openclipart_users.id
  , topic_text
  , topic_date 
  FROM cc_tbl_topics LEFT JOIN openclipart_users ON openclipart_users.username = cc_tbl_topics.username 
  WHERE topic_id = 2213;

-- TODO: USER == null - anonymous issues (unlogged captcha)

-- TODO: CLOSED or STATE and another table openclipart_issue_states

-- NSFW TAG

INSERT INTO openclipart_tags(name) VALUES('nsfw');

INSERT IGNORE INTO openclipart_clipart_tags SELECT id, (SELECT id FROM openclipart_tags WHERE name = 'nsfw') FROM ocal_files where nsfw = 1;


-- GROUPS

INSERT INTO openclipart_groups 
  VALUES(1, 'admin')
  , (2, 'librarian')
  , (3, 'banned')
  , (4, 'designer');

-- LINKS

INSERT INTO openclipart_links(title, url, user) 
  SELECT url_title, url, userid 
  FROM aiki_user_links 
  INNER JOIN openclipart_users ON openclipart_users.id = userid;

-- MESSAGES

INSERT INTO openclipart_messages(id, sender, receiver, date, title, content) 
  SELECT ocal_msgs.id
  , ( SELECT min(userid) 
      FROM aiki_users 
      WHERE username = written_by
  )
  , ( SELECT min(userid) 
      FROM aiki_users 
      WHERE username = written_to
  )
  , written_on
  , msg_title
  , msg_text 
  FROM ocal_msgs
  -- enforce the foreign key constraints on sender and recv
  INNER JOIN openclipart_users as sender ON sender.username = ocal_msgs.written_by
  INNER JOIN openclipart_users as recv ON recv.username = ocal_msgs.written_to;

-- CONTESTS

INSERT INTO openclipart_contests (user, name, title, content, create_date, deadline) 
  SELECT contest_user
  , contest_short_name
  , contest_friendly_name
  , contest_description
  , contest_created
  , contest_deadline 
  FROM cc_tbl_contests
  INNER JOIN openclipart_users ON openclipart_users.id = contest_user;

-- COLLECTIONS

INSERT INTO openclipart_collections 
  SELECT set_list_titles.id
  , ''
  , set_title
  , date_added
  , openclipart_users.id
  FROM set_list_titles
  INNER JOIN openclipart_users ON openclipart_users.username = set_list_titles.username;

INSERT INTO openclipart_collection_clipart 
  SELECT DISTINCT 
  image_id
  , set_list_id 
  FROM set_list_contents
  INNER JOIN openclipart_clipart ON openclipart_clipart.id = set_list_contents.image_id
  INNER JOIN openclipart_collections ON openclipart_collections.id = set_list_contents.set_list_id;

-- LOGS

INSERT INTO openclipart_log_type 
  VALUES (1, 'Login')
  , (2, 'Upload')
  , (3, 'Comment')
  , (4, 'Send Message')
  , (5, 'Delete Clipart')
  , (6, 'Modify Clipart')
  , (7, 'Report Issue')
  , (8, 'Vote')
  , (9, 'Favorite Clipart')
  , (10, 'Edit Button')
  , (11, 'Collection Create')
  , (12, 'Collection Delete')
  , (13, 'Add To Collection')
  , (14, 'Remove from Collection')
  , (15, 'Edit Profile')
  , (16, 'Change Avatar')
  , (17, 'Add Url')
  , (18, 'Register');

-- META

INSERT INTO openclipart_log_meta_type 
  VALUES 
    (1, 'User')
  , (2, 'Clipart')
  , (3, 'Collection')
  , (4, 'Message')
  , (5, 'Collection Item');

-- messages
INSERT INTO openclipart_logs 
  SELECT ocal_logs.id
  , openclipart_users.id
  , created_at
  , 4 
  FROM ocal_logs 
  INNER JOIN openclipart_users ON openclipart_users.username = ocal_logs.created_by
  WHERE log_type = 1;

INSERT INTO openclipart_log_meta 
  SELECT ocal_logs.id
  , 4
  , msg_id 
  FROM ocal_logs 
  INNER JOIN openclipart_logs ON openclipart_logs.id = ocal_logs.id
  WHERE log_type = 1;

-- comments
INSERT INTO openclipart_logs 
  SELECT id
  , (
    SELECT min(userid) 
    FROM aiki_users 
    WHERE username = created_by
  )
  , created_at
  , 3 
  FROM ocal_logs 
  WHERE log_type = 2;

INSERT INTO openclipart_log_meta 
  SELECT id
  , 2
  , image_id 
  FROM ocal_logs 
  WHERE log_type = 2;


-- logs

INSERT INTO openclipart_logs 
  SELECT ocal_logs.id
  , openclipart_users.id
  , created_at
  , 17 
  FROM ocal_logs 
  INNER JOIN openclipart_users ON openclipart_users.username = ocal_logs.created_by
  WHERE log_type = 3;

INSERT INTO openclipart_logs 
  SELECT ocal_logs.id
  , openclipart_users.id
  , created_at
  , 11 
  FROM ocal_logs 
  INNER JOIN openclipart_users ON openclipart_users.username = ocal_logs.created_by
  WHERE log_type = 5;

INSERT INTO openclipart_log_meta 
  SELECT id
  , 3
  , set_id 
  FROM ocal_logs 
  WHERE log_type = 5;

INSERT INTO openclipart_logs 
  SELECT 
  ocal_logs.id
  , openclipart_users.id
  , created_at
  , 13 
  FROM ocal_logs 
  INNER JOIN openclipart_users ON openclipart_users.username = ocal_logs.created_by
  WHERE log_type = 6;

INSERT INTO openclipart_log_meta 
  SELECT ocal_logs.id
  , 5
  , set_content_id 
  FROM ocal_logs 
  INNER JOIN openclipart_logs ON ocal_logs.id = openclipart_logs.id
  WHERE log_type = 6;

-- favorites

INSERT INTO openclipart_logs 
  SELECT ocal_logs.id
  , openclipart_users.id
  , created_at
  , 9 
  FROM ocal_logs 
  INNER JOIN openclipart_users ON openclipart_users.username = ocal_logs.created_by
  WHERE log_type = 7;

INSERT INTO openclipart_log_meta 
  SELECT id
  , 2
  , image_id 
  FROM ocal_logs 
  WHERE log_type = 7;

-- NEWS

INSERT INTO openclipart_news(link, title, date, content) 
  SELECT link
  , title
  , pubDate
  , content 
  FROM apps_planet_posts;

