
SET default_storage_engine = InnoDB;

USE ocal;

-- USERS
CREATE TABLE IF NOT EXISTS openclipart_users(
  id integer NOT NULL auto_increment, 
  username varchar(255) UNIQUE, 
  password varchar(60), 
  full_name varchar(255), 
  country varchar(255), 
  email varchar(255), 
  avatar integer,
  homepage varchar(255), 
  twitter varchar(255),
  creation_date datetime, 
  about TEXT,
  notify boolean, 
  nsfw_filter boolean, 
  token varchar(40), 
  token_expiration datetime default null, 
  PRIMARY KEY(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;



CREATE TABLE IF NOT EXISTS openclipart_clipart(
  id integer NOT NULL auto_increment, 
  filename varchar(255), 
  title varchar(255), 
  link varchar(255), 
  description TEXT, 
  owner integer NOT NULL, 
  original_author VARCHAR(255) DEFAULT NULL, 
  sha1 varchar(40), 
  filesize INTEGER, 
  downloads INTEGER NOT NULL, 
  hidden boolean default 0, 
  created datetime, 
  modified datetime, 
  PRIMARY KEY(id),
  FOREIGN KEY(owner) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;


ALTER TABLE openclipart_users ADD FOREIGN KEY (avatar) REFERENCES openclipart_clipart(id);



-- REMIXES
CREATE TABLE IF NOT EXISTS openclipart_remixes(
  clipart integer NOT NULL, 
  original integer NOT NULL, 
  PRIMARY KEY(clipart, original), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(original) REFERENCES openclipart_clipart(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- FAVORITES

CREATE TABLE IF NOT EXISTS openclipart_favorites(
  clipart integer NOT NULL, 
  user integer NOT NULL, 
  date datetime, 
  PRIMARY KEY(clipart, user), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- COMMENTS

CREATE TABLE IF NOT EXISTS openclipart_comments(
  id INTEGER NOT NULL auto_increment, 
  clipart INTEGER NOT NULL, 
  user integer, 
  comment text, 
  date datetime, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- ISSUES

CREATE TABLE IF NOT EXISTS openclipart_clipart_issues(
  id integer NOT NULL auto_increment, 
  date datetime, 
  clipart integer NOT NULL, 
  user integer NOT NULL, 
  title VARCHAR(255), 
  comment TEXT, 
  closed boolean, 
  PRIMARY KEY(id), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- TAGS

CREATE TABLE IF NOT EXISTS openclipart_tags(
  id integer NOT NULL auto_increment,
  name varchar(255) UNIQUE, 
  PRIMARY KEY(id)
)  CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS openclipart_clipart_tags(
  clipart integer NOT NULL, 
  tag integer NOT NULL, 
  PRIMARY KEY(clipart, tag), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(tag) REFERENCES openclipart_tags(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- TAG COLLECTIONS

CREATE TABLE IF NOT EXISTS openclipart_tags_collection(
  id INTEGER NOT NULL auto_increment, 
  name VARCHAR(255), 
  creator INTEGER DEFAULT NULL, 
  created DATETIME, 
  last_archive_date DATETIME DEFAULT NULL, 
  PRIMARY KEY(id), FOREIGN KEY(creator) REFERENCES openclipart_users(id)
)  CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS openclipart_tags_collection_tag(
  tag INTEGER NOT NULL, 
  collection INTEGER NOT NULL, 
  added DATETIME, 
  PRIMARY KEY(tag, collection), 
  FOREIGN KEY(tag) REFERENCES openclipart_tags(id)
  -- TODO: tag collections
  -- FOREIGN KEY(collection) REFERENCES tag_collection(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- GROUPS

CREATE TABLE IF NOT EXISTS openclipart_groups(
  id integer NOT NULL auto_increment, 
  name varchar(255) UNIQUE, 
  PRIMARY KEY(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS openclipart_user_groups(
  user_group INTEGER NOT NULL, 
  user INTEGER NOT NULL, 
  PRIMARY KEY(user_group, user), 
  FOREIGN KEY(user_group) REFERENCES openclipart_groups(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;


-- CLIPART in USE [NEW]

CREATE TABLE IF NOT EXISTS openclipart_file_usage(
  id INTEGER NOT NULL auto_increment, 
  filename VARCHAR(255) NOT NULL, 
  clipart INTEGER NOT NULL, 
  user INTEGER DEFAULT NULL, 
  primary key(id), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- LINKS

CREATE TABLE IF NOT EXISTS openclipart_links(
  id INTEGER NOT NULL auto_increment, 
  title VARCHAR(255), 
  url VARCHAR(255), 
  user INTEGER NOT NULL, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- MESSAGES

CREATE TABLE IF NOT EXISTS openclipart_messages(
  id INTEGER NOT NULL auto_increment, 
  sender INTEGER NOT NULL, 
  receiver INTEGER NOT NULL, 
  reply_to INTEGER DEFAULT NULL, 
  date datetime, 
  title VARCHAR(255), 
  content TEXT, 
  readed boolean, 
  PRIMARY KEY(id), 
  FOREIGN KEY(sender) REFERENCES openclipart_users(id), 
  FOREIGN KEY(receiver) REFERENCES openclipart_users(id), 
  FOREIGN KEY(reply_to) REFERENCES openclipart_messages(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;


-- CONTESTS

CREATE TABLE IF NOT EXISTS openclipart_contests(
  id INTEGER NOT NULL auto_increment, 
  user INTEGER NOT NULL, 
  name VARCHAR(100) UNIQUE, 
  title VARCHAR(255), 
  image INTEGER DEFAULT NULL, 
  content TEXT, 
  create_date datetime, 
  deadline datetime, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id), 
  FOREIGN KEY(image) REFERENCES openclipart_clipart(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- COLLECTIONS

CREATE TABLE IF NOT EXISTS openclipart_collections(
  id INTEGER NOT NULL auto_increment, 
  name VARCHAR(255) DEFAULT NULL, 
  title VARCHAR(255), 
  date DATETIME, 
  user INTEGER NOT NULL, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS
openclipart_collection_clipart(
  clipart INTEGER NOT NULL, 
  collection INTEGER NOT NULL, 
  PRIMARY KEY(clipart, collection), 
  FOREIGN KEY(clipart) REFERENCES openclipart_clipart(id), 
  FOREIGN KEY(collection) REFERENCES openclipart_collections(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- LOGS

CREATE TABLE IF NOT EXISTS openclipart_log_type(
  id INTEGER NOT NULL auto_increment,
  name VARCHAR(100) UNIQUE, 
  PRIMARY KEY(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS openclipart_logs(
  id INTEGER NOT NULL auto_increment, 
  user INTEGER NOT NULL, 
  date DATETIME, 
  type INTEGER NOT NULL, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id), 
  FOREIGN KEY(type) REFERENCES openclipart_log_type(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;


CREATE TABLE IF NOT EXISTS openclipart_log_meta_type(
  id INTEGER NOT NULL auto_increment, 
  name VARCHAR(100), 
  PRIMARY KEY(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS openclipart_log_meta(
  log INTEGER NOT NULL, 
  type INTEGER NOT NULL, 
  value BLOB, 
  PRIMARY KEY(log, type), 
  FOREIGN KEY(log) REFERENCES openclipart_logs(id), 
  FOREIGN KEY(type) REFERENCES openclipart_log_meta_type(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;

-- NEWS

CREATE TABLE IF NOT EXISTS openclipart_news(
  id INTEGER NOT NULL auto_increment, 
  link VARCHAR(255) DEFAULT NULL, 
  title VARCHAR(255), 
  date DATETIME, 
  user INTEGER DEFAULT NULL, 
  content TEXT, 
  PRIMARY KEY(id), 
  FOREIGN KEY(user) REFERENCES openclipart_users(id)
) CHARACTER SET utf8 COLLATE utf8_general_ci;