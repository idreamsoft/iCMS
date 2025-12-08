<?php
try {
    require_once __DIR__ . '/../../iCMS.php';
    $key = Request::ip();
    $time = time();
    Crypt::init($key);
    $hashArr = Crypt::decrypt(Request::post('hash'));
    if ($hashArr['host'] != $_SERVER['HTTP_HOST']) {
        iJson::fail('非法请求');
    }
    $lock_file = sprintf('%s/install.lock', iPHP_CONFIG_DIR);
    if (!is_file($lock_file)) {
        Config::cache();
        File::put($lock_file, 'iCMS.' . time(), false);
        File::rmdir(__DIR__);
        iJson::success();
    }
} catch (\Exception $ex) {
    iJson::fail('缓存更新出错');
}
