-- DATABASE MIGRATION FILE

DROP TABLE IF EXISTS openclipart_clipart;
DROP TABLE IF EXISTS openclipart_users;
DROP TABLE IF EXISTS openclipart_remixes;
DROP TABLE IF EXISTS openclipart_favorites;
DROP TABLE IF EXISTS openclipart_comments;
DROP TABLE IF EXISTS openclipart_clipart_issues;
DROP TABLE IF EXISTS openclipart_tags;
DROP TABLE IF EXISTS openclipart_clipart_tags;
DROP TABLE IF EXISTS openclipart_groups;
DROP TABLE IF EXISTS openclipart_user_groups;
DROP TABLE IF EXISTS openclipart_file_usage;
DROP TABLE IF EXISTS openclipart_links;
DROP TABLE IF EXISTS openclipart_messages;
DROP TABLE IF EXISTS openclipart_contests;
DROP TABLE IF EXISTS openclipart_collections;
DROP TABLE IF EXISTS openclipart_collection_clipart;
DROP TABLE IF EXISTS openclipart_log_type;
DROP TABLE IF EXISTS openclipart_logs;
DROP TABLE IF EXISTS openclipart_log_meta_type;
DROP TABLE IF EXISTS openclipart_log_meta;


-- FILES
CREATE TABLE openclipart_clipart(id integer NOT NULL auto_increment, filename varchar(255), title varchar(255), link varchar(255), description TEXT, owner integer NOT NULL, original_author VARCHAR(255) DEFAULT NULL, sha1 varchar(40), filesize INTEGER, downloads integer, hidden boolean default 0, created datetime, modifed datetime, PRIMARY KEY(id), FOREIGN KEY(owner) REFERENCES openclipart_users(id));

INSERT INTO openclipart_clipart(id, filename, title, description, owner, sha1, downloads, hidden, created) SELECT ocal_files.id, filename, upload_name, upload_description, users.userid, sha1, file_num_download, not upload_published, upload_date FROM ocal_files LEFT JOIN aiki_users users ON users.username = ocal_files.user_name INNER JOIN (SELECT MIN(userid) as userid FROM aiki_users GROUP by username) minids ON minids.userid = users.userid;


-- USERS
CREATE TABLE openclipart_users(id integer NOT NULL auto_increment, username varchar(255) UNIQUE, password varchar(60), full_name varchar(255), country varchar(255), email varchar(255), avatar integer, homepage varchar(255), creation_date datetime, notify boolean, nsfw_filter boolean, token varchar(40), token_expiration datetime default null, PRIMARY KEY(id), FOREIGN KEY(avatar) REFERENCES openclipart_clipart(id));

-- copy non duplicate aiki_users

INSERT INTO openclipart_users(id, username, password, full_name, country, email, avatar, homepage, user_group, creation_date, notify, nsfw_filter) SELECT minids.userid, username, password, full_name, country, email, clip.id as avatar, homepage, first_login, notify, nsfwfilter FROM aiki_users users INNER JOIN (SELECT MIN(userid) as userid FROM aiki_users GROUP by username) minids ON minids.userid = users.userid LEFT OUTER JOIN openclipart_clipart clip ON clip.owner = users.userid AND RIGHT(users.avatar, 3) = 'svg' AND clip.filename = users.avatar;

-- REMIXES

CREATE TABLE openclipart_remixes(clipart integer NOT NULL, original integer NOT NULL, PRIMARY KEY(clipart, original), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(original) REFERENCES openclipart_clipart(id));

INSERT INTO openclipart_remixes SELECT distinct tree_child, tree_parent FROM cc_tbl_tree;

-- FAVORITES

