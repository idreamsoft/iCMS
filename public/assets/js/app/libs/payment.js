define(["cms", "jq-timer", "qrcode"], function (cms) {
    const factory = {
        cache: {},
        notifyer: {},
        notify: function (options) {
            let me = this;
            let defaults = {
                url: "payment/result",
                param: {},
                time: 1000,
                success: function (json, s) {
                    iCMS.notify.success("感谢您的支持!");
                },
                wait: function (json, s) {
                },
                fail: function (json, s) {
                    iCMS.notify.error(json.message);
                },
                error: function (json) {
                    iCMS.notify.error(json.message);
                }
            }
            options = $.extend(defaults, options);
            this.send = function () {
                iCMS.request
                    .post(iCMS.url(options.url, false), options.param)
                    .then(function (json) {
                        if (json.code == "1") {
                            options.success(json, me);
                        } else if (json.code == "2") {
                            options.wait(json, me);
                            //等待支付
                            if (!me.clear) {
                                me.timer.play();
                            }
                        } else {
                            options.fail(json, me);
                        }
                    })
                    .catch(function (json) {
                        options.error(json);
                    });
            }
            if (this.timer) {
                this.timer.clearTimer();
            }
            this.clear = false;
            this.timer = $.timer(function () {
                me.timer.stop();
                me.send();
            });
            this.timer.set({ time: options.time, autostart: true });
            this.start = function () {
                me.clear = false;
                me.timer.play();
            };
            this.stop = function () {
                me.clear = true;
                me.timer.stop();
            };
            this.clearTimer = function () {
                me.stop();
                me.timer.clearTimer();
            };
            return this;
        },
        //支付弹窗
        dialog: function ($el, tpl, title) {
            var me = this;
            const debounced = debounce(function (event) {
                event.preventDefault();
                var self = $(this);
                var params = self.serializeJSON();
                title = title || self.data('title') || self.attr('title') || params['subject'] || Lang('Payment');
                iCMS.ui.dialogTpl(['payment', tpl], {
                    title, params,
                    success: function (layero, index) {
                        me.dialog_index = index;
                        $(layero).on('click', '[name="cancel"]', function () {
                            Layer.close(index)
                        }).on('click', '.payment-charge', function () {
                            Layer.close(index)
                        });
                    }
                });
                return false;
            });
            $el.off("submit").on("submit", function (event) {
                event.preventDefault();
                return debounced.call(this, event);
            });
        },
        //支付表单提交
        submit: function ($el, SUCCESS, FAIL) {
            const debounced = debounce(function (event) {
                event.preventDefault();
                let self = $(this);
                let data = self.serializeJSON();
                iCMS.api.spost('payment', { action: data.action, data },
                    function (json) {
                        if (typeof SUCCESS === 'function') SUCCESS(json);
                    },
                    function (error) {
                        if (typeof FAIL === 'function') {
                            FAIL(error);
                        } else {
                            iCMS.notify.error(error.message);
                        }
                    }
                );
                return false;
            });
            $el.off("submit").on("submit", function (event) {
                event.preventDefault();
                return debounced.call(this, event);
            });
        },
        /**
         * [qrcode description]
         *
         * @param   {[type]}  params   其它参数
         * @param   {[type]}  payret   支付返回数据
         * @param   {[type]}  SUCCESS  [SUCCESS description]
         * @param   {[type]}  WAIT     [WAIT description]
         * @param   {[type]}  FAIL     [FAIL description]
         *
         * @return  {[type]}           [return description]
         */
        qrcode: function (params, payret, SUCCESS, WAIT, FAIL) {
            iCMS.ui.dialogTpl(['payment', 'dialog.qrcode'], {
                params,
                time: 0,
                title: payret.data.title || '扫码支付',
                btn: false,
                success: function (layero, index) {
                    Layer.setTop(layero);
                    var QREL = $("#payQrcode", layero).empty();
                    var qrcode = new QRCode(QREL[0], { width: 150, height: 150 });
                    qrcode.clear();
                    qrcode.makeCode(payret.data.result);
                    factory.notifyer = factory.notify({
                        param: {
                            trade_id: payret.data.trade_id,
                            product_id: payret.data.product_id,
                        },
                        success: function (json, s) {
                            Layer.close(index);
                            iCMS.notify.success("支付成功！");
                            if (typeof SUCCESS === "function") {
                                // console.log({SUCCESS,payret,json, s});
                                SUCCESS(json, s);
                            }
                        },
                        wait: function (json, s) {
                        },
                        fail: function (json, s) {
                            Layer.close(index);
                            iCMS.notify.error(json.message);
                        },
                        error: function (json) {
                            Layer.close(index);
                            iCMS.notify.error(json.message);
                        }
                    });
                },
                end: function (a) {
                    factory.notifyer.clearTimer();
                    console.log(a);
                },
            });
        },
        cashierUrl: function (amount, url) {
            iCMS.$("payment:cashier:amount").text(amount);
            // QRel.attr("src", "<!--{$site.urls.public}-->/img/loading.gif");
            // var expire = "<!--{$payment_config.expire}-->" || 600;

            require(["qrcode"], function (QRCode) {
                var QRel = iCMS.$("payment:cashier:qrcode").empty();
                var qrcode = new QRCode(QRel[0], { width: 150, height: 150 });
                qrcode.clear();
                qrcode.makeCode(url);
                iCMS.$("app:payment:cashier:url").attr("href", url);
            });

        },
        getCashierUrl: function (amount, productId) {
            iCMS.$("payment:cashier:amount").text(amount);
            // QRel.attr("src", "<!--{$site.urls.public}-->/img/loading.gif");
            // var expire = "<!--{$payment_config.expire}-->" || 600;

            iCMS.api.spost('payment/getCashierUrl', { amount, productId },
                function ({ data }) {
                    var expire = data.expire || 600;
                    require(["qrcode"], function (QRCode) {
                        var QRel = iCMS.$("payment:cashier:qrcode").empty();
                        var qrcode = new QRCode(QRel[0], { width: 150, height: 150 });
                        qrcode.clear();
                        qrcode.makeCode(data.cashierUrl);
                        iCMS.$("app:payment:cashier:url").attr("href", data.cashierUrl);
                    });
                }, function (error) {
                    iCMS.notify.error(error.message);
                });
        }
    }
    return factory;
});