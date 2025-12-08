<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
define('iPHP', true);
define('iPHP_APP', 'iCMS'); //应用名
define('iPHP_APP_MAIL', 'master@icmsdev.com');
//iCMS版本
require_once __DIR__ . '/../../config/define.php'; //定义常量
require_once __DIR__ . '/../../config/git.php'; //GIT版本
require_once __DIR__ . '/../../config/version.php';
//加载iPHP框架
require_once __DIR__ . '/../../iPHP/bootstrap.php';

$_URI = $_SERVER['PHP_SELF'];
$_DIR = substr(dirname($_URI), 0, -8);
$_DIR = trim($_DIR, '/') . '/';
$_DIR == '/' or $_DIR = '/' . $_DIR;
$_URL = $_SERVER['REQUEST_HOST'] . rtrim($_DIR, '/');
$lock_file = sprintf('%s/install.lock', iPHP_CONFIG_DIR);
$site = Application::site();
$check_list = [
    ['/', '系统根目录'],
    ['/cache', '缓存目录'],
    ['/cache/template', '模板缓存目录'],
    ['/cache/' . $site, '当前站点缓存目录'],
    ['/config', '配置目录'],
    ['/config/secretkey.php', '系统密钥文件'],
    ['/config/' . $site, '当前站点配置目录'],
    ['/public/install', '安装程序'],
    ['/public/uploads', '资源上传目录']
];
$key = Request::ip();
Crypt::init($key);
$hash = Crypt::encrypt([
    'time'   => time(),
    'once'   => random(16),
    'referer'=> $_SERVER['HTTP_REFERER'],
    'host'   => $_SERVER['HTTP_HOST'],
]);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>iCMS<?php echo iCMS_VERSION; ?> - 安装向导</title>
    <meta name="Copyright" content="iCMSdev.com" />
    <meta name="author" content="iCMSdev" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="shortcut icon" href="/assets/img/favicon.png" />
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/favicon-192x192.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon-180x180.png" />
    <link rel="stylesheet" href="/assets/libs/oneui/dist/assets/css/oneui.min.css?v=<?php echo time(); ?>" id="css-main" />
    <link rel="stylesheet" href="/assets/libs/iCMS/css/iCMS.css?v=<?php echo time(); ?>" id="css-iCMS" />
    <link rel="stylesheet" href="/assets/css/admincp.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./assets/install.css?v=<?php echo time(); ?>" />
    <script type="text/javascript">
        const $APP = {
            init: true,
            bootstrap: "cms",
            SCRIPT_NAME: "/api.php",
            CSRF_TOKEN: '<?php echo Security::$CSRF_TOKEN; ?>',
            url: "/api.php",
            do: "install",
            method: "do_install",
            name: "install",
            lang: false,
            jspath: "assets/install.js",
            jsdebug: false,
            gitTime: '<?php echo short_md5(iCMS_GIT_TIME); ?>',
            release: '<?php echo short_md5(iCMS_RELEASE); ?>'
        }
    </script>
    
    <script src="/assets/libs/require.js" data-main="/assets/js/init.min.js?v=<?php echo time(); ?>"></script>
</head>