CREATE TABLE openclipart_favorites(clipart integer NOT NULL, user integer NOT NULL, date datetime, PRIMARY KEY(clipart, user), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT IGNORE INTO openclipart_favorites SELECT DISTINCT openclipart_clipart.id, openclipart_users.id, fav_date FROM ocal_favs LEFT JOIN openclipart_clipart ON openclipart_clipart.id = clipart_id LEFT JOIN openclipart_users ON ocal_favs.username = openclipart_users.username;

-- COMMENTS

CREATE TABLE openclipart_comments(id INTEGER NOT NULL auto_increment, clipart INTEGER NOT NULL, user integer NOT NULL, comment text, date datetime, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id));

INSERT INTO openclipart_comments SELECT topic_id, topic_upload, openclipart_users.id, topic_text, topic_date FROM cc_tbl_topics left join openclipart_users on openclipart_users.username = cc_tbl_topics.username WHERE topic_deleted = 0 AND topic_upload != 0;

SELECT topic_id, topic_upload, openclipart_users.id, topic_text, topic_date FROM cc_tbl_topics left join openclipart_users on openclipart_users.username = cc_tbl_topics.username where topic_id = 2213

-- ISSUES [NEW]

CREATE TABLE openclipart_clipart_issues(id integer NOT NULL auto_increment, date datetime, clipart integer NOT NULL, user integer NOT NULL, title VARCHAR(255), comment TEXT, closed boolean, PRIMARY KEY(id), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(user) REFERENCES openclipart_user(id));

-- TODO: USER == null - anonymous issues (unlogged captcha)

-- TODO: CLOSED or STATE and another table openclipart_issue_states

-- TAGS

CREATE TABLE openclipart_tags(id integer NOT NULL auto_increment, name varchar(255) UNIQUE, PRIMARY KEY(id));

CREATE TABLE openclipart_clipart_tags(clipart integer NOT NULL, tag integer NOT NULL, PRIMARY KEY(clipart, tag), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(tag) REFERENCES openclipart_tags(id));


-- NSFW TAG

INSERT INTO openclipart_tags(name) VALUES('nsfw');

INSERT IGNORE INTO openclipart_clipart_tags SELECT id, (SELECT id FROM openclipart_tags WHERE name = 'nsfw') FROM ocal_files where nsfw = 1;

-- TAG COLLECTIONS

CREATE TABLE tag_collection(id INTEGER NOT NULL auto_increment, name VARCHAR(255), creator INTEGER DEFAULT NULL, created DATETIME, last_archive_date DATETIME DEFAULT NULL, PRIMARY KEY(id), FOREIGN KEY(creator) REFERENCES openclipart_users(id));

CREATE TABLE tag_collection_tag(tag INTEGER NOT NULL, collection INTEGER NOT NULL, added DATETIME, PRIMARY KEY(tag, collection), FOREIGN KEY(tag) REFERENCES openclipart_tags(id), FOREIGN KEY(collection) REFERENCES tag_collection(id));


-- GROUPS

CREATE TABLE openclipart_groups(id integer NOT NULL auto_increment, name varchar(255) UNIQUE, PRIMARY KEY(id));

INSERT INTO openclipart_groups VALUES(1, 'admin'), (2, 'librarian');

CREATE TABLE openclipart_user_groups(user_group INTEGER NOT NULL, user INTEGER NOT NULL, PRIMARY KEY(user_group, user), FOREIGN KEY(user_group) REFERENCES openclipart_groups(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));





-- CLIPART in USE [NEW]

