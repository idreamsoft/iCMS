<?php
defined('iPHP') OR exit('Access Denied');
return array (
  'admincp' => 
  array (
    'sidebar' => 
    array (
      'enable' => '1',
      'mini' => '0',
      'right' => '0',
    ),
  ),
  'api' => 
  array (
    'baidu' => 
    array (
      'sitemap' => 
      array (
        'site' => '',
        'access_token' => '',
        'sync' => '0',
      ),
    ),
  ),
  'apps' => 
  array (
    'article' => 1,
    'node' => 2,
    'tag' => 3,
    'category' => 4,
    'comment' => 5,
    'prop' => 6,
    'message' => 7,
    'favorite' => 8,
    'user' => 9,
    'admincp' => 10,
    'config' => 11,
    'files' => 12,
    'menu' => 13,
    'role' => 14,
    'member' => 15,
    'editor' => 16,
    'apps' => 17,
    'former' => 18,
    'patch' => 19,
    'content' => 20,
    'index' => 21,
    'public' => 22,
    'cache' => 23,
    'filter' => 24,
    'plugin' => 25,
    'forms' => 26,
    'vote' => 27,
    'chain' => 28,
    'links' => 29,
    'search' => 31,
    'database' => 32,
    'html' => 33,
    'spider' => 34,
    'archive' => 35,
    'developer' => 36,
    'payment' => 37,
    'forum' => 43,
    'test' => 51,
    'weixin' => 52,
  ),
  'cache' => 
  array (
    'engine' => 'file',
    'host' => '',
    'time' => '300',
    'compress' => '0',
    'page_total' => '300',
    'prefix' => 'iCMS',
  ),
  'CDN' => 
  array (
    'enable' => '0',
    'cache_control' => 'public',
    'expires' => '86400',
  ),
  'debug' => 
  array (
    'php' => '1',
    'php_trace' => '0',
    'php_errorlog' => '0',
    'access_log' => '0',
    'tpl' => '1',
    'tpl_trace' => '0',
    'db' => '1',
    'db_trace' => '0',
    'db_explain' => '0',
    'db_optimize_in' => '0',
  ),
  'FS' => 
  array (
    'url' => 'http://v8.icmsdev.com/res/',
    'dir' => 'res',
    'dir_format' => '{md5:0,2}/{md5:2,3}/',
    'allow_ext' => 'gif,jpg,rar,swf,jpeg,png,zip,mp4',
    'check_md5' => '0',
  ),
  'mail' => 
  array (
    'host' => '',
    'secure' => '',
    'port' => '25',
    'username' => '',
    'password' => '',
    'setfrom' => '',
    'replyto' => '',
  ),
  'member' => 
  array (
    'life' => '86400000',
  ),
  'plugin' => 
  array (
    'SMS' => 
    array (
      'aliyun' => 
      array (
        'AccessKeyId' => '',
        'AccessKeySecret' => '',
        'TemplateCode' => '',
        'SignName' => '',
      ),
      'expire' => '60',
    ),
  ),
  'publish' => 
  array (
  ),
  'route' => 
  array (
    'url' => 'http://v8.icmsdev.com',
    'redirect' => '0',
    404 => 'http://v8.icmsdev.com/public/404.htm',
    'public' => 'http://v8.icmsdev.com/public',
    'user' => 'http://v8.icmsdev.com/user',
    'dir' => '/',
    'ext' => '.html',
    'speed' => '5',
    'rewrite' => '0',
  ),
  'site' => 
  array (
    'name' => 'iCMSasd',
    'seotitle' => '给我一套程序，我能搅动互联网',
    'keywords' => 'iCMS,iCMS内容管理系统,文章管理系统,PHP文章管理系统',
    'description' => 'iCMS 是一套采用 PHP 和 MySQL 构建的高效简洁的内容管理系统,为您的网站提供一个完美的开源解决方案',
    'code' => '',
    'icp' => '',
  ),
  'sphinx' => 
  array (
    'host' => '127.0.0.1:9312',
    'index' => 
    array (
      'article' => 'iCMS_article iCMS_article_delta',
    ),
  ),
  'system' => 
  array (
    'patch' => '1',
  ),
  'taoke' => 
  array (
    'pid' => '',
  ),
  'template' => 
  array (
    'index' => 
    array (
      'mode' => '0',
      'rewrite' => '1',
      'tpl' => '{iTPL}/index.htm',
      'name' => 'index',
    ),
    'desktop' => 
    array (
      'domain' => 'http://v8.icmsdev.com',
      'name' => 'desktop',
      'agent' => '',
      'tpl' => 'blog',
      'index' => '{iTPL}/index.htm',
    ),
    'mobile' => 
    array (
      'name' => 'mobile',
      'agent' => 'WAP,Smartphone,Mobile,UCWEB,Opera Mini,Windows CE,Symbian,SAMSUNG,iPhone,Android,BlackBerry,HTC,Mini,LG,SonyEricsson,J2ME,MOT',
      'domain' => 'http://v8.icmsdev.com',
      'tpl' => 'blog',
      'index' => '{iTPL}/index.htm',
    ),
  ),
  'thumb' => 
  array (
    'size' => '',
  ),
  'time' => 
  array (
    'zone' => 'Asia/Shanghai',
    'cvtime' => '0',
    'dateformat' => 'Y-m-d H:i:s',
  ),
  'watermark' => 
  array (
    'enable' => '0',
    'mode' => '0',
    'pos' => '8',
    'x' => '10',
    'y' => '10',
    'width' => '140',
    'height' => '140',
    'allow_ext' => 'jpg,jpeg,png',
    'img' => 'watermark.png',
    'transparent' => '80',
    'text' => 'iCMS',
    'font' => '',
    'fontsize' => '24',
    'color' => '#000000',
    'mosaics' => 
    array (
      'width' => '150',
      'height' => '90',
      'deep' => '9',
    ),
  ),
  'article' => 
  array (
    'img_title' => '0',
    'pic_center' => '1',
    'pic_next' => '0',
    'pageno_incr' => '',
    'markdown' => '0',
    'autoformat' => '0',
    'catch_remote' => '1',
    'remote' => '0',
    'autopic' => '0',
    'autodesc' => '1',
    'descLen' => '100',
    'autoPage' => '0',
    'AutoPageLen' => '',
    'repeatitle' => '0',
    'showpic' => '0',
    'filter' => 
    array (
      0 => 'description:简介',
      1 => 'body:内容',
      2 => 'stitle:短标题',
      3 => 'keywords:关键字',
    ),
    'totalNum' => '',
    'clink' => '-',
    'emoji' => '',
    'sphinx' => 
    array (
      'host' => '127.0.0.1:9312',
      'index' => 'iCMS_article iCMS_article_delta',
    ),
  ),
  'node' => 
  array (
    'domain' => 
    array (
      'http://v8test.icmsdev.com' => 4,
    ),
  ),
  'tag' => 
  array (
    'rule' => '{PHP}',
    'tpl' => '{iTPL}/tag.htm',
    'tkey' => '-',
  ),
  'comment' => 
  array (
    'enable' => '1',
    'examine' => '1',
    'captcha' => '1',
    'reply' => 
    array (
      'enable' => '1',
      'examine' => '0',
      'captcha' => '0',
    ),
  ),
  'user' => 
  array (
    'register' => 
    array (
      'enable' => '1',
      'mode' => 
      array (
        0 => 'account',
      ),
      'modeText' => 
      array (
        'account' => '用户名',
        'phone' => '手机号',
        'email' => '邮箱',
      ),
      'verify' => 
      array (
        'phone' => '0',
        'email' => '0',
      ),
      'captcha' => '0',
      'interval' => '600',
      'role' => '3',
    ),
    'agreement' => '',
    'login' => 
    array (
      'enable' => '1',
      'modeText' => 
      array (
        'phone' => '免密登录',
        'account' => '密码登录',
        'weixin' => '扫码登录',
      ),
      'mode' => 
      array (
        0 => 'account',
      ),
      'auto_register' => '0',
      'captcha' => '0',
      'interval' => '10',
      'times' => '2',
    ),
    'post' => 
    array (
      'captcha' => '1',
      'interval' => '10',
    ),
    'forward' => '0',
    'coverpic' => '/ui/coverpic.jpg',
    'node' => 
    array (
      'max' => '10',
    ),
    'report' => 
    array (
      'reason' => '垃圾广告信息
不实信息
辱骂、人身攻击等不友善行为
有害信息
涉嫌侵权
诱导赞同、关注等行为',
    ),
    'open' => 
    array (
      'WX' => 
      array (
        'enable' => '0',
        'id' => '1',
        'name' => '微信',
        'appid' => '',
        'appkey' => '',
        'redirect' => '',
      ),
      'QQ' => 
      array (
        'enable' => '0',
        'id' => '2',
        'name' => 'QQ',
        'appid' => '',
        'appkey' => '',
        'redirect' => '',
      ),
      'WB' => 
      array (
        'enable' => '0',
        'id' => '3',
        'name' => '微博',
        'appid' => '',
        'appkey' => '',
        'redirect' => '',
      ),
      'TB' => 
      array (
        'enable' => '0',
        'id' => '4',
        'name' => '淘宝',
        'appid' => '',
        'appkey' => '',
        'redirect' => '',
      ),
    ),
  ),
  'cloud' => 
  array (
    'enable' => '0',
    'local' => '0',
    'vendor' => 
    array (
      'AliYunOSS' => 
      array (
        'BucketDomain' => '',
        'Bucket' => '',
        'AccessKey' => '',
        'SecretKey' => '',
        'Dir' => '',
        'domain' => '',
      ),
    ),
  ),
  'files' => 
  array (
  ),
  'APPS:META' => 
  array (
    'actor' => 1,
    'apps' => 1,
    'article' => 1,
    'config' => 1,
    'forms' => 1,
    'forum' => 1,
    'links' => 1,
    'meta' => 1,
    'node' => 1,
    'tag' => 1,
    'test' => 1,
    'user' => 1,
  ),
  'hooks' => 
  array (
    'fields' => 
    array (
      'article' => 
      array (
        'body' => 
        array (
          0 => 'LinksHook::run',
          1 => 'PluginMarkdownHook::run',
        ),
      ),
    ),
  ),
  'chain' => 
  array (
    'limit' => '-1',
  ),
  'links' => 
  array (
    'base' => 'http://v8.icmsdev.com/public/api.php?app=links',
    'template' => '/tools/links.target.htm',
  ),
  'payment' => 
  array (
    'enable' => '0',
    'anonymous' => '0',
    'cookie' => '0',
    'name' => 'i币',
    'unit' => '个',
    'expire' => '600',
    'wx' => 
    array (
      'use_sandbox' => '0',
      'interface' => 'PaymentVendorXhp',
      'app_id' => 'f0fc1f470f8a489ea2e4eb4f45e3c321',
      'mch_id' => 'e5db460e55f445d6a45a6794f0c67f39',
      'mch_key' => 'e5db460e55f445d6a45a6794f0c67f39',
      'notify_url' => '',
      'redirect_url' => '',
      'sslcert' => '',
      'sslkey' => '',
      'return_raw' => '0',
    ),
    'ali' => 
    array (
      'use_sandbox' => '0',
      'interface' => '',
      'app_id' => '',
      'public_key' => '',
      'private_key' => '',
      'notify_url' => '',
      'redirect_url' => '',
      'return_raw' => '0',
    ),
    'charge' => 
    array (
      'ratio' => '1',
      'max' => '50000',
      'info' => '',
    ),
  ),
  'forum' => 
  array (
    'tag' => '已解决,未解决',
    'classify' => '热门标签1,热门标签2',
  ),
  'iurl' => 
  array (
    'article' => 
    array (
      'type' => '2',
      'primary' => 'id',
      'page' => 'p',
      'sort' => 3,
    ),
    'node' => 
    array (
      'type' => '1',
      'primary' => 'id',
      'sort' => 2,
    ),
    'tag' => 
    array (
      'type' => '3',
      'primary' => 'id',
      'sort' => 4,
    ),
    'category' => 
    array (
      'rule' => '1',
      'primary' => 'cid',
    ),
    'content' => 
    array (
      'type' => '4',
      'primary' => 'id',
      'page' => 'p',
      'sort' => 5,
    ),
    'index' => 
    array (
      'type' => '0',
      'primary' => '',
      'sort' => 1,
    ),
    'forum' => 
    array (
      'rule' => '2',
      'primary' => 'id',
      'page' => 'p',
    ),
    'test' => 
    array (
      'type' => '4',
      'primary' => 'id',
      'page' => 'p',
      'sort' => 5,
    ),
  ),
  'routing' => 
  array (
    'ArticleUser' => 'api.php?app=ArticleUser',
    'ArticleUser/manage' => 'api.php?app=ArticleUser&do=manage',
    'ArticleUser/publish' => 'api.php?app=ArticleUser&do=publish',
    'CommentUser/manage' => 'api.php?app=CommentUser&do=manage',
    'FilesUser' => 'api.php?app=FilesUser',
    'FilesUser/add' => 'api.php?app=FilesUser&do=add',
    'FilesUser/browse' => 'api.php?app=FilesUser&do=browse',
    'FilesUser/multi' => 'api.php?app=FilesUser&do=multi',
    'FilesUser/preview' => 'api.php?app=FilesUser&do=preview',
    'ForumUser' => 'api.php?app=ForumUser',
    'ForumUser/manage' => 'api.php?app=ForumUser&do=manage',
    'ForumUser/publish' => 'api.php?app=ForumUser&do=publish',
    'UserNode' => 'api.php?app=UserNode',
    'UserNode/manage' => 'api.php?app=UserNode&do=manage',
    'UserProfile' => 'api.php?app=UserProfile',
    'api' => 'api.php',
    'comment' => 'api.php?app=comment',
    'favorite' => 'api.php?app=favorite',
    'favorite/{id}' => 'api.php?app=favorite&id={id}',
    'forms' => 'api.php?app=forms',
    'forms/save' => 'api.php?app=forms&do=save',
    'forms/{id}' => 'api.php?app=forms&id={id}',
    'payment' => 'api.php?app=payment',
    'public/captcha' => 'api.php?app=public&do=captcha',
    'public/license:charge' => 'api.php?app=public&do=license&s=charge',
    'public/privacy' => 'api.php?app=public&do=privacy',
    'public/terms' => 'api.php?app=public&do=terms',
    'search' => 'api.php?app=search',
    'user' => 'api.php?app=user',
    'user/article' => 'api.php?app=user&do=manage&pg=article',
    'user/content/home' => 'api.php?app=user&do=content&s=home',
    'user/findpwd' => 'api.php?app=user&do=findpwd',
    'user/home' => 'api.php?app=user&do=home',
    'user/login' => 'api.php?app=user&do=login',
    'user/login/qq' => 'api.php?app=user&do=login&sign=qq',
    'user/login/qrcode' => 'api.php?app=user&do=login&s=qrcode',
    'user/login/wb' => 'api.php?app=user&do=login&sign=wb',
    'user/login/wx' => 'api.php?app=user&do=login&sign=wx',
    'user/logout' => 'api.php?app=user&do=logout',
    'user/manage' => 'api.php?app=user&do=manage',
    'user/manage/category' => 'api.php?app=user&do=manage&s=category',
    'user/manage/charge' => 'api.php?app=user&do=manage&s=charge',
    'user/manage/comment' => 'api.php?app=user&do=manage&s=comment',
    'user/manage/email' => 'api.php?app=user&do=manage&s=email',
    'user/manage/fans' => 'api.php?app=user&do=manage&s=fans',
    'user/manage/favorite' => 'api.php?app=user&do=manage&s=favorite',
    'user/manage/follow' => 'api.php?app=user&do=manage&s=follow',
    'user/manage/history' => 'api.php?app=user&do=manage&s=history',
    'user/manage/phone' => 'api.php?app=user&do=manage&s=phone',
    'user/manage/profile' => 'api.php?app=user&do=manage&s=profile',
    'user/manage/vip' => 'api.php?app=user&do=manage&s=vip',
    'user/message/inbox' => 'api.php?app=user&do=message&s=inbox',
    'user/profile' => 'api.php?app=user&do=profile',
    'user/profile/avatar' => 'api.php?app=user&do=profile&pg=avatar',
    'user/profile/base' => 'api.php?app=user&do=profile&pg=base',
    'user/profile/bind' => 'api.php?app=user&do=profile&pg=bind',
    'user/profile/custom' => 'api.php?app=user&do=profile&pg=custom',
    'user/profile/setpassword' => 'api.php?app=user&do=profile&pg=setpassword',
    'user/publish' => 'api.php?app=user&do=manage&pg=publish',
    'user/register' => 'api.php?app=user&do=register',
    'user/reminder' => 'api.php?app=user&do=reminder',
    '{uid}' => 'api.php?app=user&do=home&uid={uid}',
    '{uid}/comment' => 'api.php?app=user&do=comment&uid={uid}',
    '{uid}/fans' => 'ap;i.php?app=user&do=fans&uid={uid}',
    '{uid}/favorite' => 'api.php?app=user&do=favorite&uid={uid}',
    '{uid}/favorite/{id}' => 'api.php?app=user&do=favorite&uid={uid}&id={id}',
    '{uid}/follower' => 'api.php?app=user&do=follower&uid={uid}',
    '{uid}/home' => 'api.php?app=user&do=home&uid={uid}',
    '{uid}/share' => 'api.php?app=user&do=share&uid={uid}',
    '{uid}/{cid}' => 'api.php?app=user&do=home&uid={uid}&cid={cid}',
  ),
  'meta' => NULL,
);