<body class="modern-install">
    <div id="page-container">
        <main id="main-container">
            <!-- Modern Hero Section -->
            <div class="hero-section">
                <div class="hero-overlay">
                    <div class="container">
                        <div class="hero-content">
                            <div class="brand-logo">
                                <div class="logo-text">
                                    <span class="logo-i">i</span><span class="logo-cms">CMS</span>
                                </div>
                                <div class="version-badge">V8.0</div>
                            </div>
                            <h1 class="hero-title">安装向导</h1>
                            <p class="hero-subtitle">简洁、高效、开源的内容管理系统<br>让网站管理更简单</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="install-content">
                <!-- Page Content -->
                <div class="container">
                    <?php if (@is_file($lock_file)) {?>
                            <div class="install-complete-card">
                                <div class="complete-icon">✅</div>
                                <h2>安装已完成</h2>
                                <p>您已经成功安装了 iCMS，如需重新安装，请删除 config/install.lock 文件。</p>
                                <a href="/" class="btn btn-primary btn-lg">访问网站</a>
                            </div>
                    <?php } else { ?>  
                    <!-- Validation Wizard -->
                    <div class="js-wizard-validation block install-wizard">
                        <!-- Step Tabs -->
                            <div class="step-progress">
                                <div class="step-progress-line"></div>
                                <a href="javascript:;" class="step-item active" data-step="1" data-prev="false" data-next="false">
                                    <div class="step-circle">1</div>
                                    <div class="step-label">系统简介</div>
                                </a>
                                <a  href="javascript:;" class="step-item" data-step="2" data-next="false">
                                    <div class="step-circle">2</div>
                                    <div class="step-label">许可协议</div>
                                </a>
                                <a  href="javascript:;" class="step-item" data-step="3">
                                    <div class="step-circle">3</div>
                                    <div class="step-label">安装须知</div>
                                </a>
                                <a  href="javascript:;" class="step-item" data-step="4">
                                    <div class="step-circle">4</div>
                                    <div class="step-label">环境检测</div>
                                </a>
                                <a  href="javascript:;" class="step-item" data-step="5" data-next="false">
                                    <div class="step-circle">5</div>
                                    <div class="step-label">配置信息</div>
                                </a>
                                <a  href="javascript:;" class="step-item" data-step="6">
                                    <div class="step-circle">6</div>
                                    <div class="step-label">安装完成</div>
                                </a>
                            </div>
                        <ul i="cms:ui:tabs" class="nav nav-tabs nav-tabs-alt nav-justified wizard-steps" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="#wizard-validation-step1" data-toggle="tab" data-prev="false" data-next="false" ></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#wizard-validation-step2" data-toggle="tab" data-next="false"></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#wizard-validation-step3" data-toggle="tab"></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#wizard-validation-step4" data-toggle="tab"></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#wizard-validation-step5" data-toggle="tab" data-next="false"></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#wizard-validation-step6" data-toggle="tab"></a>
                            </li>
                        </ul>
                        <!-- END Step Tabs -->

                        <!-- Form -->
                        <form class="js-wizard-validation-form" method="POST" novalidate="novalidate" onsubmit="return false;">
                            <input type="hidden" name="hash" value="<?php echo $hash?>">
                            <!-- Steps Content -->
                            <div class="block-content block-content-full tab-content px-md-5" style="min-height: 300px;">
                                <?php include __DIR__ . '/wizard/step1.php'; ?>
                                <?php include __DIR__ . '/wizard/step2.php'; ?>
                                <?php include __DIR__ . '/wizard/step3.php'; ?>
                                <?php include __DIR__ . '/wizard/step4.php'; ?>
                                <?php include __DIR__ . '/wizard/step5.php'; ?>
                                <?php include __DIR__ . '/wizard/step6.php'; ?>
                            </div>
                            <!-- END Steps Content -->

                            <!-- Steps Navigation -->
                            <div class="block-content block-content-sm block-content-full bg-gray rounded-bottom p-4">
                                <div class="row" id="wizard-btn">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-lg btn-secondary py-3 px-5 disabled hide" data-wizard="prev"><i class="fa fa-fw fa-angle-left me-1"></i> 上一步</button>
                                    </div>
                                    <div class="col-6 text-end">
                                        <button type="button" class="btn btn-lg btn-success py-3 px-5 hide" data-wizard="next">下一步 <i class="fa fa-fw fa-angle-right ms-1"></i></button>
                                        <button type="button" class="btn btn-lg btn-primary  py-3 px-5 d-none" data-wizard="finish"><i class="fa fa-fw fa-check me-1"></i> 安装完成</button>
                                    </div>
                                </div>
                            </div>
                            <!-- END Steps Navigation -->
                        </form>
                        <!-- END Form -->
                    </div>
                    <?php }; ?>
                    <!-- END Validation Wizard Classic -->
                </div>
                <!-- END Page Content -->
            </div>        
        </main>
        <footer id="page-footer" class="bg-body-light">
            <div class="content py-3">
                <div class="row fs-sm">
                    <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                        iCMS 源码受 <a href="https://www.icmsdev.com/LICENSE.html" target="_blank">LGPLv3</a> 开源协议保护<br />软件著作权号:<?php echo iCMS_COPYRIGHT; ?>
                    </div>
                    <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                        Powered <i class="fa fa-fw fa-fire text-danger"></i> by <a class="fw-semibold" href="https://www.icmsdev.com" target="_blank">iCMS <?php echo iCMS_VERSION; ?></a>。 <br />iCMS (<a href="https://www.icmsdev.com" target="_blank">iCMSdev.com</a>)
                        版权所有 &copy; <span data-toggle="year-copy"></span>。
                    </div>
                </div>
            </div>
        </footer>
        <div class="d-none">
            <iframe class="d-none" id="iCMS_FRAME" name="iCMS_FRAME"></iframe>
            <script type="text/javascript" src="https://www.icmsdev.com/cms/install.php"></script>
            <script type="text/javascript">
                var _bdhmProtocol = (("https:" == document.location.protocol) ? " https://" : " http://");
                document.write(unescape("%3Cscript src='" + _bdhmProtocol +
                    "hm.baidu.com/h.js%3F7b43330a4da4a6f4353e553988ee8a62' type='text/javascript'%3E%3C/script%3E"));
            </script>
        </div>
    </div>
</body>

</html>