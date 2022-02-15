# ************************************************************
# Sequel Ace SQL dump
# Version 2104
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: 127.0.0.1 (MySQL 5.7.29)
# Database: icms8
# Generation Time: 2021-04-23 09:56:13 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
SET NAMES utf8;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table icms_admincp_log
# ------------------------------------------------------------

CREATE TABLE `icms_admincp_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `username` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `app` varchar(255) NOT NULL DEFAULT '' COMMENT '应用',
  `uri` varchar(1024) NOT NULL DEFAULT '' COMMENT '资源',
  `useragent` varchar(512) NOT NULL DEFAULT '' COMMENT 'UA',
  `ip` varchar(255) NOT NULL DEFAULT '' COMMENT '用户IP',
  `method` varchar(255) NOT NULL DEFAULT '' COMMENT '请求方法',
  `referer` varchar(1024) NOT NULL DEFAULT '' COMMENT '来路',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `app` (`app`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_apps
# ------------------------------------------------------------

CREATE TABLE `icms_apps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '应用ID appid',
  `app` varchar(100) NOT NULL DEFAULT '' COMMENT '应用标识',
  `rootid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父应用',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '应用名',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '应用标题',
  `apptype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型 0官方 1本地 2自定义',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '应用类型',
  `table` text NOT NULL COMMENT '应用表',
  `config` text NOT NULL COMMENT '应用配置',
  `fields` text NOT NULL COMMENT '应用自定义字段',
  `route` text NOT NULL COMMENT '应用路由',
  `menu` varchar(100) NOT NULL DEFAULT '' COMMENT '应用菜单',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '应用状态',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_apps_field
# ------------------------------------------------------------

CREATE TABLE `icms_apps_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(255) NOT NULL DEFAULT '' COMMENT '所属应用',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用APPID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '字段名称',
  `field` varchar(255) NOT NULL DEFAULT '' COMMENT '字段名',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT '字段类型',
  `length` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '字段长度',
  `default` varchar(255) NOT NULL DEFAULT '' COMMENT '字段默认值',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '字段注释',
  `dataType` varchar(255) NOT NULL DEFAULT '' COMMENT '数据类型',
  `group` varchar(255) NOT NULL DEFAULT '' COMMENT '分组',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `config` mediumtext NOT NULL COMMENT '其它配置',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_apps_meta
# ------------------------------------------------------------

CREATE TABLE `icms_apps_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_apps_store
# ------------------------------------------------------------

CREATE TABLE `icms_apps_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL DEFAULT '0' COMMENT 'store id',
  `appid` int(10) NOT NULL DEFAULT '0' COMMENT 'appid',
  `app` varchar(255) NOT NULL DEFAULT '' COMMENT 'app',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `version` varchar(255) NOT NULL DEFAULT '' COMMENT '版本',
  `authkey` varchar(255) NOT NULL DEFAULT '' COMMENT 'authkey',
  `git_sha` varchar(255) NOT NULL DEFAULT '' COMMENT 'git sha',
  `git_time` int(10) NOT NULL DEFAULT '0' COMMENT 'git版本时间',
  `transaction_id` varchar(255) NOT NULL DEFAULT '' COMMENT '订单号',
  `data` text NOT NULL COMMENT '信息',
  `files` mediumtext NOT NULL COMMENT '资源列表',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '安装时间',
  `uptime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'app:0 tpl:1',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_archive
# ------------------------------------------------------------

