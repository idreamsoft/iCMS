<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
define('iPHP', TRUE);
define('iPHP_APP', 'iCMS'); //应用名
define('iPHP_APP_MAIL', 'master@icmsdev.com');
//加载iPHP框架
require_once __DIR__.'/../iPHP/bootstrap.php';
//iCMS版本
require_once iPHP_PATH . '/config/define.php';
require_once iPHP_PATH . '/config/version.php';

$_URI      = $_SERVER['PHP_SELF'];
$_DIR      = substr(dirname($_URI), 0, -8);
$_DIR      = trim($_DIR, '/') . '/';
$_DIR == '/' or $_DIR = '/' . $_DIR;
$_URL      = $_SERVER['HTTP_URL'] . rtrim($_DIR, '/');
$lock_file = sprintf('%s/install.lock',iPHP_CONFIG_DIR);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>iCMS
        <?php echo iCMS_VERSION; ?> - 安装向导</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta content="iCMSdev.com" name="Copyright" />
    <link rel="stylesheet" id="css-main" href="../assets/oneui/css/oneui.min.css">
    <link rel="stylesheet" href="../assets/oneui/js/plugins/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="./assets/install.css">
    <script src="../assets/oneui/js/oneui.core.min.js"></script>
    <script src="../assets/oneui/js/oneui.app.min.js"></script>
    <script src="../assets/oneui/js/plugins/jquery-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
    <script src="../assets/oneui/js/plugins/jquery-validation/jquery.validate.min.js"></script>
    <script src="../assets/oneui/js/plugins/jquery-validation/additional-methods.js"></script>
    <script src="../assets/oneui/js/plugins/es6-promise/es6-promise.auto.min.js"></script>
    <script src="../assets/oneui/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="../assets/oneui/js/plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="../assets/iCMS.js"></script>

    <script src="./assets/install.js"></script>
</head>

