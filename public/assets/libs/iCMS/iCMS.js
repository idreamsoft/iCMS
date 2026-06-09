define(function (require) {
    require("func-extend");
    require("jquery-extend");
    console.log("iCMS.js is loaded");
    var Layer = require("layer");
    var Moment = require('moment');
    var LangData = require("lang");

    var iCMS = {
        $SET: {},
        set: function (key, options) {
            iCMS.$SET[key] = $.extend(iCMS.$SET[key], options);
            return this;
        },
        getModPath: function (params, method) {
            let module = params[1];
            if (module == 'ui') {//cms:ui:ooxx 
            } else {
                let path = $APP.modPath; //cms:aaa:ooxx 
                if (params[0] == 'app') { //app:aaa:ooxx 
                    path = $APP.appModPath;
                }
                module = iCMS.utils.display(path, { module, method });
            }
            return module;
        },
        /**
         * 将 data-{key}='cms:aa:bb'转成可回调方法
         * 该转换为异步
         * 调用 iCMS.callDataFunc.call(this,$el, key)
         * @param {jQuery} $el 
         * @param {string} key 
         */
        callDataFunc: function ($el, key) {
            let that = this;
            let flag = $el.data(key);
            if (isString(flag)) {
                if (flag.includes(':')) {
                    var prices = flag.split(":");
                    var method = prices[2];
                    var module = iCMS.getModPath(prices, prices[2]);
                    if (prices.length > 3) {
                        let $slice = prices.slice(3);
                        method += '_' + $slice.join('_');
                    }
                    console.log(prices, module, method);
                    require([module], function (ft) {
                        if (typeof ft === "object") {
                            that[key] = ft[method];
                        } else {
                            that[key] = ft;
                        }
                    });
                } else if (isFunction(window[flag]) == 'function') {
                    that[key] = window[flag];
                }
            }
        },
        /**
         * 加载并执行
         * @param {string} name 
         * @param {string} d0 
         * @param {object} param 
         * @returns 
         */
        run: function (name, method, param) {
            let module = name;
            if (!Array.isArray(name)) {
                module = [name];
            }
            return new Promise((resolve, reject) => {
                require(module, function (factory) {
                    var result, flag;
                    if (typeof factory === "function") {
                        // console.log('factory() run');
                        result = factory(param, method);
                    } else if (typeof factory === "object") {
                        if (factory.hasOwnProperty("init")) {
                            // console.log('factory.init(param)');
                            flag = factory.init(param, method);
                        }
                        if (factory.hasOwnProperty(method)) {
                            // console.log('factory.method(param)');
                            result = factory[method](param);
                        } else {
                            if (factory.hasOwnProperty("run")) {
                                // console.log('factory.run(param)');
                                result = factory.run(param, method);
                            } else {
                                if (!flag) {
                                    console.log("%c %s", "color: yellow; background-color: black;", module + "[not find method: " + method + "] ")
                                    // console.error(module + " not find method [" + method + "]");
                                }
                            }
                        }
                    } else {
                        console.log(module + " no define function Or object ");
                    }
                    resolve({ result, param });
                }, function (e) {
                    console.log(e);
                })
            })
        },
        /**
         * APP开始方法
         * @param {object} $APP 
         * @returns 
         */
        start: function ($APP) {
            if ($APP.jspath) {
                console.log($APP.jspath + " start");
                return this.run($APP.jspath, $APP["do"], {});
            } else {
                console.log("$APP.jspath no define");
                console.log($APP.jspaths + " all no exist");
                return new Promise((resolve, reject) => {
                    resolve();
                });
            }
        },
        ui: {
            addcss: function ($name) {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.type = "text/css";
                link.href = `${$name}.css`; // 要加载的CSS文件路径
                document.head.appendChild(link);
            },
            openurl: function (url) {
                // alert(url);
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                // 触发点击
                link.click();
            },
            iframe: function (url) {
                // alert(url);
                const link = document.createElement('iframe');
                link.src = url;
            },
            timeout: function (func, time) {
                window.setTimeout(func, time);
            },
            tips: function (el, msg, param) {
                if ($.isNumeric(param)) {
                    param = { tips: param };
                }
                Layer.tips(msg, el, param);
            },
            success: function (msg, callback, time) {
                return this.alert(msg, callback,
                    "success", "circle-check",
                    { time: time || 3000 }
                );
            },
            error: function (msg, callback, time) {
                return this.alert(msg, callback,
                    "danger", "times-circle",
                    { area: ["50%", 'auto'], time: time || 100000 }
                );
            },
            close: function (index) {
                if (index) {
                    Layer.close(index);
                } else {
                    Layer.closeAll();
                }
            },
            warning: function (msg, callback) {
                return this.alert(msg, callback,
                    "warning", "exclamation-circle",
                    { area: ["50%", 'auto'] }
                );
            },
            alert: function (msg, callback, label, icon, options) {
                msg = iCMS.utils.lang(msg);
                var tmpl = '<div class="alert alert-{{label}}"><i class="fa fa-fw fa-{{icon}}"></i>{{msg}}</div>'
                var content = iCMS.utils.display(tmpl,
                    {
                        label: label || 'primary',
                        icon: icon || 'info-circle', msg
                    }
                );
                var options = $.extend({}, options, {
                    // id: 'ui-alert-content',
                    skin: 'ui-alert-skin',
                    zIndex: Layer.zIndex,
                    success: function (dom, index) {
                        Layer.setTop(dom);
                    }
                });
                Layer.config(options);
                content = content.replace("\n", '<br />');
                return Layer.alert(content, function (index) {
                    if (isFunction(callback)) {
                        return callback(index);
                    } else {
                        Layer.close(index);
                    }
                });
                // return iCMS.ui.open(msg, callback, options, true);
            },
            options: function (el, options, name) {
                if (isObject(el) && isUndef(options)) {
                    options = el;
                    var url = options.url;
                } else {
                    var params = $(el).getDatas(name) || {};
                    var url = options.url || $(el).data('url') || $(el).attr('href');
                    var title = options.title || $(el).attr('title');
                    var options = $.extend({}, options, params);
                }

                var sss = name + '=true';

                var herf = window.location.href;
                var height = options.height || $(el).data('height') || 450;
                var width = options.width || $(el).data('width');
                if (name == 'dialog') {
                    width = width || 'auto';
                } else {
                    width = width || '61.8%';
                    if (height == "auto") {
                        height = $(window).height() - 50;
                    }
                }
                // console.log([width, height, options]);

                if (herf.has(sss)) {
                    height = $(window).height() - 100;
                }

                if (typeof url === "string" && !url.has(sss)) {
                    url += url.has("?") ? "&" : "?";
                    url += sss;
                }
                // if (options.start) {
                //     options.success = options.start;
                //     delete options['start'];
                // }
                var content = url;
                var type = 2; //iframe层
                if (options.content) {
                    content = options.content;
                    delete options['content'];
                    type = 1; //页面层
                }

                if (typeof content === "string") {
                    var a = content.substring(0, 1);
                    if (a == "." || a == "#") {
                        content = $(content);
                        type = 1; //页面层
                    }
                } else {
                    if (!(content instanceof jQuery)) {
                        content = $(content);
                    }
                    type = 1; //页面层
                }
                if (type == 1 && width == 'auto') {
                    if (content instanceof jQuery) {
                        width = content.width();
                    }
                }

                if (typeof (height) == "number") {
                    height += "px";
                }
                if (typeof (width) == "number") {
                    width += "px";
                }

                title && (title = iCMS.utils.lang(title));
                var defaults = {
                    title, type, content,
                    area: [width, height],
                    zIndex: Layer.zIndex,
                    success: function (dom, index, that) {
                        dom.find(".sp_input").each(function (index, spel) {
                            $(spel).selectPageClear();
                        });
                        Layer.setTop(dom);
                        $("body").addClass(`ui-${name}-wrap`);
                        if (type == 2) {
                            // var $body = dom.find('iframe').contents().find('body'); //得到iframe页的窗口对象，执行iframe页的方法：iframeWin.method();
                            // var lastHeight = $body.height(); // 初始化记录当前高度
                            // var intervalId; // 用于存储定时器 ID
                            // var stopTime = 5 * 1000; // 5秒后停止轮询（单位：毫秒）
                            // Layer.iframeAuto(index);
                            // that.offset();

                            // // 定义轮询函数
                            // function startPolling() {
                            //     intervalId = setInterval(function () {
                            //         var currentHeight = $body.height();
                            //         console.log({currentHeight,lastHeight});

                            //         if (currentHeight !== lastHeight) {
                            //             console.log("body 高度发生变化");
                            //             console.log("新高度:", currentHeight);
                            //             lastHeight = currentHeight; // 更新高度记录
                            //             Layer.iframeAuto(index);
                            //             that.offset();
                            //         }
                            //     }, 500); // 每 500 毫秒检查一次
                            // }

                            // // 启动轮询
                            // startPolling();

                            // // 设置 5 秒后停止轮询
                            // setTimeout(function () {
                            //     clearInterval(intervalId); // 停止定时器
                            //     console.log("轮询已停止");
                            // }, stopTime);

                            // setInterval(function () {
                            // Layer.iframeAuto(index);
                            // that.offset();

                            // }, 500);
                        }
                    },
                };

                return $.extend({}, defaults, options);
            },
            modal: function (el, options) {
                var options = this.options(el, options, 'modal');
                var defaults = {
                    end: function () {
                        window.ui_modal_idx = 0;
                        $("body").removeClass('ui-modal-wrap');
                    },
                };
                var options = $.extend(defaults, options);

                window.ui_modal_idx = Layer.open(options);
                return window.ui_modal_idx;
            },
            dialog: function (el, options) {
                var options = this.options(el, options, 'dialog');
                var row = options.row || {};
                var yes, cancel;
                if (options.yes) {
                    yes = options.yes;
                    delete options['yes'];
                }
                if (options.ok) {
                    yes = options.ok;
                    delete options['ok'];
                }
                if (options.cancel) {
                    cancel = options.cancel;
                    delete options['cancel'];
                }
                var btn = [iCMS.utils.lang("OK"), iCMS.utils.lang("Cancel")];
                if (!isUndef(options.yesText)) {
                    btn[0] = iCMS.utils.lang(options.yesText);
                }
                if (!isUndef(options.cancelText)) {
                    btn[1] = iCMS.utils.lang(options.cancelText);
                }
                var defaults = {
                    btn, //按钮
                    // id: 'ui-dialog-content',
                    skin: 'ui-dialog-skin',
                    shadeClose: true,
                    only: false, //唯一
                    yes: function (index, dom) {
                        var $body = Layer.getChildFrame('body', index);
                        if (options.type == 2) {
                            var iframeWin = window[dom.find('iframe')[0]['name']]; //得到iframe页的窗口对象，执行iframe页的方法：iframeWin.method();
                            // console.log(iframeWin);
                        }
                        if (typeof yes === "function") {
                            var flag = yes(index, dom, row);
                            if (isUndef(flag)) {
                                Layer.close(index);
                            } else {
                                return flag;
                            }
                        } else {
                            Layer.close(index);
                        }
                    },
                    end: function () {
                        window.ui_dialog_idx = 0;
                        $("body").removeClass('ui-dialog-wrap');
                    },
                    cancel: function (index, dom) {
                        if (typeof cancel === "function") {
                            return cancel();
                        }
                        return true;
                    }
                };
                var options = $.extend(defaults, options);
                window.ui_dialog_idx = Layer.open(options);
                return window.ui_dialog_idx;
            },
            reCaptcha: function ($el) {
                $el = $el || $(".captcha-img");
                if (!($el instanceof jQuery)) {
                    $el = $($el)
                }
                var src = $el.attr("src");
                if (src) {
                    src = src.replace(/[\?|&]t=.+/g, "");
                    src += src.includes("?") ? "&" : "?";
                    $el.attr("src", src + "t=" + Math.random());
                    var $wrap = $el.closest(".input-group");
                    $('[name="captcha"]', $wrap).val("");
                }
            },
            dialogTpl: function (paths, options) {
                options = options || {};
                var params = options.params || {};
                delete options.params;

                iCMS.api.spost('public/template', { paths, params },
                    function ({ data }) {
                        options = $.extend({}, {
                            type: 1,
                            area: '320px',
                            content: data.html,
                            skin: 'cms-dialog',
                            resize: false,
                            fixed: true,
                        }, options);

                        window.dialogTplId = Layer.open(options);
                    }
                );
            }
        },
        request: {
            loopId: 0,
            loop: function (json, resolve, reject) {
                var btn = [];
                var func = {};
                var autoFunc, autoTime;

                function nextFunc(url, params, resolve, reject) {
                    iCMS.request.post(url, params || {}).then(resolve, reject);
                }

                function str_replace(url, str) {
                    return url.replace(new RegExp('[?&]' + str + '(&|$)'), function (match, p1) {
                        return p1 === '&' ? '&' : '';
                    }).replace(/[?&]$/, '');
                }
                function btnFunc(value) {
                    // 定义常量
                    const FRAME_MAIN = 'frame=main';

                    let url = value.url;
                    let is_iframe = (window.parent !== window) //是否在框架里

                    // 判断是否为后台链接
                    let is_admin_link = url.has($APP.SCRIPT_NAME);

                    // 处理frame=main参数的逻辑
                    if (is_admin_link) {
                        // 是后台链接的情况下
                        if (value.target == '_blank' || value.target == '_parent' || value.target == '_top') {
                            // _blank，_parent，_top 如果url没有frame=main要加上
                            if (!url.has(FRAME_MAIN)) {
                                // 判断URL是否已有参数
                                let separator = url.has('?') ? '&' : '?';
                                value.url = url + separator + FRAME_MAIN;
                            }
                        } else {
                            // 其它情况（默认target）
                            if (is_iframe) {
                                // 在框架里且是后台链接，不要加frame=main，要去掉
                                value.url = str_replace(value.url, FRAME_MAIN);
                            } else {
                                // 不在框架里，去掉frame=main
                                // value.url = str_replace(value.url, FRAME_MAIN);
                                if (!url.has(FRAME_MAIN)) {
                                    // 判断URL是否已有参数
                                    let separator = url.has('?') ? '&' : '?';
                                    value.url = url + separator + FRAME_MAIN;
                                }
                            }
                        }
                    } else {
                        // 不是后台链接，不要加frame=main，如果有要去掉
                        value.url = str_replace(value.url, FRAME_MAIN);
                    }

                    if (value.target == '_blank') {
                        window.open(value.url, value.target);
                    } else if (value.target == '_parent') {
                        parent.window.location.href = value.url;
                    } else if (value.target == '_top') {
                        top.window.location.href = value.url;
                    } else {
                        // console.log(value.url);
                        if (value.url == '-1') {
                            window.history.back();
                        } else {
                            window.location.href = value.url;
                        }
                    }
                }
                $.each(json.data.btn, function (idx, value) {
                    btn.push(value['text']);
                    var btnName = 'btn' + (idx + 1);
                    var url = value['url'];

                    if (idx == 0) btnName = 'yes';
                    if (value.next) {
                        autoTime = value.time;
                        autoFunc = function () {
                            nextFunc(url, value['params'], resolve, reject)
                        }
                    }
                    func[btnName] = function (index, dom) {
                        if (url) {
                            if (value.next) {
                                nextFunc(url, value['params'], resolve, reject)
                            } else {
                                if(value['params']){
                                    nextFunc(url, value['params'], resolve, reject)
                                }else{
                                    btnFunc(value);
                                }
                            }
                        }
                        // iCMS.notify.info(value['text']);
                        return false;
                    }
                });

                if (autoTime && isFunction(autoFunc)) {
                    window.setTimeout(autoFunc, autoTime);
                }
                var opts = $.extend({
                    height: 'auto',
                    id: 'ui-dialog-loop',
                    content: json.message,
                    end: function () {
                        iCMS.request.loopId = 0;
                        if (json.data.onClose) {
                            btnFunc(json.data.onClose);
                        }
                    }
                }, func, { btn });

                if (iCMS.request.loopId) {
                    // Layer.closeAll();
                    Layer.content(json.message, iCMS.request.loopId);
                    console.log({Layer,opts});
                    /**
                     * 重新绑定 layer 弹窗的按钮回调（使用新的配置对象）
                     * @param {number} layerId   弹窗索引（iCMS.request.loopId）
                     * @param {object} newConfig 新的配置对象，包含 yes, btn1, btn2 等回调函数
                     */
                    function rebindLayerBtns(layerId, newConfig) {
                        var layero = $('#layui-layer' + layerId);
                        if (!layero.length) return;

                        // 获取所有按钮
                        var btns = layero.find('.layui-layer-btn').children('a');
                        if (!btns.length) return;

                        // 解绑原有事件
                        btns.off('click');

                        // 按原始逻辑重新绑定（与 layer 源码中的处理一致）
                        btns.on('click', function() {
                            var index = $(this).index();  // 按钮索引，0 为第一个按钮
                            if (index === 0) {
                                // 第一个按钮的处理
                                if (newConfig.yes) {
                                    newConfig.yes(layerId, layero);
                                } else if (newConfig.btn1) {
                                    newConfig.btn1(layerId, layero);
                                } else {
                                    layer.close(layerId);
                                }
                            } else {
                                // 其他按钮的处理：btn2, btn3 ...
                                var btnKey = 'btn' + (index + 1);
                                if (newConfig[btnKey]) {
                                    var close = newConfig[btnKey](layerId, layero);
                                    if (close !== false) {
                                        layer.close(layerId);
                                    }
                                } else {
                                    // 若未定义回调，默认关闭弹窗
                                    layer.close(layerId);
                                }
                            }
                        });
                    }
                    rebindLayerBtns(iCMS.request.loopId, opts);
                    // console.log({Layer,opts});
                    // iCMS.request.loopId = iCMS.ui.dialog(opts);
                    // Layer.config(opts);
                    // Layer.btn(btn, true, func);
                    return;
                }
                if (json.data.loop == 'end') {
                    Layer.close(iCMS.request.loopId);
                    return;
                }

                iCMS.request.loopId = iCMS.ui.dialog(opts);
                if (json.data.time) {
                    window.setTimeout(function () {
                        Layer.close(iCMS.request.loopId);
                    }, json.data.time);
                }
            },
            success: function (json, resolve, reject, flag) {
                if (!isFunction(resolve)) {
                    resolve = function (j) {
                        iCMS.notify.success(j.message);
                    }
                }
                if (!isFunction(reject)) {
                    reject = function (j) {
                        iCMS.notify.error(j.message);
                    }
                }

                if (isObject(json.data)) {
                    if (json.data.async) {
                        iCMS.request.post(json.data.async, {}).then(function (json) {
                            if (json.code == 1) {
                                iCMS.notify.success(json.message);
                            }
                        });
                    }
                    if (json.data.loop || json.data.btn) {
                        this.loop(json, resolve, reject)
                        return false;
                    }
                }
                if (json.state == 'worker.js') {
                    return iCMS.utils.worker(json);
                }

                function jumpto(json) {
                    if (json.url) {
                        var time = 1500;
                        if (isObject(json.data)) {
                            if (json.data.time) time = json.data.time
                        }
                        iCMS.utils.jumpto(json.url, time);
                    }
                }
                //拦截失败提示   
                if (isObject($APP.reject)) {
                    if (isFunction($APP.reject[json.code])) {
                        var reflag = $APP.reject[json.code](json);
                        if (reflag === 'break') {
                            return;
                        }
                    }
                }
                //拦截成功提示  
                if (isObject($APP.resolve)) {
                    if (isFunction($APP.resolve[json.code])) {
                        var reflag = $APP.resolve[json.code](json);
                        if (reflag === 'break') {
                            return;
                        }
                    }
                }
                if (json.code == -9999) { //登录失效
                    if (isArray(json.state)) {
                        let module = json.state[0];
                        let method = json.state[1];
                        require([module], function (factory) {
                            if (factory.hasOwnProperty(method)) {
                                factory[method](json);
                            }
                        }, function () {
                            jumpto(json);
                        });
                        return;
                    }
                } else if (json.code <= -99) { //出极大错
                    iCMS.ui.error(json.message);
                } else if (json.code == -9) { //出大错
                    iCMS.ui.warning(json.message);
                } else if (json.code == -3) { //无权限
                    iCMS.notify.warning(json.message, 100000);
                } else if (json.code == -1) { //出错
                    iCMS.ui.alert(json.message);
                } else if (json.code == 0) { //失败
                    reject(json);
                } else if (json.code >= 1) { //成功
                    resolve(json)
                }
                if (json.code <= 0) { //失败
                    reject(json);
                }
                jumpto(json);
            },
            error: function (response) {
                var message = response.message;
                if (response.status == 200) {
                    iCMS.ui.alert(response.responseText);
                } else {
                    message = message || response.responseText ? iCMS.utils.sprintf(
                        "request error, httpCode=%s,responseText:%s",
                        response.status,
                        response.responseText
                    ) : '';
                    if (message) {
                        iCMS.ui.error(message);
                    }
                }
            },
            send: function (url, param, type, flag) {
                var self = this;
                return new Promise(function (resolve, reject) {
                    if (isUndef(param['CSRF_TOKEN'])) {
                        param['CSRF_TOKEN'] = $APP.CSRF_TOKEN;
                    }
                    flag = flag || false;
                    $.ajax({
                        type: type || "GET",
                        url: url,
                        data: param,
                        async: true, //默认为true,即异步请求；false为同步请求
                        success: function (response) {
                            var index = iCMS.api.ajax_loading || 0;
                            if (index) {
                                Layer.close(index);
                            }
                            self.success(response, resolve, reject, flag);
                        },
                        error: function (response) {
                            self.error(response);
                        },
                        dataType: "json",
                    });
                });
            },
            get: function (url, param, flag) {
                return iCMS.request.send(url, param, "GET", flag);
            },
            post: function (url, param, flag) {
                return iCMS.request.send(url, param, "POST", flag);
            },
            upload: function (url, param) {
                var self = this;
                return new Promise(function (resolve, reject) {
                    var data = new FormData();
                    $.each(param, function (key, value) {
                        data.append(key, value);
                    });
                    if (isUndef(data['CSRF_TOKEN'])) {
                        data['CSRF_TOKEN'] = $APP.CSRF_TOKEN;
                    }
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: data,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                        success: function (response) {
                            self.success(response, resolve, reject);
                        },
                        error: function (response) {
                            self.error(response);
                        },
                    });
                });
            }
        },
        notify: {
            close: function (option) {
                $.notifyClose();
            },
            run: function (option) {
                require(["notify"], function (factory) {
                    option.z_index = 99999999;
                    option.delay = 3000;
                    option.icon = "fa fa-" + option.icon + " mr-1";
                    if (isArray(option.message)) {
                        option.message = iCMS.utils.lang.apply(iCMS.utils.lang, option.message);
                    } else {
                        option.message = iCMS.utils.lang(option.message);
                    }
                    return factory.notify(option);
                });
            },
            info: function (msg) {
                return iCMS.notify.run({
                    type: "info",
                    icon: "info-circle",
                    message: msg,
                });
            },
            success: function (msg) {
                return iCMS.notify.run({
                    type: "success",
                    icon: "check",
                    message: msg,
                });
            },
            error: function (msg, time) {
                // console.error(msg);
                return iCMS.notify.run({
                    type: "danger",
                    icon: "times-circle",
                    message: msg,
                    timer: time || 10000,
                });
            },
            alert: function (msg, time) {
                return iCMS.notify.run({
                    type: "primary",
                    icon: "info-circle",
                    message: msg,
                    timer: time || 3000,
                });
            },
            warning: function (msg, time) {
                return iCMS.notify.run({
                    type: "warning",
                    icon: "exclamation-circle",
                    message: msg,
                    timer: time || 5000,
                });
            }
        },
        utils: {
            moment: Moment,
            getJson: function (id) {
                const dataElement = document.getElementById(id);
                if (!dataElement) {
                    return [];
                }
                if (!dataElement.textContent) {
                    return [];
                }
                return JSON.parse(dataElement.textContent.trim()) || [];
            },
            source: function (source, field, placeholder) {
                var url, data;
                if (typeof source === 'object') {
                    data = source;
                } else if (typeof source === 'string') {
                    if (source.has("./") || source[0] == '/') {
                        url = iCMS.url(source, false);
                    } else if (source[0] == '#' || source[0] == '.') {
                        data = JSON.parse($(source).val()) || [];
                    } else if (!source.isUrl() && (source[0] == '{' || source[0] == '[')) {
                        data = JSON.parse(source) || [];
                    } else if ($APP.context && typeof ($APP.context[source]) === "object") {
                        var collection = $APP.context[source];
                        data = [];
                        data.push({ id: '', name: placeholder, text: placeholder });
                        var _field = field;
                        if (_field.has("rs[")) {
                            _field = _field.ltrim("rs[").rtrim(']');
                        }
                        $.each(collection, function (id, name) {
                            name = iCMS.utils.lang(name);
                            var text = iCMS.utils.display("{{name}} [{{_field}}={{id}}]", { id, name, _field })
                            data.push({ id, name, text });
                        });
                    } else {
                        // data = iCMS.url(source);
                    }
                }
                return { url, data }
            },
            cookie: function (el, data) {
                return require('cookie');
            },
            worker: function (json) {
                let url = json.url
                let pageTotal = json.data.pageTotal || 0
                let param = {};
                // 当前处理的 URL 索引
                let api = iCMS.url(url, false);
                // 创建 Web Worker
                const worker = new Worker('/assets/js/app/libs/worker.js');
                // 向 Worker 发送
                postMessage(json.data);
                function postMessage(pd) {
                    param = $.extend({}, param, pd || {});
                    param['page'] = pd.page ? pd.page : 1;
                    param['pageTotal'] = pd.pageTotal || 0;
                    param['totalCount'] = pd.totalCount || 0;
                    param['worker'] = true;
                    param['CSRF_TOKEN'] = $APP.CSRF_TOKEN;
                    worker.postMessage({ api, 'data': param });
                }
                // 监听 Worker 返回的消息
                worker.addEventListener('message', (event) => {
                    const { state, data, error, message } = event.data;
                    pageTotal = data.pageTotal;
                    messages = data.messages || [];
                    // console.log(event.data);
                    if (messages.length) {
                        messages.forEach(item => {
                            iCMS.notify.run({
                                type: "info",
                                icon: "info-circle",
                                message: item,
                                from: 'top',
                                align: 'center',
                            })
                            //    iCMS.notify.info(item);
                        })
                    }
                    if (error) {
                        iCMS.ui.alert(error);
                        return;
                    } else {
                        let msg = data.text || message;
                        if (state == 'SUCCESS') {
                            iCMS.notify.success(msg);
                        } else if (state == 'ERROR') {
                            iCMS.notify.error(msg);
                        } else {
                            iCMS.notify.info(msg);
                        }
                    }
                    data.page++;
                    // 处理下一个
                    if (data.page <= pageTotal) {
                        postMessage(data);
                        // 发送下一个
                    } else {
                        iCMS.notify.success('所有任务完成')
                        worker.terminate(); // 结束 Worker
                    }
                });

                // 监听 Worker 的错误
                worker.addEventListener('error', (error) => {
                    console.error('Worker error:', error);
                });
            },
            concatPaths: function (basePath, newPath) {
                basePath = basePath.replace(/\/+$/, '');
                newPath = newPath.replace(/^\.\/+/, '');
                const pieces = newPath.split('/');
                if (pieces[0] === basePath.split('/').pop()) {
                    pieces.shift();
                    newPath = pieces.join('/');
                }

                basePath += '/' + newPath;
                return basePath;
            },
            isUpperCase(str) {
                return str.charAt(0) === str.charAt(0).toUpperCase() && str.charAt(0) !== str.charAt(0).toLowerCase();
            },
            lang: function () {
                var [key, ...params] = arguments; // 解构参数
                if (!key) return ''; // 如果没有提供键值，返回空字符串

                const regex = /^(?=[\w:-]*$)(?=.*[A-Za-z])([\w-]+:)*[\w-]+$/;
                if (!regex.test(key) && key.indexOf(' ') === -1) {
                    return key;
                }

                let is_common = false;
                LangData = LangData || {}
                if (key.indexOf(':') === -1) {
                    if (key[0] && key[0] === key[0].toUpperCase() && typeof LangData[key] !== 'undefined') {
                        return LangData[key];
                    } else {
                        key = 'common:' + key;
                        is_common = true;
                    }
                }

                let value = LangData;
                // 按层级获取键值
                const keys = key.split(':');
                for (const k of keys) {
                    value = value[k];
                    if (value === undefined) {
                        value = key;
                    } // 找不到键值时返回原始键名
                }
                // 替换占位符（%s）
                if (params.length > 0 && typeof value === 'string') {
                    if (value.includes('%s')) {
                        value = value.replace(/%s/g, () => {
                            const param = params.shift(); // 获取下一个占位参数
                            return typeof param === 'string' ? iCMS.utils.lang(param) : param; // 递归解析语言包中的参数
                        });
                    } else {
                        value += params.join(' ');
                    }
                }
                if (value.includes('%s')) {
                    value = value.replace(/%s/g, '');
                }

                if (is_common && value.includes('common:')) {
                    value = value.replace(/common:/g, '');
                }
                return value;
            },
            jumpto: function (url, time) {
                if (isUndef(url)) return;
                // var index = Layer.load(1);
                window.setTimeout(function () {
                    // Layer.close(index);
                    let _location = window.location;
                    if (window.parent !== window && window.name.includes('layui-layer-iframe')) {
                        _location = parent.window.location;
                    }
                    if (url.isUrl() || url.has("/")) {
                        _location.href = url;
                    } else if (url == "1" || url == "reload") {
                        _location.reload();
                    } else if (url == "-1" || url == "back") {
                        window.history.back();
                    }
                }, time || 0);
            },
            random: function (len, ischar) {
                len = len || 16;
                var chars = "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ";
                if (ischar) {
                    var chars = "abcdefhjmnpqrstuvwxyz";
                }
                var code = "";
                for (var i = 0; i < len; i++) {
                    code += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return code;
            },
            randomNum: function (minNum, maxNum) {
                switch (arguments.length) {
                    case 1:
                        return parseInt(Math.random() * minNum + 1, 10);
                        break;
                    case 2:
                        return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10);
                        break;
                    default:
                        return 0;
                        break;
                }
            },
            template: function (option, data) {
                var template = require('art-template');
                template.defaults.openTag = "<%";
                template.defaults.closeTag = "%>";
                if (typeof option === 'string' && isDef(data)) {
                    return template(option, data);
                }
                if (isObject(option)) {
                    template.defaults = $.extend(template.defaults, option);
                }
                return template;
            },
            display: function (str, data) {
                var tmpl = 'var __p=[],print=function(){__p.push.apply(__p,arguments);};' +
                    // 'with(obj||{}){' +
                    "__p.push('" + str.replace(/\\/g, '\\\\').replace(/'/g, "\\'")
                        .replace(/<%=([\s\S]+?)%>/g, function (match, code) {
                            return "',obj['" + code.replace(/\\'/g, "'") + "'],'";
                        }).replace(/\{\{([\s\S]+?)\}\}/g, function (match, code) {
                            return "',obj['" + code.replace(/\\'/g, "'") + "'],'";
                        }).replace(/<%([\s\S]+?)%>/g, function (match, code) {
                            return "');" + code.replace(/\\'/g, "'").replace(/[\r\n\t]/g, ' ') + "__p.push('";
                        }).replace(/\r/g, '\\r').replace(/\n/g, '\\n').replace(/\t/g, '\\t') +
                    "');" +
                    // '}' +
                    "return __p.join('');";
                // console.log(tmpl);
                var func = new Function('obj', tmpl);
                return data ? func(data) : func;
            },
            sprintf: function sprintf(_str) {
                if (isEmpty(_str)) {
                    return '';
                }
                for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
                    args[_key - 1] = arguments[_key];
                }

                var i = 0;

                var str = _str.replace(/%[sd]/g, function () {
                    var arg = args[i++];

                    if (isUndef(arg)) {
                        arg = 'undefined';
                    }

                    return arg;
                });

                return str;
            },
            resUrl: function (url) {
                if (!url.isUrl()) {
                    url = $APP.file.url + url
                }
                return url;
            },
            resPath: function (url) {
                return url.replace($APP.file.url, '');
            },
            parseQuery: function (str) {
                var qsList = str.split("&")
                var obj = {};
                qsList.forEach(function (item) {
                    if (item) {
                        var a = item.split('=');
                        obj[a[0]] = decodeURIComponent(a[1]);
                    }
                });
                return obj;
            },
            getQuery: function (name) {
                var str = window.location.search.substring(1);
                var obj = iCMS.utils.parseQuery(str);
                return name ? obj[name] : obj;
            },
            makeUrl: function (url, arr) {
                if (arr) {
                    var qsArr = [];
                    $.each(arr, function (key, value) {
                        qsArr.push(key + '=' + value)
                    });
                    var qs = qsArr.join("&");
                    url += (url.has("?") ? "&" : "?") + qs;

                }
                return url;
            },
            lcfirst: function (str) {
                return str.substring(0, 1).toLowerCase() + str.substring(1)
            },
            ucfirst: function (str) {
                return str.substring(0, 1).toUpperCase() + str.substring(1)
            }
        },
        catch: function (json) {
            iCMS.notify.alert(json.message);
        },
        get: function (url, data, func) {
            if (!url.isUrl() && url[0] != '/') {
                url = iCMS.url(url);
            }
            $.getJSON(url, data, func);
        },
        post: function (url, data, func) {
            if (url.isUrl() && url[0] != '/') {
                url = iCMS.url(url);
            }
            $.post(url, data, func, "json");
        },
        url: function ($url, $uri, $csrf) {
            if ($url === true) return $APP.SCRIPT_NAME;

            if (typeof $url !== 'string') return $url;

            // console.log($url);

            // ./aa/add
            if ($url.indexOf('./') === 0) {
                $url = this.utils.concatPaths($APP.API_URL, $url);
                // $APP.API_URL + "/" + $url.ltrim('./');
            }
            if (!$url.has($APP.SCRIPT_NAME) && !$url.isUrl()) {
                $url = $APP.SCRIPT_NAME + "/" + $url.ltrim('/');
            }
            // console.log($url);

            if ($uri === false) { // without csrf_token
                return $url;
            }
            if ($uri) $url += "/" + $uri.ltrim('/');
            if ($csrf === false) {
                return $url;
            }
            return iCMS.api.csrf_token($url);
        },
        api: {
            /**
             * 
             * @param {string} $title 标题
             * @param {string}  el 元素
             * @param {object} $validate 表单验证规则
             * @param {function} resolve 成功回调
             * @param {function} dataCallback 数据处理回调
             */
            submit: function ($title, $el, $validate, resolve, dataCallback) {
                require(["validation"], function () {
                    if (typeof $el === "string" && $el.includes(":")) {
                        $el = iCMS.$($el);
                    }
                    if (!$el.length) {
                        return;
                    }
                    var isNotify = $el.data('notify');
                    isUndef(isNotify) && (isNotify = true);

                    $el.validate($validate);
                    $el.submit(function (e) {
                        e.preventDefault();
                        var $form = $(this);
                        if (!$form.valid()) {
                            return false;
                        }
                        $title = iCMS.utils.lang($title);
                        isNotify && iCMS.notify.info(['msg:wait', $title]);
                        var api = $form.attr("action");
                        var data = $form.serializeJSON();
                        if (isFunction(dataCallback)) {
                            data = dataCallback(data);
                        }
                        if (!isFunction(resolve)) {
                            var success;
                            if (isObject(resolve)) {
                                if (resolve.hasOwnProperty("success")) {
                                    success = resolve.success;
                                }
                            }
                            resolve = function (json) {
                                if (json.code) {
                                    if (isFunction(success)) success(json, $form, data);

                                    if (json.message) {
                                        iCMS.notify.success(json.message);
                                    } else {
                                        iCMS.notify.success(['msg:success', $title]);
                                    }
                                    
                                    if (json.param && json.param.hasOwnProperty('forward')) {
                                        if (json.param.forward.isUrl()) {
                                            iCMS.utils.jumpto(json.param.forward, 1000);
                                        }else{
                                            window.location.reload();
                                        }
                                        return;
                                    }
                                    if (json.url) {
                                        // iCMS.utils.jumpto(json.url, 1000);
                                    }
                                }
                            };
                        }
                        iCMS.api.post(api, data, resolve, function (json) {
                            var $ct = $(".captcha-img", $form);
                            iCMS.ui.reCaptcha($ct);
                            iCMS.notify.warning(json.message);
                        });
                    });
                });
            },
            url: function (app, $do) {
                return iCMS.url(app, $do, false);
            },
            csrf_token: function ($url) {
                if (!$url.has("CSRF_TOKEN=")) {
                    $url += $url.has("?") ? "&" : "?";
                    $url += "CSRF_TOKEN=" + $APP.CSRF_TOKEN;
                }
                return $url;
            },

            sget: function (url, param, resolve, reject, flag) {
                return iCMS.api.ajax(url, param, resolve, reject, 'sget', flag);
            },
            spost: function (url, param, resolve, reject, flag) {
                return iCMS.api.ajax(url, param, resolve, reject, 'spost', flag);
            },
            get: function (url, param, resolve, reject, flag) {
                return iCMS.api.ajax(url, param, resolve, reject, 'get', flag);
            },
            post: function (url, param, resolve, reject, flag) {
                return iCMS.api.ajax(url, param, resolve, reject, 'post', flag);
            },
            ajax_loading: 0,
            /**
             * AJAX请求带loading,CSRF_TOKEN
             * @param String url 请求链接
             * @param ObjectFunction param 参数或者success回调函数
             * @param Function success  成功回调函数
             * @param Function error 失败回调函数
             * @param String type 请求类型[get,sget,post,spost]
             */
            ajax: function (url, param, success, error, type, flag) {

                if (typeof param === "function") {
                    error = success;
                    success = param;
                    param = {};
                } else {
                    param = param || {}
                }
                var index = 0;
                if (type === 'get' || type === 'post') {
                    iCMS.api.ajax_loading = index = Layer.load(1);
                }

                var send = ((type === 'get' || type === 'sget') ? iCMS.request.get : iCMS.request.post);

                var url = iCMS.url(url, false);
                param['CSRF_TOKEN'] = $APP.CSRF_TOKEN;

                if (isFunction(param['success'])) success = param['success'];
                if (isFunction(param['error'])) error = param['error'];

                delete param['success'];
                delete param['error'];

                if (!isFunction(success)) {
                    success = function (json) {
                        json.message = json.message || "msg:success";
                        iCMS.notify.success(json.message);
                        iCMS.utils.jumpto(json.url, 3000);
                    }
                }

                if (!isFunction(error)) {
                    error = function (json) {
                        iCMS.notify.warning(json.message);
                    }
                }
                send(url, param, flag).then(function (json) {
                    Layer.close(index);
                    json['param'] = param;
                    if (isObject(json.data)) {
                        iCMS.request.success(json, success);
                    } else {
                        return success.call(this, json);
                    }
                }).catch(function (json) {
                    Layer.close(index);
                    json['param'] = param;
                    return error.call(this, json);
                });
            }
        },
        $: function (i, a) {
            if (i instanceof jQuery) {
                return i;
            }
            a = a || document;
            if (typeof i === "string") {
                if (i.includes(':')) {
                    var s = this.$s(i);
                    if (typeof a === "string" && a.includes(':')) {
                        a = this.$s(a);
                    }
                    return $(s, a);
                }
            }
            return $(i, a);
        },
        /**
         * i属性
         * @param {*} selector 
         * @param {*} filter =,^=,$=
         * @param {*} tag 默认为空
         * @returns 
         */
        $s: function (selector, filter, tag) {
            filter = filter || '=';
            tag = tag || '';
            // return this.utils.sprintf('[i%s"%s"]', selector, filter)
            return tag + '[i' + filter + '"' + selector + '"]';
        },
        init: function (options) {
            this.$CONFIG = $.extend(this.$CONFIG, options);
            Layer.config({
                type: 0,
                title: this.utils.lang("iCMS Tips"),
                skin: 'iCMS-layer',
                shadeClose: true,
                shade: 0.5,
                content: ''
            });
            $(window).keyup(function (e) {
                if (e.keyCode == 27) {
                    if ($(".layui-layer").length > 0) {
                        var index = $(".layui-layer").attr("times");
                        if (index) {
                            Layer.close(index);
                        } else {
                            Layer.closeAll();
                        }
                    }
                }
            });
        },
    }


    window.iCMS = iCMS;
    window.Lang = iCMS.utils.lang;
    window.Layer = Layer;
    window.Moment = Moment;
    iCMS.init();
    return iCMS;
});