CREATE TABLE `icms_archive` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `index_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `node_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id',
  `second_node_id` varchar(255) NOT NULL DEFAULT '0' COMMENT '副栏目',
  `user_node_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户分类',
  `porp_id` varchar(255) NOT NULL DEFAULT '' COMMENT '属性',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `stitle` varchar(255) NOT NULL DEFAULT '' COMMENT '短标题',
  `clink` varchar(255) NOT NULL DEFAULT '' COMMENT '自定义链接',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '外部链接',
  `source` varchar(255) NOT NULL DEFAULT '' COMMENT '出处',
  `author` varchar(255) NOT NULL DEFAULT '' COMMENT '作者',
  `editor` varchar(255) NOT NULL DEFAULT '' COMMENT '编辑',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `haspic` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有缩略图',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `bpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图1',
  `mpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图2',
  `spic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图3',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `description` varchar(5120) NOT NULL DEFAULT '' COMMENT '摘要',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `postime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交时间',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总点击数',
  `hits_today` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天点击数',
  `hits_yday` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '昨天点击数',
  `hits_week` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `hits_month` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `favorite` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `comments` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `good` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '顶',
  `bad` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读点数',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `creative` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '文章类型 1原创 0转载',
  `chapter` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '章节',
  `weight` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `mobile` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1手机发布 0 pc',
  `postype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型 0用户 1管理员',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '[[0:草稿],[1:正常],[2:回收],[3:审核],[4:不合格]]',
  PRIMARY KEY (`id`),
  KEY `id` (`status`,`id`),
  KEY `indexId` (`status`,`index_id`,`appid`),
  KEY `nodeId` (`status`,`node_id`),
  KEY `pubdate` (`status`,`pubdate`),
  KEY `hits` (`status`,`hits`),
  KEY `hits_week` (`status`,`hits_week`),
  KEY `hits_month` (`status`,`hits_month`),
  KEY `nodeId_id` (`status`,`node_id`,`id`),
  KEY `nodeId_hits` (`status`,`node_id`,`hits`),
  KEY `nodeId_week` (`status`,`node_id`,`hits_week`),
  KEY `s_index` (`appid`,`index_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_article
# ------------------------------------------------------------

CREATE TABLE `icms_article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目id',
  `scid` varchar(255) NOT NULL DEFAULT '' COMMENT '副栏目',
  `ucid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户分类',
  `pid` varchar(255) NOT NULL DEFAULT '' COMMENT '属性',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `stitle` varchar(255) NOT NULL DEFAULT '' COMMENT '短标题',
  `clink` varchar(255) NOT NULL DEFAULT '' COMMENT '自定义链接',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '外部链接',
  `source` varchar(255) NOT NULL DEFAULT '' COMMENT '出处',
  `author` varchar(255) NOT NULL DEFAULT '' COMMENT '作者',
  `editor` varchar(255) NOT NULL DEFAULT '' COMMENT '编辑',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `haspic` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有缩略图',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `bpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图1',
  `mpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图2',
  `spic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图3',
  `picdata` varchar(255) NOT NULL DEFAULT '' COMMENT '图片数据',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `description` varchar(5120) NOT NULL DEFAULT '' COMMENT '摘要',
  `related` text NOT NULL COMMENT '相关',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `postime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交时间',
  `tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '模板',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总点击数',
  `hits_today` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天点击数',
  `hits_yday` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '昨天点击数',
  `hits_week` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `hits_month` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `favorite` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `comment` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `good` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '顶',
  `bad` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读点数',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `creative` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '文章类型 1原创 0转载',
  `chapter` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '章节',
  `weight` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `markdown` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'markdown标识',
  `mobile` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1手机发布 0 pc',
  `postype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型 0用户 1管理员',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '[[0:草稿],[1:正常],[2:回收],[3:审核],[4:不合格]]',
  PRIMARY KEY (`id`),
  KEY `id` (`status`,`id`),
  KEY `cid` (`status`,`cid`),
  KEY `pubdate` (`status`,`pubdate`),
  KEY `hits` (`status`,`hits`),
  KEY `hits_week` (`status`,`hits_week`),
  KEY `hits_month` (`status`,`hits_month`),
  KEY `cid_id` (`status`,`cid`,`id`),
  KEY `cid_hits` (`status`,`cid`,`hits`),
  KEY `cid_week` (`status`,`cid`,`hits_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_article_data
# ------------------------------------------------------------

CREATE TABLE `icms_article_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `body` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_article_meta
# ------------------------------------------------------------

CREATE TABLE `icms_article_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_chain
# ------------------------------------------------------------