CREATE TABLE openclipart_file_usage(id INTEGER NOT NULL auto_increment, filename VARCHAR(255), clipart INTEGER NOT NULL, user INTEGER DEFAULT NULL, primary key(id), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

-- user can be NULL for unlogged users ("I use this clipart" button, captcha and text box) librarians will check it and assign to Anonymous account (or different shared account).

-- LINKS

CREATE TABLE openclipart_links(id INTEGER NOT NULL auto_increment, title VARCHAR(255), url VARCHAR(255), user INTEGER NOT NULL, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT INTO openclipart_links(title, url, user) SELECT url_title, url, userid FROM aiki_user_links;

-- MESSAGES

CREATE TABLE openclipart_messages(id INTEGER NOT NULL auto_increment, sender INTEGER NOT NULL, receiver INTEGER NOT NULL, reply_to INTEGER DEFAULT NULL, date datetime, title VARCHAR(255), content TEXT, readed boolean, PRIMARY KEY(id), FOREIGN KEY(sender) REFERENCES openclipart_users(id), FOREIGN KEY(receiver) REFERENCES openclipart_users(id), FOREIGN KEY(reply_to) REFERENCES openclipart_messages(id));

INSERT INTO openclipart_messages(id, sender, receiver, date, title, content) SELECT id, (SELECT min(userid) FROM aiki_users WHERE username = written_by), (SELECT min(userid) FROM aiki_users WHERE username = written_to), written_on, msg_title, msg_text FROM ocal_msgs;

-- CONTESTS

CREATE TABLE openclipart_contests(id INTEGER NOT NULL auto_increment, user INTEGER NOT NULL, name VARCHAR(100) UNIQUE, title VARCHAR(255), image INTEGER DEFAULT NULL, content TEXT, create_date datetime, deadline datetime, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id), FOREIGN KEY(image) REFERENCES openclipart_clipart(id));

INSERT INTO openclipart_contests(user, name, title, content, create_date, deadline) SELECT contest_user, contest_short_name, contest_friendly_name, contest_description, contest_created, contest_deadline from cc_tbl_contests;

-- COLLECTIONS

CREATE TABLE openclipart_collections(id INTEGER NOT NULL auto_increment, name VARCHAR(255) DEFAULT NULL, title VARCHAR(255), date DATETIME, user INTEGER NOT NULL, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT INTO openclipart_collections SELECT id, '', set_title, date_added, (SELECT min(userid) FROM aiki_users WHERE aiki_users.username = set_list_titles.username) FROM set_list_titles;

CREATE TABLE openclipart_collection_clipart(clipart INTEGER NOT NULL, collection INTEGER NOT NULL, PRIMARY KEY(clipart, collection), FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), FOREIGN KEY(collection) REFERENCES openclipart_collections(id));

INSERT INTO openclipart_collection_clipart SELECT DISTINCT image_id, set_list_id FROM set_list_contents;

-- LOGS

CREATE TABLE openclipart_log_type(id INTEGER NOT NULL auto_increment, name VARCHAR(100) UNIQUE, PRIMARY KEY(id));

INSERT INTO openclipart_log_type VALUES (1, 'Login'), (2, 'Upload'), (3, 'Comment'), (4, 'Send Message'), (5, 'Delete Clipart'), (6, 'Modify Clipart'), (7, 'Report Issue'), (8, 'Vote'), (9, 'Favorite Clipart'), (10, 'Edit Button'), (11, 'Collection Create'), (12, 'Collection Delete'), (13, 'Add To Collection'), (14, 'Remove from Collection'), (15, 'Edit Profile'), (16, 'Change Avatar'), (17, 'Add Url'), (18, 'Register');

CREATE TABLE openclipart_logs(id INTEGER NOT NULL auto_increment, user INTEGER NOT NULL, date DATETIME, type INTEGER NOT NULL, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id), FOREIGN KEY(type) REFERENCES openclipart_log_type(id));

CREATE TABLE openclipart_log_meta_type(id INTEGER NOT NULL auto_increment, name VARCHAR(100), PRIMARY KEY(id));

CREATE TABLE openclipart_log_meta(log INTEGER NOT NULL, type INTEGER NOT NULL, value BLOB, PRIMARY KEY(log, type), FOREIGN KEY(log) REFERENCES openclipart_logs(id), FOREIGN KEY(type) REFERENCES openclipart_log_meta_type(id));

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

CREATE TABLE openclipart_news(id INTEGER NOT NULL auto_increment, link VARCHAR(255) DEFAULT NULL, title VARCHAR(255), date DATETIME, user INTEGER DEFAULT NULL, content TEXT, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT INTO openclipart_news(link, title, date, content) SELECT link, title, pubDate, content FROM apps_planet_posts;
