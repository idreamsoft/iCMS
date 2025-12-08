<?php defined('iPHP') or exit(); ?>

<div class="tab-pane" id="wizard-validation-step5" role="tabpanel">
      <h1>配置信息</h1>
      <h2>1.数据库配置</h2>
      <input name="action" type="hidden" value="install" />
      <div class="form-group row">
          <label class="col-sm-3 col-form-label text-sm-end">数据库驱动</label>
          <div class="col-sm-9">
              <div class="row items-push">
                  <div class="col-md-6">
                    <div class="form-check form-block">
                      <input class="form-check-input" type="radio" value="mysql" id="db-mysql" name="DB_DRIVER" checked>
                      <label class="form-check-label" for="db-mysql">
                        <span class="d-flex align-items-center">
                          <img class="img-thumbnail" style="height: 36px;" src="./assets/Mysql.png" alt="">
                          <span class="ms-2">
                            <span class="fw-bold">MySQL</span>
                            <span class="d-block fs-sm text-muted">需要配置</span>
                          </span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check form-block">
                      <input class="form-check-input" type="radio" value="sqlite" id="db-sqlite" name="DB_DRIVER">
                      <label class="form-check-label" for="db-sqlite">
                        <span class="d-flex align-items-center">
                          <img class="img-thumbnail" style="height: 36px;" src="./assets/SQLite.png" alt="">
                          <span class="ms-2">
                            <span class="fw-bold">SQLite</span>
                            <span class="d-block fs-sm text-muted" style="font-size: 11px !important;">轻量级配置简易</span>
                          </span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
          </div>
      </div>
      <?php include __DIR__ . '/mysql.php'; ?>

      <h2>2.设置超级管理员</h2>
      <div class="form-group row">
          <label class="col-sm-3 col-form-label text-sm-end" for="ADMIN_NAME">账号</label>
          <div class="col-sm-9">
              <input type="text" id="ADMIN_NAME" name="ADMIN_NAME" class="form-control" placeholder="管理员账号">
          </div>
      </div>
      <div class="form-group row">
          <label class="col-sm-3 col-form-label text-sm-end" for="ADMIN_PASSWORD">密码</label>
          <div class="col-sm-9">
              <input type="text" id="ADMIN_PASSWORD" name="ADMIN_PASSWORD" class="form-control" placeholder="管理员密码">
              <span class="fs-sm text-muted">管理员密码，请设置至少6位以上带字母、数字及符号的密码</span>
          </div>
      </div>
      <h2>3.网站配置</h2>
      <div class="form-group row">
          <label class="col-sm-3 col-form-label text-sm-end" for="ROUTE_URL">网站URL</label>
          <div class="col-sm-9">
              <input type="text" name="ROUTE_URL" class="form-control" id="ROUTE_URL" value="<?php echo $_URL; ?>" />
          </div>
      </div>
      <div class="form-group row">
          <label class="col-sm-3 col-form-label text-sm-end"></label>
          <div class="col-sm-9 text-center">
              <input name="action" type="hidden" value="install" />
              <button type="button" class="btn btn-lg btn-primary py-2 px-5" id="install-btn"><i class="fa fa-fw fa-check me-1"></i> 开始安装</button>
              <a href="javascript:;" id="install-btn-reset" class="hide">重置</a>
          </div>
      </div>
</div>