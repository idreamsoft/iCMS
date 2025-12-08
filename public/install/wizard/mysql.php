<div id="mysql_db">
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_HOST">服务器地址</label>
        <div class="col-sm-9">
            <div class="input-group">
                <input type="text" class="form-control" id="DB_HOST" name="DB_HOST" value="localhost">
                <span class="input-group-text font-weight-bold">:</span>
                <input type="text" class="form-control col-3" id="DB_PORT" name="DB_PORT" value="3306" style="width: 120px;">
            </div>
            <span class="fs-sm text-muted">数据库服务器名或服务器ip和数据库端口,一般为localhost:3306</span>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_USER">数据库用户名</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="DB_USER" name="DB_USER" placeholder="数据库用户名">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_PASSWORD">数据库密码</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="DB_PASSWORD" name="DB_PASSWORD" placeholder="数据库密码">
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_NAME">数据库名</label>
        <div class="col-sm-9">
            <div class="input-group">
                <input type="text" class="form-control" id="DB_NAME" name="DB_NAME" placeholder="数据库名">

                <span class="input-group-text">
                    <div class=" form-check form-check-inline me-0">
                        <input type="checkbox" class="form-check-input" id="CREATE_DATABASE" name="CREATE_DATABASE" value="1">
                        <label class="form-check-label" for="CREATE_DATABASE">创建数据库</label>
                    </div>
                </span>

            </div>
            <span class="fs-sm text-muted">数据库用户需要拥有创建数据库权限才能自动创建数据库,如果没有权限请先创建</span>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_PREFIX">数据表名前缀</label>
        <div class="col-sm-9">
            <input type="text" class="form-control" id="DB_PREFIX" name="DB_PREFIX" value="icms_">
            <span class="fs-sm text-muted">数据表名前缀，同一数据库安装多个请修改此处。<span class="label label-important">如果存在同名数据表，程序将自动删除</span></span>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_CHARSET">数据字符集</label>
        <div class="col-sm-9">
            <select id="DB_CHARSET" name="DB_CHARSET" class="form-control" i="cms:ui:bs-selectpicker">
                <option value="utf8mb4" selected="selected">utf8mb4 支持emoji</option>
                <option value="utf8">utf8</option>
            </select>
            <span class="fs-sm text-muted">默认utf8,如果有emoji表情的话请选择utf8mb4。<span class="label label-important">MySQL 5.5.3及以上版本才支持utf8mb4</span></span>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="DB_ENGINE">数据库引擎</label>
        <div class="col-sm-9">
            <select id="DB_ENGINE" name="DB_ENGINE" class="form-control" i="cms:ui:bs-selectpicker">
                <option value="InnoDB" selected="selected">InnoDB</option>
                <option value="MyISAM">MyISAM</option>
            </select>
            <span class="fs-sm text-muted">默认InnoDB,如果数据量请求量都不大可以选MyISAM。</span>
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-3 col-form-label text-sm-end" for="SETUP_MODE">安装方式</label>
        <div class="col-sm-9">
            <select id="SETUP_MODE" name="SETUP_MODE" class="form-control" i="cms:ui:bs-selectpicker">
                <option value="new" selected="selected">全新安装</option>
                <option value="cover">覆盖安装</option>
            </select>
            <span class="fs-sm text-muted">覆盖安装会清空原有数据。</span>
        </div>
    </div>
</div>