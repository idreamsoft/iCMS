# ************************************************************
# Sequel Ace SQL dump
# Version 2104
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: 127.0.0.1 (MySQL 5.7.29)
# Database: icms8
# Generation Time: 2021-04-23 10:19:12 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
SET NAMES utf8;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table icms_apps
# ------------------------------------------------------------


/*!40000 ALTER TABLE `icms_apps` DISABLE KEYS */;

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(1,'article',0,'文章系统','文章',1,1,'{\"article\":[\"article\",\"id\",\"\",\"\\u6587\\u7ae0\"],\"article_data\":[\"article_data\",\"id\",\"aid\",\"\\u6b63\\u6587\"],\"article_meta\":[\"article_meta\",\"id\",\"\",\"\\u52a8\\u6001\\u5c5e\\u6027\"]}','{\"info\":\"文章系统\",\"template\":[\"$article\"],\"version\":\"v8.0\",\"iurl\":{\"rule\":\"2\",\"primary\":\"id\",\"page\":\"p\"}}','','','default',1619516495,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(2,'node',0,'节点系统','节点',1,1,'{\"node\":[\"node\",\"id\",\"\",\"节点\"],\"node_meta\":[\"node_meta\",\"id\",\"\",\"动态属性\"]}','{\"info\":\"通用无限级节点系统\",\"template\":[\"iCMS:node:array\",\"iCMS:node:list\",\"$node\"],\"version\":\"v8.0\",\"iurl\":{\"rule\":\"1\",\"primary\":\"id\"}}','','','default',1509504903,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(3,'tag',0,'标签系统','标签',1,1,'{\"tag\":[\"tag\",\"id\",\"\",\"标签\"],\"tag_map\":[\"tag_map\",\"id\",\"node\",\"标签映射\"],\"tag_meta\":[\"tag_meta\",\"id\",\"\",\"动态属性\"]}','{\"info\":\"自由多样性标签系统\",\"template\":[\"iCMS:tag:list\",\"iCMS:tag:array\",\"$tag\"],\"version\":\"v8.0\",\"iurl\":{\"rule\":\"3\",\"primary\":\"id\"}}','','','default',1495729291,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(4,'category',0,'分类系统','分类',0,1,'','{\"info\":\"节点别名，向下兼容\",\"template\":[\"iCMS:category:array\",\"iCMS:category:list\",\"$category\"],\"version\":\"v8.0\",\"iurl\":{\"rule\":\"1\",\"primary\":\"cid\"}}','','','default',1509504903,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(5,'comment',0,'评论系统','评论',1,1,'{\"comment\":[\"comment\",\"id\",\"\",\"评论\"]}','{\"info\":\"通用评论系统\",\"template\":[\"iCMS:comment:array\",\"iCMS:comment:list\",\"iCMS:comment:form\"],\"version\":\"v8.0\"}','','','default',1523008095,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(6,'prop',0,'属性系统','属性',0,1,'{\"prop\":[\"prop\",\"pid\",\"\",\"属性\"],\"prop_map\":[\"prop_map\",\"id\",\"node\",\"属性映射\"]}','{\"info\":\"通用属性系统\",\"template\":[\"iCMS:prop:array\"],\"version\":\"v8.0\"}','','','default',1489151390,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(7,'message',0,'私信系统','私信',0,1,'{\"message\":[\"message\",\"id\",\"\",\"私信\"]}','{\"info\":\"用户私信系统\",\"version\":\"v8.0\",\"template\":[\"iCMS:message:list\"]}','','','default',1488706289,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(8,'favorite',0,'收藏系统','收藏',0,1,'{\"favorite\":[\"favorite\",\"id\",\"\",\"收藏信息\"],\"favorite_data\":[\"favorite_data\",\"fid\",\"\",\"收藏数据\"],\"favorite_follow\":[\"favorite_follow\",\"id\",\"fid\",\"收藏关注\"]}','{\"info\":\"用户收藏系统\",\"template\":[\"iCMS:favorite:list\",\"iCMS:favorite:data\"],\"version\":\"v8.0\"}','','','default',1523008024,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(9,'user',0,'用户系统','用户',1,1,'{\"user\":[\"user\",\"uid\",\"\",\"用户\"],\"user_category\":[\"user_category\",\"cid\",\"uid\",\"用户分类\"],\"user_data\":[\"user_data\",\"uid\",\"uid\",\"用户数据\"],\"user_follow\":[\"user_follow\",\"uid\",\"uid\",\"用户关注\"],\"user_openid\":[\"user_openid\",\"uid\",\"uid\",\"第三方\"],\"user_report\":[\"user_report\",\"id\",\"userid\",\"举报\"],\"user_cdata\":[\"user_cdata\",\"cdata_id\",\"user_id\",\"附加\"]}','{\"info\":\"用户系统\",\"template\":[\"iCMS:user:cookie\",\"iCMS:user:data\",\"iCMS:user:list\",\"iCMS:user:category\",\"iCMS:user:follow\",\"iCMS:user:stat\"],\"version\":\"v8.0\"}','','','default',1533299803,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(10,'admincp',0,'后台程序','后台',0,0,'{\"access_log\":[\"access_log\",\"id\",\"\",\"访问记录\"]}','{\"info\":\"基础管理系统\",\"version\":\"v8.0\"}','','','default',1493342705,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(11,'config',0,'系统配置','配置',0,0,'{\"config\":[\"config\",\"appid\",\"\",\"系统配置\"]}','{\"info\":\"系统配置\",\"version\":\"v8.0\"}','','','default',1493342808,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(12,'files',0,'文件系统','文件',0,0,'{\"files\":[\"files\",\"id\",\"\",\"文件\"],\"files_map\":[\"files_map\",\"fileid\",\"fileid\",\"文件映射\"]}','{\"info\":\"文件管理系统\",\"version\":\"v8.0\"}','','','default',1492653210,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(13,'menu',0,'后台菜单','菜单',0,0,'0','{\"info\":\"后台菜单管理\",\"version\":\"v8.0\"}','','','default',1488704378,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(14,'role',0,'角色系统','角色',0,0,'{\"role\":[\"role\",\"id\",\"\",\"角色\"]}','{\"info\":\"角色权限系统\",\"version\":\"v8.0\"}','','','default',1488704473,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(15,'member',0,'管理员系统','管理员',0,0,'{\"member\":[\"member\",\"id\",\"\",\"管理员\"]}','{\"info\":\"管理员系统\",\"version\":\"v8.0\"}','','','default',1488704428,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(16,'editor',0,'后台编辑器','编辑器',0,0,'0','{\"info\":\"后台编辑器\",\"version\":\"v8.0\"}','','','default',1488704375,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(17,'apps',0,'应用管理','应用',0,0,'{\"apps\":[\"apps\",\"id\",\"\",\"应用\"],\"apps_store\":[\"apps_store\",\"id\",\"\",\"应用市场\"]}','{\"info\":\"应用管理\",\"template\":[\"iCMS:apps:list\",\"iCMS:apps:data\"],\"version\":\"v8.0\"}','','','default',1543398919,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(18,'former',0,'表单生成器','表单',0,0,'0','{\"info\":\"表单生成器\",\"version\":\"v8.0\"}','','','default',1490201571,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(19,'patch',0,'升级程序','升级',0,0,'0','{\"info\":\"用于升级系统\",\"version\":\"v8.0\"}','','','default',1488704373,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(20,'content',0,'内容管理','内容',0,1,'0','{\"info\":\"自定义应用内容管理\\/接口\",\"template\":[\"iCMS:content:list\",\"iCMS:content:prev\",\"iCMS:content:next\",\"$content\"],\"version\":\"v8.0\"}','','','',1493339370,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(21,'index',0,'首页程序','首页',0,1,'0','{\"info\":\"首页程序\",\"version\":\"v8.0\",\"iurl\":{\"rule\":\"0\",\"primary\":\"\"}}','','','default',1488771698,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(22,'public',0,'公共程序','公共',0,0,'[]','{\"info\":\"公共程序\",\"version\":\"v8.0\"}','[]','','',1605774068,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(23,'cache',0,'缓存更新','缓存',0,1,'0','{\"info\":\"用于更新应用程序缓存\",\"version\":\"v8.0\"}','','','default',1489336794,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(24,'filter',0,'过滤系统','过滤',0,1,'0','{\"info\":\"关键词过滤/违禁词系统\",\"version\":\"v8.0\"}','','','default',1488704119,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(25,'plugin',0,'插件管理','插件',0,1,'0','{\"info\":\"插件程序\",\"version\":\"v8.0\"}','','','',1488704192,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(26,'forms',0,'自定义表单','表单',0,1,'{\"forms\":[\"forms\",\"id\",\"\",\"表单\"]}','{\"info\":\"自定义表单管理\\/接口\",\"template\":[\"iCMS:forms:array\",\"iCMS:forms:create\",\"iCMS:forms:data\",\"iCMS:forms:list\",\"$forms\"],\"version\":\"v8.0\"}','','','default',1523007995,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(27,'vote',0,'投票','投票',0,1,'{\"vote\":[\"vote\",\"id\",\"\",\"\\u6295\\u7968\"]}','{\"info\":\"\\u6295\\u7968\\/\\u70b9\\u8d5e\",\"version\":\"v1.0.0\"}','[]','[]','0',1620350812,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(28,'chain',0,'内链系统','内链',0,1,'{\"chain\":[\"chain\",\"id\",\"\",\"内链\"]}','{\"info\":\"内链系统\",\"version\":\"v8.0\"}','','','default',1488704241,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(29,'links',0,'友情链接','链接',1,1,'{\"links\":[\"links\",\"id\",\"\",\"友情链接\"]}','{\"info\":\"友情链接程序\",\"template\":[\"iCMS:links:list\"],\"version\":\"v8.0\"}','','','default',1489932498,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(31,'search',0,'搜索系统','搜索',0,1,'{\"search_log\":[\"search_log\",\"id\",\"\",\"搜索记录\"]}','{\"info\":\"文章搜索系统\",\"template\":[\"iCMS:search:list\",\"iCMS:search:url\",\"$search\"],\"version\":\"v8.0\"}','','','default',1523008070,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(32,'database',0,'数据库管理','数据库',0,1,'0','{\"info\":\"后台简易数据库管理\",\"version\":\"v8.0\"}','','','default',1488703931,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(33,'html',0,'静态系统','静态',0,1,'0','{\"info\":\"静态文件生成程序\",\"version\":\"v8.0\"}','','','default',1488703939,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(34,'spider',0,'采集系统','采集',0,1,'{\"spider_post\":[\"spider_post\",\"id\",\"\",\"发布\"],\"spider_project\":[\"spider_project\",\"id\",\"\",\"方案\"],\"spider_rule\":[\"spider_rule\",\"id\",\"\",\"规则\"],\"spider_url\":[\"spider_url\",\"id\",\"\",\"采集结果\"],\"spider_url_data\":[\"spider_url_data\",\"id\",\"\",\"采集附加数据\"],\"spider_error\":[\"spider_error\",\"id\",\"\",\"错误记录\"]}','{\"info\":\"采集系统\",\"version\":\"v8.0\"}','','','default',1546316018,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(35,'archive',0,'内容统一归档','归档',0,0,'{\"archive\":[\"archive\",\"id\",null,\"归档”]}','{}','','','',1597630483,1);

INSERT INTO `icms_apps` (`id`, `app`, `rootid`, `name`, `title`, `apptype`, `type`, `table`, `config`, `fields`, `route`, `menu`, `addtime`, `status`)
VALUES
	(36,'developer',0,'开发者系统','开发者',0,0,'{}','{}','{}','[]','default',1598872366,1);

/*!40000 ALTER TABLE `icms_apps` ENABLE KEYS */;


# Dump of table icms_config
# ------------------------------------------------------------


/*!40000 ALTER TABLE `icms_config` DISABLE KEYS */;

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'admincp','{\"sidebar\":{\"enable\":\"1\",\"mini\":\"0\",\"right\":\"0\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'api','{\"baidu\":{\"sitemap\":{\"site\":\"\",\"access_token\":\"\",\"sync\":\"0\"}}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'apps','[]');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'cache','{\"engine\":\"file\",\"host\":\"\",\"time\":\"300\",\"compress\":\"0\",\"page_total\":\"300\",\"prefix\":\"iCMS\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'CDN','{\"enable\":\"0\",\"cache_control\":\"public\",\"expires\":\"86400\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'debug','{\"php\":\"1\",\"php_trace\":\"0\",\"php_errorlog\":\"0\",\"access_log\":\"0\",\"tpl\":\"1\",\"tpl_trace\":\"0\",\"db\":\"1\",\"db_trace\":\"0\",\"db_explain\":\"0\",\"db_optimize_in\":\"0\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'FS','{\"url\":\"http:\\/\\/v8.icmsdev.com\\/res\\/\",\"dir\":\"res\",\"dir_format\":\"{md5:0,2}\\/{md5:2,3}\\/\",\"allow_ext\":\"gif,jpg,rar,swf,jpeg,png,zip,mp4\",\"check_md5\":\"0\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'mail','{\"host\":\"\",\"secure\":\"\",\"port\":\"\",\"username\":\"\",\"password\":\"\",\"setfrom\":\"\",\"replyto\":\"\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'member','{\"life\":\"8640000\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'plugin','{\"SMS\":{\"aliyun\":{\"AccessKeyId\":\"\",\"AccessKeySecret\":\"\",\"TemplateCode\":\"\",\"SignName\":\"\"},\"expire\":\"\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'publish','[]');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'route','{\"url\":\"http:\\/\\/v8.icmsdev.com\",\"redirect\":\"0\",\"404\":\"http:\\/\\/v8.icmsdev.com\\/public\\/404.htm\",\"public\":\"http:\\/\\/v8.icmsdev.com\\/public\",\"user\":\"http:\\/\\/v8.icmsdev.com\\/user\",\"dir\":\"\\/\",\"ext\":\".html\",\"speed\":\"5\",\"rewrite\":\"0\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'site','{\"name\":\"iCMS\",\"seotitle\":\"\\u7ed9\\u6211\\u4e00\\u5957\\u7a0b\\u5e8f\\uff0c\\u6211\\u80fd\\u6405\\u52a8\\u4e92\\u8054\\u7f51\",\"keywords\":\"iCMS,iCMS\\u5185\\u5bb9\\u7ba1\\u7406\\u7cfb\\u7edf,\\u6587\\u7ae0\\u7ba1\\u7406\\u7cfb\\u7edf,PHP\\u6587\\u7ae0\\u7ba1\\u7406\\u7cfb\\u7edf\",\"description\":\"iCMS \\u662f\\u4e00\\u5957\\u91c7\\u7528 PHP \\u548c MySQL \\u6784\\u5efa\\u7684\\u9ad8\\u6548\\u7b80\\u6d01\\u7684\\u5185\\u5bb9\\u7ba1\\u7406\\u7cfb\\u7edf,\\u4e3a\\u60a8\\u7684\\u7f51\\u7ad9\\u63d0\\u4f9b\\u4e00\\u4e2a\\u5b8c\\u7f8e\\u7684\\u5f00\\u6e90\\u89e3\\u51b3\\u65b9\\u6848\",\"code\":\"\",\"icp\":\"\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'sphinx','{\"host\":\"127.0.0.1:9312\",\"index\":{\"article\":\"iCMS_article iCMS_article_delta\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'system','{\"patch\":\"1\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'template','{\"index\":{\"mode\":\"0\",\"rewrite\":\"0\",\"tpl\":\"{iTPL}\\/index.htm\",\"name\":\"index\"},\"desktop\":{\"domain\":\"http:\\/\\/v8.icmsdev.com\",\"name\":\"desktop\",\"agent\":\"\",\"tpl\":\"blog\",\"index\":\"{iTPL}\\/index.htm\"},\"mobile\":{\"name\":\"mobile\",\"agent\":\"WAP,Smartphone,Mobile,UCWEB,Opera Mini,Windows CE,Symbian,SAMSUNG,iPhone,Android,BlackBerry,HTC,Mini,LG,SonyEricsson,J2ME,MOT\",\"domain\":\"http:\\/\\/m.v8.icmsdev.com\",\"tpl\":\"blog\",\"index\":\"{iTPL}\\/index.htm\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'thumb','{\"size\":\"\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'time','{\"zone\":\"Asia\\/Shanghai\",\"cvtime\":\"0\",\"dateformat\":\"Y-m-d H:i:s\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,0,'watermark','{\"enable\":\"0\",\"mode\":\"0\",\"pos\":\"8\",\"x\":\"10\",\"y\":\"10\",\"width\":\"140\",\"height\":\"140\",\"allow_ext\":\"jpg,jpeg,png\",\"img\":\"watermark.png\",\"transparent\":\"80\",\"text\":\"iCMS\",\"font\":\"\",\"fontsize\":\"24\",\"color\":\"#000000\",\"mosaics\":{\"width\":\"150\",\"height\":\"90\",\"deep\":\"9\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,1,'article','{\"img_title\":\"0\",\"pic_center\":\"1\",\"pic_next\":\"0\",\"pageno_incr\":\"\",\"markdown\":\"0\",\"autoformat\":\"0\",\"catch_remote\":\"0\",\"remote\":\"0\",\"autopic\":\"0\",\"autodesc\":\"1\",\"descLen\":\"100\",\"autoPage\":\"0\",\"AutoPageLen\":\"\",\"repeatitle\":\"1\",\"showpic\":\"0\",\"filter\":[\"description:\\u7b80\\u4ecb\",\"body:\\u5185\\u5bb9\",\"stitle:\\u77ed\\u6807\\u9898\",\"keywords:\\u5173\\u952e\\u5b57\"],\"totalNum\":\"\",\"clink\":\"-\",\"emoji\":\"\",\"sphinx\":{\"host\":\"\",\"index\":\"\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,2,'node','{\"domain\":null}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,3,'tag','{\"rule\":\"{PHP}\",\"tpl\":\"{iTPL}\\/tag.htm\",\"tkey\":\"-\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,5,'comment','{\"enable\":\"1\",\"examine\":\"1\",\"captcha\":\"1\",\"reply\":{\"enable\":\"1\",\"examine\":\"0\",\"captcha\":\"0\"}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,9,'user','{\"register\":{\"enable\":\"1\",\"mode\":[\"account\",\"phone\"],\"modeText\":{\"account\":\"\\u7528\\u6237\\u540d\",\"phone\":\"\\u624b\\u673a\\u53f7\",\"email\":\"\\u90ae\\u7bb1\"},\"verify\":{\"phone\":\"0\",\"email\":\"0\"},\"captcha\":\"0\",\"interval\":\"600\",\"role\":\"3\"},\"agreement\":\"\",\"login\":{\"enable\":\"1\",\"mode\":[\"phone\",\"account\"],\"modeText\":{\"phone\":\"\\u514d\\u5bc6\\u767b\\u5f55\",\"account\":\"\\u5bc6\\u7801\\u767b\\u5f55\",\"weixin\":\"\\u626b\\u7801\\u767b\\u5f55\"},\"auto_register\":\"0\",\"captcha\":\"0\",\"interval\":\"10\",\"times\":\"2\"},\"post\":{\"captcha\":\"0\",\"interval\":\"10\"},\"forward\":\"0\",\"coverpic\":\"\\/ui\\/coverpic.jpg\",\"node\":{\"max\":\"10\"},\"open\":{\"WX\":{\"enable\":\"1\",\"id\":\"1\",\"name\":\"\\u5fae\\u4fe1\",\"appid\":\"\",\"appkey\":\"\",\"redirect\":\"\"},\"QQ\":{\"enable\":true,\"id\":\"2\",\"name\":\"QQ\",\"appid\":\"\",\"appkey\":\"\",\"redirect\":\"\"},\"WB\":{\"enable\":\"0\",\"id\":\"3\",\"name\":\"\\u5fae\\u535a\",\"appid\":\"\",\"appkey\":\"\",\"redirect\":\"\"},\"TB\":{\"enable\":\"0\",\"id\":\"4\",\"name\":\"\\u6dd8\\u5b9d\",\"appid\":\"\",\"appkey\":\"\",\"redirect\":\"\"}}}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,12,'cloud','{}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,12,'files','[]');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,15,'member','{\"life\":\"86400\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,17,'APPS:META','{\"apps\":1,\"article\":1,\"config\":1,\"node\":1,\"tag\":1}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,17,'hooks','[]');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,28,'chain','{\"limit\":\"-1\"}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,999999,'filter','{}');

INSERT INTO `icms_config` (`siteid`, `appid`, `name`, `value`)
VALUES
	(1,999999,'icmsdev','{}');

/*!40000 ALTER TABLE `icms_config` ENABLE KEYS */;


# Dump of table icms_member
# ------------------------------------------------------------


/*!40000 ALTER TABLE `icms_member` DISABLE KEYS */;

INSERT INTO `icms_member` (`id`, `role_id`, `user_id`, `account`, `password`, `nickname`, `realname`, `gender`, `access`, `info`, `config`, `regtime`, `lastloginip`, `lastlogintime`, `logintimes`, `post`, `type`, `status`)
VALUES
	(1,1,1,'admin','e10adc3949ba59abbe56e057f20f883e','iCMS','iCMS',0,'','','',0,'127.0.0.1',1608632242,1,0,0,1);

/*!40000 ALTER TABLE `icms_member` ENABLE KEYS */;


# Dump of table icms_prop
# ------------------------------------------------------------


/*!40000 ALTER TABLE `icms_prop` DISABLE KEYS */;

INSERT INTO `icms_prop` (`id`, `rootid`, `cid`, `field`, `appid`, `app`, `count`, `name`, `val`, `info`, `sortnum`, `status`)
VALUES
	(6,0,0,'pid',1,'article',0,'头条','1','',0,1);

INSERT INTO `icms_prop` (`id`, `rootid`, `cid`, `field`, `appid`, `app`, `count`, `name`, `val`, `info`, `sortnum`, `status`)
VALUES
	(7,0,0,'pid',1,'article',0,'推荐','2','',1,1);

INSERT INTO `icms_prop` (`id`, `rootid`, `cid`, `field`, `appid`, `app`, `count`, `name`, `val`, `info`, `sortnum`, `status`)
VALUES
	(8,0,0,'pid',2,'node',0,'推荐栏目','1','',0,1);

INSERT INTO `icms_prop` (`id`, `rootid`, `cid`, `field`, `appid`, `app`, `count`, `name`, `val`, `info`, `sortnum`, `status`)
VALUES
	(9,0,0,'pid',3,'tag',0,'热门标签','1','',0,1);

INSERT INTO `icms_prop` (`id`, `rootid`, `cid`, `field`, `appid`, `app`, `count`, `name`, `val`, `info`, `sortnum`, `status`)
VALUES
	(10,0,0,'pid',9,'user',0,'推荐用户','1','',0,1);

/*!40000 ALTER TABLE `icms_prop` ENABLE KEYS */;


# Dump of table icms_role
# ------------------------------------------------------------


/*!40000 ALTER TABLE `icms_role` DISABLE KEYS */;

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(1,'超级管理员',1,'','','',0,0,0,0,1,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(2,'编辑',2,'{\"node\":[\"all:a\",\"all:ca\",\"119:cm\",\"4:cm\",\"4:ca\",\"4:cd\"],\"app\":[\"ADMINCP\",\"article\",\"article_category\",\"article_category&do=add\",\"article-6-1\",\"article&do=add\",\"article&do=manage\"],\"menu\":[\"admincp\",\"article&do=config\",\"article&do=manage\"]}','','',0,0,0,0,1,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(3,'普通会员',3,'','','',10,0,10,0,0,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(4,'白银会员',3,'','','',50,0,100,0,0,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(5,'黄金会员',3,'','','',100,0,1000,0,0,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(6,'铂金会员',0,'','','',200,0,10000,0,0,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(7,'钻石会员',0,'','','',500,0,100000,0,0,1);

INSERT INTO `icms_role` (`id`, `name`, `sortnum`, `access`, `config`, `remark`, `credit`, `money`, `scores`, `free`, `type`, `status`)
VALUES
	(8,'永久会员',0,'','','',1000,0,1000000,0,0,1);

/*!40000 ALTER TABLE `icms_role` ENABLE KEYS */;


# Dump of table icms_user
# ------------------------------------------------------------

/*!40000 ALTER TABLE `icms_user` DISABLE KEYS */;

INSERT INTO `icms_user` (`uid`, `role_id`, `pid`, `account`, `phone`, `email`, `password`, `nickname`, `gender`, `fans`, `follow`, `comment`, `article`, `favorite`, `money`, `credit`, `scores`, `free`, `regip`, `regdate`, `lastloginip`, `lastlogintime`, `hits`, `hits_today`, `hits_yday`, `hits_week`, `hits_month`, `setting`, `type`, `status`)
VALUES
	(1,3,'','admin','','admin@website.com','d2ca8fe1e139ec20df1007461a816b1e','admin',0,0,0,0,0,0,0,0,0,0,'127.0.0.1',1614831745,'127.0.0.1',1616665950,0,0,0,0,0,'{\"message\":{\"receive\":\"all\"}}',0,1);

/*!40000 ALTER TABLE `icms_user` ENABLE KEYS */;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
