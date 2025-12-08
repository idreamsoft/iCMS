<?php defined('iPHP') or exit();?>

<div class="tab-pane" id="wizard-validation-step4" role="tabpanel">
    <h1>程序环境检测</h1>
    <p class="alert alert-info">检查必要目录和文件是否可写，如果发生错误，请更改文件/目录属性 777</p>
    <?php
        $step4 = 1;
        $correct = '<span class="link-fx text-success"><i class="fa fa-fw fa-check"></i></span>';
        $incorrect = '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 777属性检测不通过</span>';
        $uncorrect = '<span class="link-fx text-danger"><i class="fa fa-fw fa-times"></i> 文件不存在请上传此文件</span>';

        foreach ($check_list as $key => $value) {
            $file = iPHP_APP_PATH . ltrim($value[0], '/');
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
        <input type="checkbox" class="form-check-input" id="wizard-validation-step4" name="wizard-validation-step4">
        <button type="button" class="btn btn-lg btn-danger" onclick="javascript:window.location.reload();">请按提示设置权限后刷新重新检测</button>
    </div>
    <?php } ?>
</div>