<body>
    <div id="page-container">
        <main id="main-container">
            <div class="bg-image" style="background-image: url('./assets/galaxy.jpg');">
                <div class="bg-black-50">
                    <div class="content content-full text-center masthead">
                        <div class="mt-7 mb-5 text-center">
                            <h1 class="text-white mb-2 js-appear-enabled animated fadeInDown" data-toggle="appear" data-class="animated fadeInDown"><span style="color: #ffffff">i</span><span style="color: #31B8FF">CMS</span>
                                <span style="color: #f5f5f5"><?php echo iCMS_VERSION; ?></span>
                            </h1>
                            <h2 class="font-w400 text-white-75 js-appear-enabled animated fadeInDown" data-toggle="appear" data-class="animated fadeInDown">简洁、高效、开源的内容管理系统，让网站管理更简单。</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="content">
                <?php if(@is_file($lock_file)){?>
                    <div class="alert alert-warning text-center h1">您已经安装过了！</div>
                    <div class="alert alert-info text-center">如需重新安装，请将 config/install.lock 删除。</div>
                <?php }else{;?>  
                <!-- Validation Wizard -->
                <div class="js-wizard-validation block block">
                    <!-- Step Tabs -->
                    <ul class="nav nav-tabs nav-tabs-alt nav-justified wizard-steps" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#wizard-validation-step1" data-toggle="tab"><span class="badge badge-dark">1</span> iCMS简介</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#wizard-validation-step2" data-toggle="tab" wizard-btn="true"><span class="badge badge-dark">2</span> 许可协议</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#wizard-validation-step3" data-toggle="tab"><span class="badge badge-dark">3</span> 安装须知</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#wizard-validation-step4" data-toggle="tab"><span class="badge badge-dark">4</span> 环境检测</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#wizard-validation-step5" data-toggle="tab" wizard-btn="true"><span class="badge badge-dark">5</span> 配置信息</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#wizard-validation-step6" data-toggle="tab"><span class="badge badge-dark">6</span> 安装完成</a>
                        </li>
                    </ul>
                    <!-- END Step Tabs -->

                    <!-- Form -->
                    <form class="js-wizard-validation-form" method="POST" novalidate="novalidate" onsubmit="return false;">
                        <!-- Steps Content -->
                        <div class="block-content block-content-full tab-content px-md-5" style="min-height: 300px;">
                            <!-- Step 1 -->
                            <div class="tab-pane active" id="wizard-validation-step1" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 col-xl-3 js-appear-enabled animated fadeInUp">
                                        <a class="block block-themed block-link-shadow text-center" href="javascript:void(0)">
                                            <div class="block-header bg-primary-darker">
                                                <h3 class="block-title">十年磨一剑</h3>
                                            </div>
                                            <div class="block-content">
                                                <div class="py-2">
                                                    <p class="mb-2"><span class="item item-2x item-circle border mx-auto"><i class="si si-diamond fa-2x text-primary"></i></span></p>
                                                    <p class="h3 text-muted">开源且免费</p>
                                                </div>
                                            </div>
                                            <div class="block-content">
                                                <div class="font-size-sm py-2 text-left">
                                                    <p>历时多年精心开发，并在自主实际项目中高效运行</p>
                                                    <p class="my-0"><strong>iPHP</strong> 简洁易用的PHP框架</p>
                                                    <p class="my-0"><strong>OneUI</strong> 正版精美强大的前端框架</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6 col-xl-3 js-appear-enabled animated fadeInUp">
                                        <a class="block block-themed block-link-shadow text-center" href="javascript:void(0)">
                                            <div class="block-header bg-primary-darker">
                                                <h3 class="block-title">多终端适配</h3>
                                            </div>
                                            <div class="block-content">
                                                <div class="py-2">
                                                    <p class="mb-2"><span class="item item-2x item-circle border mx-auto"><i class="si si-screen-smartphone fa-2x text-primary"></i></span></p>
                                                    <p class="h3 text-muted">适配多种设备</p>
                                                </div>
                                            </div>
                                            <div class="block-content">
                                                <div class="font-size-sm py-2 text-left">
                                                    <p>
                                                        iCMS灵活的适应多种设备让你的项目能在通过一套内容管理系统快速、有效适配手机、微信、微信小程序、平板、PC等多种设备，这一切都是归于 iCMS 多终端适配功能。
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6 col-xl-3 js-appear-enabled animated fadeInUp">
                                        <a class="block block-themed block-link-shadow text-center" href="javascript:void(0)">
                                            <div class="block-header bg-primary-darker">
                                                <h3 class="block-title">快速开发</h3>
                                            </div>
                                            <div class="block-content">
                                                <div class="py-2">
                                                    <p class="mb-2"><span class="item item-2x item-circle border mx-auto"><i class="si si-puzzle fa-2x text-primary"></i></span></p>
                                                    <p class="h3 text-muted">零代码开发应用</p>
                                                </div>
                                            </div>
                                            <div class="block-content">
                                                <div class="font-size-sm py-2 text-left">
                                                    <p>控制台可视化拖拉构建您所需的各种完整的应用,方便实现您天马行空的想法。
                                                        <br />
                                                        亦可以通过二次开发,简单快速的把你所需要的功能添加到iCMS上,省去你很多麻烦的程序开发问题。
                                                    </p>

                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6 col-xl-3 js-appear-enabled animated fadeInUp">
                                        <a class="block block-themed block-link-shadow text-center" href="javascript:void(0)">
                                            <div class="block-header bg-primary-darker">
                                                <h3 class="block-title">完整功能支持</h3>
                                            </div>
                                            <div class="block-content">
                                                <div class="py-2">
                                                    <p class="mb-2"><span class="item item-2x item-circle border mx-auto"><i class="si si-layers fa-2x text-primary"></i></span></p>
                                                    <p class="h3 text-muted">强大的功能支持</p>
                                                </div>
                                            </div>
                                            <div class="block-content">
                                                <div class="font-size-sm py-2 text-left">
                                                    <p>iCMS 提供了网站运营所需的基本功能。也提供了功能强大标签(TAG)系统、支付、微信、小程序、自定义应用、自定义表单、内容多属性多栏目归属、自定义内链、高负载、整合第三方登录等等
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- END Step 1 -->
                            <!-- Step 2 -->
                            <div class="tab-pane" id="wizard-validation-step2" role="tabpanel">
                                <div class="block">
                                    <h1 class="text-center">iCMS使用许可协议</h1>
                                    <p>感谢您选择iCMS
                                        <?php echo iCMS_VERSION; ?>。希望我们的努力能为您提供一个高效快速和强大的内容管理解决方案。</p>
                                    <p>本软件为开源软件，软件著作权号:<?php echo iCMS_COPYRIGHT; ?>，遵循 <a href="http://www.gnu.org/licenses/lgpl-2.1.html">LGPL</a> (GNU Lesser General Public License)开源协议</p>
                                    <p>本软件版权归 iCMS 官方所有，且受《中华人民共和国计算机软件保护条例》等知识产权法律及国际条约与惯例的保护。</p>
                                    <p>无论个人或组织、盈利与否、用途如何，均需仔细阅读本协议，在理解、同意、并遵守本协议的全部条款后，方可开始使用本软件。 </p>
                                    <h2>开源协议</h2>
                                    <p>iCMS 采用 <a href="http://www.gnu.org/licenses/lgpl-2.1.html">LGPL</a> 开源协议：</p>
                                    <ul>
                                        <li>基于 GPL 的软件允许商业化销售，但不允许封闭源代码。</li>
                                        <li>如果您对遵循 GPL 的软件进行任何改动和/或再次开发并予以发布，则您的产品必须继承 GPL 协议，不允许封闭源代码。</li>
                                        <li>基于 LGPL 的软件也允许商业化销售，但不允许封闭源代码。</li>
                                        <li>如果您对遵循 LGPL 的软件进行任何改动和/或再次开发并予以发布，则您的产品必须继承 LGPL 协议，不允许封闭源代码。<br />但是如果您的程序对遵循 LGPL 的软件进行任何连接、调用而不是包含，则允许封闭源代码。</li>
                                    </ul>
                                    <h2>免责声明</h2>
                                    <ol>
                                        <li>本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的。</li>
                                        <li>您拥有使用本软件构建的网站全部内容所有权，并独立承担与这些内容的相关法律义务。 </li>
                                        <li>用户出于自愿而使用本软件，您必须了解使用本软件的风险，iCMS 不承担任何因使用本软件而产生问题的相关责任。</li>
                                        <li>iCMS 不对使用本软件构建的网站中的文章或信息承担责任。</li>
                                    </ol>
                                    <h2>商业授权</h2>
                                    <p>详情请查看 <a href="https://www.icmsdev.com/docs/service.html" target="_blank">iCMS 商业授权细则</a></p>
                                    <hr />
                                    <p>电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和等同的法律效力。</p>
                                    <div class="alert alert-danger">您一旦开始安装 iCMS，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权力的同时，受到相关的约束和限制。<br />违反本授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。</div>
                                    <div class="form-group text-center">
                                        <button type="button" class="btn btn-lg btn-primary p-3" data-wizard="next">我同意并遵循以上协议，继续安装</button>
                                        <a class="btn btn-sm btn-light" onclick="javascript:window.open(location, '_self').close();">不接受</a>
                                    </div>
                                </div>
                            </div>
                            <!-- END Step 2 -->

                            <!-- Step 3 -->
                            <div class="tab-pane" id="wizard-validation-step3" role="tabpanel">
                                <?php $step3 = true; ?>
                                <h1>安装须知</h1>
                                <p>欢迎使用 iCMS
                                    <?php echo iCMS_VERSION; ?>，本向导将帮助您将程序完整地安装在您的服务器内。</p>
                                <h2>请您先确认以下安装配置: </h2>
                                <ul>
                                    <li>MySQL 主机名称/IP 地址 </li>
                                    <li>MySQL 用户名和密码 </li>
                                    <li>MySQL 数据库名称 </li>
                                </ul>
                                <p class="alert alert-block">如果您无法确认以上的配置信息, 请与您的主机服务商联系, 我们无法为您提供任何帮助.</p>
                                <h2>服务器配置: </h2>
                                <div class="row">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>运行环境</th>
                                                <th>推荐版本</th>
                                                <th>当前版本</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>操作系统</td>
                                                <td>推荐 Linux OR FreeBSD</td>
                                                <td>
                                                    <?php echo PHP_OS; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>WEB服务器</td>
                                                <td>推荐 Nginx OR Apache</td>
                                                <td>
                                                    <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>3</td>
                                                <td>PHP版本</td>
                                                <td>PHP 5.6以上</td>
                                                <td>PHP
                                                    <?php echo PHP_VERSION; ?>
                                                    <?php if (version_compare(PHP_VERSION, '5.3', '<')) {
                                                        $step3 = false;
                                                        echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                                                    } ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>4</td>
                                                <td>MySQL数据库</td>
                                                <td>推荐 MySQL 5.6以上</td>
                                                <td>
                                                    <?php
                                                    if (version_compare(PHP_VERSION, '5.5', '>=') && extension_loaded('mysqli')) {
                                                        echo 'MySQL';
                                                    } elseif (extension_loaded('mysql')) {
                                                        echo 'MySQL';
                                                    } else {
                                                        $step3 = false;
                                                        echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>5</td>
                                                <td>GD库</td>
                                                <td>支持</td>
                                                <td>
                                                    <?php
                                                    if (function_exists('gd_info')) {
                                                        $gd_info = gd_info();
                                                        echo $gd_info['GD Version'];
                                                    } else {
                                                        $step3 = false;
                                                        echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>6</td>
                                                <td>CURL库</td>
                                                <td>支持</td>
                                                <td>
                                                    <?php
                                                    if (function_exists('curl_version')) {
                                                        $curl_version = curl_version();
                                                        echo $curl_version['version'];
                                                    } else {
                                                        $step3 = false;
                                                        echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>7</td>
                                                <td>mbstring</td>
                                                <td>支持</td>
                                                <td>
                                                    <?php
                                                    if (function_exists('mb_convert_encoding')) {
                                                        echo mb_internal_encoding();
                                                    } else {
                                                        $step3 = false;
                                                        echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (!$step3) { ?>
                                    <div class="form-group text-center">
                                        <input type="checkbox" class="custom-control-input" id="wizard-validation-step3" name="wizard-validation-step3">
                                        <button type="button" class="btn btn-lg btn-danger" onclick="javascript:window.location.reload();">请按提示开启后刷新重新检测</button>
                                    </div>
                                <?php } ?>
                            </div>
                            <!-- END Step 3 -->
                            <div class="tab-pane" id="wizard-validation-step4" role="tabpanel">
                                <h1>程序环境检测</h1>
                                <p class="alert alert-info">检查必要目录和文件是否可写，如果发生错误，请更改文件/目录属性 777</p>
                                <?php
                                $step4      = 1;
                                $correct    = '<span class="link-fx text-success"><i class="fa fa-fw fa-check"></i></span>';
                                $incorrect  = '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 777属性检测不通过</span>';
                                $uncorrect  = '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 文件不存在请上传此文件</span>';
                                $site = iAPP::site();
                                $check_list = array(
                                    array('/', '网站根目录'),
                                    array('/cache', '缓存目录'),
                                    array('/cache/template', '模板缓存目录'),
                                    array('/cache/' . $site, '当前站点缓存目录'),
                                    array('/config', '配置目录'),
                                    array('/config/secretkey.php', '系统密钥文件'),
                                    array('/config/' . $site, '当前站点配置目录'),
                                    array('/res', '资源上传目录'),
                                );
                                foreach ($check_list as $key => $value) {
                                    $file = iPHP_PATH . ltrim($value[0], '/');
                                    if (!file_exists($file)) {
                                        $check_list[$key][2] = $uncorrect;
                                        $step4 = 0;
                                    } elseif (is_writable($file)) {
                                        $check_list[$key][2] = $correct;
                                    } else {
                                        $check_list[$key][2] = $incorrect;
                                        $step4 = 0;
                                    }
                                }
                                ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>项目</th>
                                            <th>路径</th>
                                            <th>检查结果</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($check_list as $key => $value) { ?>
                                            <tr>
                                                <td>
                                                    <?php echo $key + 1; ?>
                                                </td>
                                                <td>
                                                    <?php echo $value[1]; ?>
                                                </td>
                                                <td class="text-break">
                                                    <?php echo $value[0]; ?>
                                                </td>
                                                <td>
                                                    <?php echo $value[2]; ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <?php if (!$step4) { ?>
                                    <div class="form-group text-center">
                                        <input type="checkbox" class="custom-control-input" id="wizard-validation-step4" name="wizard-validation-step4">
                                        <button type="button" class="btn btn-lg btn-danger" onclick="javascript:window.location.reload();">请按提示设置权限后刷新重新检测</button>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="tab-pane" id="wizard-validation-step5" role="tabpanel">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8 col-xl-6">
                                        <h1>配置信息</h1>
                                        <h2>1.数据库配置</h2>
                                        <input name="action" type="hidden" value="install" />
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_HOST">服务器地址</label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="DB_HOST" name="DB_HOST" value="localhost">
                                                    <div class="input-group-append"><span class="input-group-text font-weight-bold">:</span></div>
                                                    <input type="text" class="form-control col-3" id="DB_PORT" name="DB_PORT" value="3306" style="width: 120px;">
                                                </div>
                                                <span class="font-size-sm text-muted">数据库服务器名或服务器ip和数据库端口,一般为localhost:3306</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_USER">数据库用户名</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="DB_USER" name="DB_USER" placeholder="数据库用户名">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_PASSWORD">数据库密码</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="DB_PASSWORD" name="DB_PASSWORD" placeholder="数据库密码">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_NAME">数据库名</label>
                                            <div class="col-sm-9">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="DB_NAME" name="DB_NAME" placeholder="数据库名">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">
                                                            <div class="custom-control custom-checkbox custom-control-inline mr-0">
                                                                <input type="checkbox" class="custom-control-input" id="CREATE_DATABASE" name="CREATE_DATABASE" value="1">
                                                                <label class="custom-control-label" for="CREATE_DATABASE">创建数据库</label>
                                                            </div>
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="font-size-sm text-muted">数据库用户需要拥有创建数据库权限才能自动创建数据库,如果没有权限请先创建</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_PREFIX">数据表名前缀</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control" id="DB_PREFIX" name="DB_PREFIX" value="icms_">
                                                <span class="font-size-sm text-muted">数据表名前缀，同一数据库安装多个请修改此处。<span class="label label-important">如果存在同名数据表，程序将自动删除</span></span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_CHARSET">数据字符集</label>
                                            <div class="col-sm-9">
                                                <select id="DB_CHARSET" name="DB_CHARSET" class="form-control">
                                                    <option value="utf8" selected="selected">默认 utf8</option>
                                                    <option value="utf8mb4">utf8mb4 支持emoji</option>
                                                </select>
                                                <span class="font-size-sm text-muted">默认utf8,如果有emoji表情的话请选择utf8mb4。<span class="label label-important">MySQL 5.5.3及以上版本才支持utf8mb4</span></span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="DB_ENGINE">数据库引擎</label>
                                            <div class="col-sm-9">
                                                <select id="DB_ENGINE" name="DB_ENGINE" class="form-control">
                                                    <option value="InnoDB" selected="selected">InnoDB</option>
                                                    <option value="MyISAM">MyISAM</option>
                                                </select>
                                                <span class="font-size-sm text-muted">默认InnoDB,如果数据量请求量都不大可以选MyISAM。</span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="SETUP_MODE">安装方式</label>
                                            <div class="col-sm-9">
                                                <select id="SETUP_MODE" name="SETUP_MODE" class="form-control">
                                                    <option value="new" selected="selected">全新安装</option>
                                                    <option value="cover">覆盖安装</option>
                                                </select>
                                                <span class="font-size-sm text-muted">覆盖安装会清空原有数据。</span>
                                            </div>
                                        </div>
                                        <h2>2.设置超级管理员</h2>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="ADMIN_NAME">账号</label>
                                            <div class="col-sm-9">
                                                <input type="text" id="ADMIN_NAME" name="ADMIN_NAME" class="form-control" placeholder="管理员账号">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="ADMIN_PASSWORD">密码</label>
                                            <div class="col-sm-9">
                                                <input type="text" id="ADMIN_PASSWORD" name="ADMIN_PASSWORD" class="form-control" placeholder="管理员密码">
                                                <span class="font-size-sm text-muted">管理员密码，请设置至少6位以上带字母、数字及符号的密码</span>
                                            </div>
                                        </div>
                                        <h2>3.网站配置</h2>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label text-sm-right" for="ROUTE_URL">网站URL</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="ROUTE_URL" class="form-control" id="ROUTE_URL" value="<?php echo $_URL; ?>" />
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-9 ml-auto">
                                                <input name="action" type="hidden" value="install" />
                                                <button type="button" class="btn btn-lg btn-primary py-2 px-5" id="install_btn"><i class="fa fa-fw fa-check mr-1"></i> 开始安装</button>
                                                <a href="javascript:;" id="install_btn_reset" class="hide">重置</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="wizard-validation-step6" role="tabpanel">
                                <div class="text-center">
                                    <h2>恭喜您！顺利安装完成。</h2>
                                    <div class="col-8 col-sm-5 mt-5 mx-auto">
                                        <a href="../admincp.php" class="btn btn-lg btn-block btn-success" target="_blank">管理后台 <i class="fa fa-fw fa-angle-double-right"></i></a>
                                        <hr />
                                        <a href="../index.php" class="btn btn-lg btn-block btn-primary" target="_blank"><i class="fa fa-fw fa-angle-double-left"></i> 网站首页</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END Steps Content -->

                        <!-- Steps Navigation -->
                        <div class="block-content block-content-sm block-content-full bg-gray rounded-bottom p-4">
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-lg btn-secondary py-3 px-5 disabled" data-wizard="prev"><i class="fa fa-fw fa-angle-left mr-1"></i> 上一步</button>
                                </div>
                                <div class="col-6 text-right" id="wizard-btn">
                                    <button type="button" class="btn btn-lg btn-success py-3 px-5" data-wizard="next">下一步 <i class="fa fa-fw fa-angle-right ml-1"></i></button>
                                    <button type="button" class="btn btn-lg btn-primary  py-3 px-5 d-none" data-wizard="finish"><i class="fa fa-fw fa-check mr-1"></i> 安装完成</button>
                                </div>
                            </div>
                        </div>
                        <!-- END Steps Navigation -->
                    </form>
                    <!-- END Form -->
                </div>
                <?php };?>
                <!-- END Validation Wizard Classic -->
            </div>
            <!-- END Page Content -->

        </main>
        <footer id="page-footer" class="bg-body-light">
            <div class="content py-3">
                <div class="row font-size-sm">
                    <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-right">
                        iCMS 源码受 <a href="https://www.icmsdev.com/LICENSE.html" target="_blank">LGPL</a> 开源协议保护<br />软件著作权号:<?php echo iCMS_COPYRIGHT; ?>
                    </div>
                    <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-left">
                        Powered <i class="fa fa-fw fa-fire text-danger"></i> by <a class="font-w600" href="https://www.icmsdev.com" target="_blank">iCMS <?php echo iCMS_VERSION; ?></a>。 <br />iCMS (<a href="https://www.icmsdev.com" target="_blank">iCMSdev.com</a>)
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