CREATE TABLE `icms_chain` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(190) NOT NULL DEFAULT '' COMMENT '关键词',
  `replace` varchar(255) NOT NULL DEFAULT '' COMMENT '替换',
  PRIMARY KEY (`id`,`keyword`),
  UNIQUE KEY `keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_comment
# ------------------------------------------------------------

CREATE TABLE `icms_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被评论内容的APPID',
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被评论内容的分类',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论者ID',
  `username` varchar(255) NOT NULL DEFAULT '' COMMENT '评论者',
  `content` text NOT NULL COMMENT '评论',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被评论内容的ID',
  `target_title` varchar(255) NOT NULL DEFAULT '' COMMENT '被评论内容的标题',
  `target_userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被评论内容的用户ID',
  `target_username` varchar(255) NOT NULL DEFAULT '' COMMENT '被评论内容的用户名',
  `reply_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复数，0为无回复',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `up` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '赞',
  `down` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'ip',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`status`,`userid`,`id`),
  KEY `idx_iid` (`status`,`appid`,`iid`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_comment_reply
# ------------------------------------------------------------

CREATE TABLE `icms_comment_reply` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论ID',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复者用户ID',
  `username` varchar(255) NOT NULL DEFAULT '' COMMENT '回复者用户名',
  `content` text NOT NULL COMMENT '回复内容',
  `reply_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复ID',
  `reply_userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被回复者用户ID',
  `reply_username` varchar(255) NOT NULL DEFAULT '' COMMENT '被回复者用户名',
  `reply_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回复数，0为无回复',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `up` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '赞',
  `down` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'ip',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `idx_CID_ST` (`comment_id`,`status`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_config
# ------------------------------------------------------------

CREATE TABLE `icms_config` (
  `siteid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '站点ID',
  `appid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '配置名',
  `value` mediumtext NOT NULL COMMENT '配置数据',
  PRIMARY KEY (`appid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_config_meta
# ------------------------------------------------------------

CREATE TABLE `icms_config_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_favorite
# ------------------------------------------------------------

CREATE TABLE `icms_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `follow` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关注数',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1 公开 0私密',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_favorite_data
# ------------------------------------------------------------

CREATE TABLE `icms_favorite_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏者ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `fid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏夹ID',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '内容URL',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '内容标题',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx` (`uid`,`fid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_favorite_follow
# ------------------------------------------------------------

CREATE TABLE `icms_favorite_follow` (
  `fid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '收藏夹ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '关注者',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '收藏夹标题',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关注者ID',
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_files
# ------------------------------------------------------------

CREATE TABLE `icms_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  `source` varchar(255) NOT NULL DEFAULT '' COMMENT '来源',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '路径',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '信息',
  `ext` varchar(10) NOT NULL DEFAULT '' COMMENT '后缀',
  `size` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '大小',
  `time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '使用数',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `ext` (`ext`),
  KEY `path` (`path`),
  KEY `fn_userid` (`name`,`userid`),
  KEY `source` (`source`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_files_map
# ------------------------------------------------------------

CREATE TABLE `icms_files_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fileid` int(10) unsigned NOT NULL COMMENT '文件ID',
  `userid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `appid` int(10) unsigned NOT NULL COMMENT '应用ID',
  `indexid` int(10) unsigned NOT NULL COMMENT '内容ID',
  `field` varchar(64) NOT NULL DEFAULT '' COMMENT '来源字段',
  `addtime` int(10) unsigned NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `idx` (`appid`,`indexid`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_forms
# ------------------------------------------------------------

CREATE TABLE `icms_forms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '表单ID',
  `node_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '表单分类',
  `app` varchar(255) NOT NULL DEFAULT '' COMMENT '表单标识',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '表单名',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '表单标题',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '表单图片',
  `bpic` varchar(255) NOT NULL DEFAULT '' COMMENT '表单缩略图1',
  `mpic` varchar(255) NOT NULL DEFAULT '' COMMENT '表单缩略图2',
  `spic` varchar(255) NOT NULL DEFAULT '' COMMENT '表单缩略图3',
  `description` varchar(5120) NOT NULL DEFAULT '' COMMENT '表单简介',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '表单创建者ID',
  `username` varchar(255) NOT NULL DEFAULT '' COMMENT '表单创建者',
  `tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '表单模板',
  `table` text NOT NULL COMMENT '表单表',
  `config` text NOT NULL COMMENT '表单配置',
  `fields` text NOT NULL COMMENT '表单字段',
  `clink` varchar(255) NOT NULL DEFAULT '' COMMENT '自定链接',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读点数',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消费积分',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总点击数',
  `hits_today` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天点击数',
  `hits_yday` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '昨天点击数',
  `hits_week` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `hits_month` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `comment` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `favorite` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `good` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '顶',
  `bad` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权重',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '表单类型',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '表单状态',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_forms_meta
# ------------------------------------------------------------

CREATE TABLE `icms_forms_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_links
# ------------------------------------------------------------

CREATE TABLE `icms_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '链接名',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'logo',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接',
  `intro` text NOT NULL COMMENT '介绍',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  UNIQUE KEY `id` (`id`),
  KEY `idx_ssi` (`status`,`sortnum`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_marker
# ------------------------------------------------------------

CREATE TABLE `icms_marker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `key` varchar(255) NOT NULL DEFAULT '',
  `data` mediumtext NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `marker` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_member
# ------------------------------------------------------------

CREATE TABLE `icms_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联user表id',
  `account` varchar(50) NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `realname` varchar(255) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `access` mediumtext NOT NULL COMMENT '权限数据',
  `info` mediumtext NOT NULL COMMENT '信息',
  `config` mediumtext NOT NULL COMMENT '配置信息',
  `regtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `lastloginip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登陆IP',
  `lastlogintime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `logintimes` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `post` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布数',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `groupid` (`role_id`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_message
# ------------------------------------------------------------

CREATE TABLE `icms_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送者ID',
  `friend` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接收者ID',
  `send_uid` int(10) DEFAULT '0' COMMENT '发送者ID',
  `send_name` varchar(255) NOT NULL DEFAULT '' COMMENT '发送者名称',
  `receiv_uid` int(10) DEFAULT '0' COMMENT '接收者ID',
  `receiv_name` varchar(255) NOT NULL DEFAULT '' COMMENT '接收者名称',
  `content` text NOT NULL COMMENT '内容',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '信息类型',
  `sendtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送时间',
  `readtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '读取时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '信息状态 参考程序注释',
  PRIMARY KEY (`id`),
  KEY `idx` (`status`,`userid`,`friend`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_message_data
# ------------------------------------------------------------

CREATE TABLE `icms_message_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `idx` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_meta
# ------------------------------------------------------------

CREATE TABLE `icms_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_node
# ------------------------------------------------------------

CREATE TABLE `icms_node` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rootid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `pid` varchar(255) NOT NULL DEFAULT '' COMMENT '属性',
  `appid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者ID',
  `creator` varchar(255) NOT NULL DEFAULT '' COMMENT '创建者',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '节点名',
  `subname` varchar(255) NOT NULL DEFAULT '' COMMENT '副名称',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '访问密码',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '介绍',
  `dir` varchar(255) NOT NULL DEFAULT '' COMMENT '目录',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `bpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `mpic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `spic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `mode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '模式',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定域名',
  `htmlext` varchar(10) NOT NULL DEFAULT '' COMMENT '静态后缀',
  `rule` text NOT NULL COMMENT '路由规则',
  `template` text NOT NULL COMMENT '模板',
  `config` text NOT NULL COMMENT '配置',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '统计',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读点数',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `comment` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `addtime` int(10) unsigned DEFAULT '0' COMMENT '添加时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_node_map
# ------------------------------------------------------------

CREATE TABLE `icms_node_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'cid',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `field` varchar(32) NOT NULL DEFAULT '' COMMENT '字段',
  PRIMARY KEY (`id`),
  KEY `idx_NAF` (`node`,`appid`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_node_meta
# ------------------------------------------------------------

CREATE TABLE `icms_node_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_plugin_verify
# ------------------------------------------------------------

CREATE TABLE `icms_plugin_verify` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `account` varchar(20) NOT NULL COMMENT '账号',
  `code` varchar(255) NOT NULL DEFAULT '' COMMENT '短信验证码/加密字符串',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  `verify_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '验证时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,0:未验证,1:验证,2:过期',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `phone` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_prop
# ------------------------------------------------------------

CREATE TABLE `icms_prop` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性ID',
  `rootid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `field` varchar(255) NOT NULL DEFAULT '' COMMENT '字段',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `app` varchar(255) NOT NULL DEFAULT '' COMMENT '应用标识',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用数',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '属性名',
  `val` varchar(255) NOT NULL DEFAULT '' COMMENT '属性值',
  `info` varchar(512) NOT NULL DEFAULT '' COMMENT '属性信息',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `field` (`field`),
  KEY `cid` (`cid`),
  KEY `type` (`app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_prop_map
# ------------------------------------------------------------

CREATE TABLE `icms_prop_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node` int(10) NOT NULL DEFAULT '0' COMMENT 'pid',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `field` varchar(32) NOT NULL DEFAULT '' COMMENT '字段',
  PRIMARY KEY (`id`),
  KEY `iid_index` (`appid`,`iid`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_role
# ------------------------------------------------------------

CREATE TABLE `icms_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `access` mediumtext NOT NULL COMMENT '权限数据',
  `config` text NOT NULL COMMENT '配置',
  `remark` text NOT NULL COMMENT '备注',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读点数',
  `free` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '免费次数',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_search_log
# ------------------------------------------------------------

CREATE TABLE `icms_search_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(200) NOT NULL DEFAULT '',
  `times` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `search_times` (`search`,`times`),
  KEY `search_id` (`search`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_sph_counter
# ------------------------------------------------------------

CREATE TABLE `icms_sph_counter` (
  `counter_id` int(11) NOT NULL,
  `max_doc_id` int(11) NOT NULL,
  PRIMARY KEY (`counter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_error
# ------------------------------------------------------------

CREATE TABLE `icms_spider_error` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rid` int(10) unsigned NOT NULL DEFAULT '0',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `urlId` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(1024) NOT NULL DEFAULT '',
  `msg` text NOT NULL,
  `work` varchar(255) NOT NULL DEFAULT '',
  `date` varchar(255) NOT NULL DEFAULT '',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table icms_spider_post
# ------------------------------------------------------------

CREATE TABLE `icms_spider_post` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `app` varchar(255) NOT NULL DEFAULT '',
  `post` text NOT NULL,
  `fun` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_project
# ------------------------------------------------------------

CREATE TABLE `icms_spider_project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `urls` text NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `rid` int(10) unsigned NOT NULL,
  `poid` int(10) unsigned NOT NULL,
  `auto` tinyint(1) unsigned NOT NULL,
  `lastupdate` int(10) unsigned NOT NULL,
  `config` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_rule
# ------------------------------------------------------------

CREATE TABLE `icms_spider_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `rule` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_url
# ------------------------------------------------------------

CREATE TABLE `icms_spider_url` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(10) NOT NULL DEFAULT '0',
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `rid` int(10) unsigned NOT NULL DEFAULT '0',
  `indexid` int(10) NOT NULL DEFAULT '0',
  `hash` char(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `publish` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `title` (`title`),
  KEY `url` (`url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_url_collect
# ------------------------------------------------------------

CREATE TABLE `icms_spider_url_collect` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(190) NOT NULL DEFAULT '',
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `iid` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `pid` (`pid`),
  KEY `iid` (`iid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_url_data
# ------------------------------------------------------------

CREATE TABLE `icms_spider_url_data` (
  `id` int(10) unsigned NOT NULL,
  `url` varchar(190) NOT NULL DEFAULT '',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_spider_url_list
# ------------------------------------------------------------

CREATE TABLE `icms_spider_url_list` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `iid` varchar(200) NOT NULL DEFAULT '',
  `url` varchar(190) NOT NULL DEFAULT '',
  `source` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`) USING HASH
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_tag
# ------------------------------------------------------------

CREATE TABLE `icms_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `tcid` varchar(255) NOT NULL DEFAULT '' COMMENT '分类ID',
  `pid` varchar(255) NOT NULL DEFAULT '' COMMENT '属性ID',
  `tkey` varchar(255) NOT NULL DEFAULT '' COMMENT '唯一标识',
  `title` varchar(255) NOT NULL COMMENT '标题',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标签名',
  `field` varchar(255) NOT NULL DEFAULT '' COMMENT '标签字段',
  `rootid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `seotitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'seo标题',
  `subtitle` varchar(255) NOT NULL DEFAULT '' COMMENT '短标题',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` text NOT NULL COMMENT '简介',
  `related` varchar(1024) NOT NULL DEFAULT '' COMMENT '相关',
  `editor` varchar(255) NOT NULL COMMENT '编辑或用户名',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `haspic` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有缩略图',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '缩略图',
  `bpic` varchar(255) NOT NULL DEFAULT '' COMMENT '大图',
  `mpic` varchar(255) NOT NULL DEFAULT '' COMMENT '中图',
  `spic` varchar(255) NOT NULL DEFAULT '' COMMENT '小图',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '网址',
  `tpl` varchar(255) NOT NULL DEFAULT '' COMMENT '模板',
  `weight` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `clink` varchar(255) NOT NULL DEFAULT '' COMMENT '自定义链接',
  `sortnum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `pubdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `postime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发表时间',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总点击数',
  `hits_today` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天点击数',
  `hits_yday` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '昨天点击数',
  `hits_week` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `hits_month` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '使用数',
  `comment` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `favorite` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `good` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '顶',
  `bad` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '踩',
  `creative` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:转载;1:原创',
  `postype` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '0:用户;1:管理员',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`,`id`),
  KEY `idx_order` (`status`,`sortnum`),
  KEY `name` (`name`),
  KEY `tkey` (`tkey`),
  KEY `idx_count` (`status`,`count`),
  KEY `pid_count` (`pid`,`count`),
  KEY `cid_count` (`cid`,`count`),
  KEY `pid_id` (`pid`,`id`),
  KEY `cid_id` (`cid`,`id`),
  KEY `rootid` (`rootid`),
  KEY `cid_hits` (`status`,`cid`,`hits`),
  KEY `hits` (`status`,`hits`),
  KEY `hits_month` (`status`,`hits_month`),
  KEY `hits_week` (`status`,`hits_week`),
  KEY `id` (`status`,`id`),
  KEY `pubdate` (`status`,`pubdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_tag_map
# ------------------------------------------------------------

CREATE TABLE `icms_tag_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '标签ID',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `appid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `field` varchar(32) NOT NULL DEFAULT '' COMMENT '字段',
  PRIMARY KEY (`id`),
  KEY `iid_index` (`appid`,`iid`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_tag_meta
# ------------------------------------------------------------

CREATE TABLE `icms_tag_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user
# ------------------------------------------------------------

CREATE TABLE `icms_user` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `pid` varchar(255) NOT NULL DEFAULT '' COMMENT '属性ID',
  `account` varchar(255) NOT NULL DEFAULT '' COMMENT '账号',
  `phone` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(128) NOT NULL DEFAULT '' COMMENT '昵称',
  `gender` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `fans` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '粉丝数',
  `follow` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关注数',
  `comment` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `article` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文章数',
  `favorite` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '金币',
  `credit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `scores` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '点数',
  `free` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '免费点数',
  `regip` varchar(20) NOT NULL DEFAULT '' COMMENT '注册IP',
  `regdate` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册日期',
  `lastloginip` varchar(20) NOT NULL DEFAULT '' COMMENT '最后登陆IP',
  `lastlogintime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `hits` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总点击数',
  `hits_today` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天点击数',
  `hits_yday` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '昨天点击数',
  `hits_week` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '周点击',
  `hits_month` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '月点击',
  `setting` varchar(1024) NOT NULL DEFAULT '' COMMENT '其它设置',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '账号状态',
  PRIMARY KEY (`uid`),
  KEY `nickname` (`nickname`),
  KEY `account` (`account`),
  KEY `phone` (`phone`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_data
# ------------------------------------------------------------

CREATE TABLE `icms_user_data` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `realname` varchar(255) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '联系电话',
  `weixin` varchar(255) NOT NULL DEFAULT '' COMMENT '微信号',
  `weibo` varchar(255) NOT NULL DEFAULT '' COMMENT '个人微博',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '街道地址',
  `province` varchar(255) NOT NULL DEFAULT '' COMMENT '省份',
  `city` varchar(255) NOT NULL DEFAULT '' COMMENT '城市',
  `year` varchar(255) NOT NULL DEFAULT '' COMMENT '生日-年',
  `month` varchar(255) NOT NULL DEFAULT '' COMMENT '生日-月',
  `day` varchar(255) NOT NULL DEFAULT '' COMMENT '生日-日',
  `constellation` varchar(255) NOT NULL DEFAULT '' COMMENT '星座',
  `profession` varchar(255) NOT NULL DEFAULT '' COMMENT '职业',
  `personstyle` varchar(255) NOT NULL DEFAULT '' COMMENT '个人标签',
  `slogan` varchar(512) NOT NULL DEFAULT '' COMMENT '自我介绍',
  `meta` text NOT NULL COMMENT '其它数据',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uid_UNIQUE` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_follow
# ------------------------------------------------------------

CREATE TABLE `icms_user_follow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关注者ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '关注者',
  `fuid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被关注者ID',
  `fname` varchar(255) NOT NULL DEFAULT '' COMMENT '被关注者',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关注时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`userid`,`fuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_meta
# ------------------------------------------------------------

CREATE TABLE `icms_user_meta` (
  `id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_node
# ------------------------------------------------------------

CREATE TABLE `icms_user_node` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appid` int(10) unsigned NOT NULL DEFAULT '0',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1 公开 0私密',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `uid` (`userid`,`appid`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_openid
# ------------------------------------------------------------

CREATE TABLE `icms_user_openid` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `openid` varchar(128) NOT NULL DEFAULT '',
  `platform` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:wx,2:qq,3:wb,4:tb',
  `appid` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_upa` (`userid`,`platform`,`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_order
# ------------------------------------------------------------

CREATE TABLE `icms_user_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `subject` varchar(255) NOT NULL DEFAULT '' COMMENT '项目',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `order_no` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `trade_id` int(10) unsigned NOT NULL COMMENT '订单ID',
  `amount` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `value` int(10) NOT NULL DEFAULT '0' COMMENT '积分',
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `product_id` varchar(255) NOT NULL DEFAULT '' COMMENT '商品ID',
  `product_type` varchar(32) NOT NULL DEFAULT '' COMMENT '商品类型',
  `product_event` varchar(64) NOT NULL DEFAULT '' COMMENT '商品事件',
  `client_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '用户IP',
  `channel` char(8) NOT NULL DEFAULT '' COMMENT '平台 wx ali',
  `transaction_id` varchar(64) NOT NULL DEFAULT '' COMMENT '支付订单号',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单过期时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`),
  KEY `transaction_id` (`transaction_id`),
  KEY `product_appid` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户积分记录';



# Dump of table icms_user_report
# ------------------------------------------------------------

CREATE TABLE `icms_user_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报者',
  `appid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被举报者',
  `reason` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `content` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table icms_user_timeline
# ------------------------------------------------------------

CREATE TABLE `icms_user_timeline` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `appid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '应用ID',
  `iid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容发布者ID',
  `event` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '事件',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
