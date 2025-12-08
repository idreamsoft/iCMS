<?php defined('iPHP') or exit(); ?>
<div class="tab-pane" id="wizard-validation-step3" role="tabpanel">
    <?php $step3 = true; ?>
    <h1>安装须知</h1>
    <p>欢迎使用 iCMS
        <?php echo iCMS_VERSION; ?>，本向导将帮助您将程序完整地安装在您的服务器内。
    </p>
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
                    <td>PHP 7.3以上</td>
                    <td>PHP
                        <?php echo PHP_VERSION; ?>
                        <?php if (version_compare(PHP_VERSION, '7.3', '<')) {
                            $step3 = false;
                            echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
                        } ?>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>数据库支持</td>
                    <td>推荐 MySQL 5.6 或者 Sqlite3 以上</td>
                    <td>
                        <?php
                        if (extension_loaded('pdo_mysql')||extension_loaded('pdo_sqlite')) {
                            extension_loaded('pdo_mysql') && print('pdo_mysql 支持 ');
                            extension_loaded('pdo_sqlite') && print('pdo_sqlite 支持');
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
                <tr>
                    <td>8</td>
                    <td>openssl</td>
                    <td>支持</td>
                    <td>
                        <?php
if (extension_loaded('openssl')) {
    echo OPENSSL_VERSION_TEXT; 
} else {
    $step3 = false;
    echo '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 不支持</span>';
}
?>
                    </td>
                </tr>
                <tr>
                    <td>9</td>
                    <td>session</td>
                    <td>支持</td>
                    <td>
                        <?php
if (function_exists('session_start')) {
    echo '支持'; 
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
        <input type="checkbox" class="form-check-input" id="wizard-validation-step3" name="wizard-validation-step3">
        <button type="button" class="btn btn-lg btn-danger" onclick="javascript:window.location.reload();">请按提示开启后刷新重新检测</button>
    </div>
    <?php } ?>
</div>
<!-- END Step 3 -->