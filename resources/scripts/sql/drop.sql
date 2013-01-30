SET default_storage_engine = InnoDB;

CREATE DATABASE IF NOT EXISTS ocal;
USE ocal;


DROP TABLE IF EXISTS openclipart_users;
DROP TABLE IF EXISTS openclipart_clipart;
DROP TABLE IF EXISTS openclipart_remixes;
DROP TABLE IF EXISTS openclipart_favorites;
DROP TABLE IF EXISTS openclipart_comments;
DROP TABLE IF EXISTS openclipart_clipart_issues;
DROP TABLE IF EXISTS openclipart_tags;
DROP TABLE IF EXISTS openclipart_clipart_tags;
DROP TABLE IF EXISTS openclipart_tags_collection;
DROP TABLE IF EXISTS openclipart_tags_collection_tag;
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
DROP TABLE IF EXISTS openclipart_news;