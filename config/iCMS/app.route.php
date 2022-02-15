<?php
defined('iPHP') OR exit('Access Denied');
return array (
  'api' => 
  array (
    0 => '/api',
    1 => 'api.php',
  ),
  'comment' => 
  array (
    0 => '/comment',
    1 => 'api.php?app=comment',
  ),
  'favorite' => 
  array (
    0 => '/favorite',
    1 => 'api.php?app=favorite',
  ),
  'favorite:id' => 
  array (
    0 => '/favorite/{id}/',
    1 => 'api.php?app=favorite&id={id}',
  ),
  'forms' => 
  array (
    0 => '/forms',
    1 => 'api.php?app=forms',
  ),
  'forms:id' => 
  array (
    0 => '/forms/{id}/',
    1 => 'api.php?app=forms&id={id}',
  ),
  'forms:save' => 
  array (
    0 => '/forms/save',
    1 => 'api.php?app=forms&do=save',
  ),
  'public:captcha' => 
  array (
    0 => '/public/captcha',
    1 => 'api.php?app=public&do=captcha',
  ),
  'public:privacy' => 
  array (
    0 => '/public/privacy',
    1 => 'api.php?app=public&do=privacy',
  ),
  'public:terms' => 
  array (
    0 => '/public/terms',
    1 => 'api.php?app=public&do=terms',
  ),
  'search' => 
  array (
    0 => '/search',
    1 => 'api.php?app=search',
  ),
  'test' => 
  array (
    0 => '/test',
    1 => 'test.php',
  ),
  'uid:cid' => 
  array (
    0 => '/{uid}/{cid}/',
    1 => 'api.php?app=user&do=home&uid={uid}&cid={cid}',
  ),
  'uid:comment' => 
  array (
    0 => '/{uid}/comment/',
    1 => 'api.php?app=user&do=comment&uid={uid}',
  ),
  'uid:fans' => 
  array (
    0 => '/{uid}/fans/',
    1 => 'api.php?app=user&do=fans&uid={uid}',
  ),
  'uid:favorite' => 
  array (
    0 => '/{uid}/favorite/',
    1 => 'api.php?app=user&do=favorite&uid={uid}',
  ),
  'uid:favorite:id' => 
  array (
    0 => '/{uid}/favorite/{id}/',
    1 => 'api.php?app=user&do=favorite&uid={uid}&id={id}',
  ),
  'uid:follower' => 
  array (
    0 => '/{uid}/follower/',
    1 => 'api.php?app=user&do=follower&uid={uid}',
  ),
  'uid:home' => 
  array (
    0 => '/{uid}/',
    1 => 'api.php?app=user&do=home&uid={uid}',
  ),
  'uid:share' => 
  array (
    0 => '/{uid}/share/',
    1 => 'api.php?app=user&do=share&uid={uid}',
  ),
  'user' => 
  array (
    0 => '/user',
    1 => 'api.php?app=user',
  ),
  'user:article' => 
  array (
    0 => '/user/article',
    1 => 'api.php?app=user&do=manage&pg=article',
  ),
  'user:findpwd' => 
  array (
    0 => '/user/findpwd',
    1 => 'api.php?app=user&do=findpwd',
  ),
  'user:home' => 
  array (
    0 => '/user/home',
    1 => 'api.php?app=user&do=home',
  ),
  'user:login' => 
  array (
    0 => '/user/login',
    1 => 'api.php?app=user&do=login',
  ),
  'user:login:qq' => 
  array (
    0 => '/user/login/qq',
    1 => 'api.php?app=user&do=login&sign=qq',
  ),
  'user:login:wb' => 
  array (
    0 => '/user/login/wb',
    1 => 'api.php?app=user&do=login&sign=wb',
  ),
  'user:login:wx' => 
  array (
    0 => '/user/login/wx',
    1 => 'api.php?app=user&do=login&sign=wx',
  ),
  'user:logout' => 
  array (
    0 => '/user/logout',
    1 => 'api.php?app=user&do=logout',
  ),
  'user:manage' => 
  array (
    0 => '/user/manage',
    1 => 'api.php?app=user&do=manage',
  ),
  'user:manage:category' => 
  array (
    0 => '/user/manage/category',
    1 => 'api.php?app=user&do=manage&s=category',
  ),
  'user:manage:comment' => 
  array (
    0 => '/user/manage/comment',
    1 => 'api.php?app=user&do=manage&s=comment',
  ),
  'user:manage:fans' => 
  array (
    0 => '/user/manage/fans',
    1 => 'api.php?app=user&do=manage&s=fans',
  ),
  'user:manage:favorite' => 
  array (
    0 => '/user/manage/favorite',
    1 => 'api.php?app=user&do=manage&s=favorite',
  ),
  'user:manage:follow' => 
  array (
    0 => '/user/manage/follow',
    1 => 'api.php?app=user&do=manage&s=follow',
  ),
  'user:profile' => 
  array (
    0 => '/user/profile',
    1 => 'api.php?app=user&do=profile',
  ),
  'user:profile:avatar' => 
  array (
    0 => '/user/profile/avatar',
    1 => 'api.php?app=user&do=profile&pg=avatar',
  ),
  'user:profile:base' => 
  array (
    0 => '/user/profile/base',
    1 => 'api.php?app=user&do=profile&pg=base',
  ),
  'user:profile:bind' => 
  array (
    0 => '/user/profile/bind',
    1 => 'api.php?app=user&do=profile&pg=bind',
  ),
  'user:profile:custom' => 
  array (
    0 => '/user/profile/custom',
    1 => 'api.php?app=user&do=profile&pg=custom',
  ),
  'user:profile:setpassword' => 
  array (
    0 => '/user/profile/setpassword',
    1 => 'api.php?app=user&do=profile&pg=setpassword',
  ),
  'user:publish' => 
  array (
    0 => '/user/publish',
    1 => 'api.php?app=user&do=manage&pg=publish',
  ),
  'user:register' => 
  array (
    0 => '/user/register',
    1 => 'api.php?app=user&do=register',
  ),
);