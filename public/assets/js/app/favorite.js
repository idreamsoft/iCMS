define(["cms"], function (cms) {
    //当前页面参数
    const params = $APP.params || {};
    const factory = {
        $widget: {},
        ltvm: null,
        //cms:favorite:vlist
        vlist: function ({ $el, el }) {
            require(["vue"], function (Vue) {
                var vars = $el.getDatas('vars');
                factory.ltvm = new Vue({
                    el,
                    methods: {
                        load: function () {
                            let that = this;
                            iCMS.api.spost('favorite/vlist', { params, vars }, function (json) {
                                that.favoriteList = json.data.rows;
                            });
                        },
                    },
                    mounted: function () {
                        this.load();
                    },
                    data: {
                        favoriteList: [],
                    },
                })
            })
        },
        //cms:favorite:num
        num: function ({ $el, el }) {
            this.$widget['num'] = $el;
        },
        //cms:favorite:add
        add: function ({ $el, el }) {
            var that = this;
            var fid = $el.data('fid');
            iCMS.api.spost('favorite/add', { fid, params }, function (json) {
                // iCMS.notify.alert(json.message);
                var itemOpt = $el.getDatas('item');
                var item = $el.parents(itemOpt.class);
                var cEl = $(itemOpt.count, item);

                var numObj = that.$widget['num'];
                var num = parseInt(numObj.text()) || 0;

                var count = parseInt(cEl.text());
                $el.removeClass();
                if (json.data.id > 0) {
                    count++
                    num++
                    item.addClass('active');
                    var obj = $el.getDatas('active');
                } else {
                    count--;
                    if (count < 0) count = 0;

                    num--
                    if (num < 0) num = 0;

                    item.removeClass('active');
                    var obj = $el.getDatas('cancel');
                }
                numObj.text(num)
                cEl.text(count)
                $el.addClass(obj.class);
                $el.text(obj.text);
            });
        },
        //cms:favorite:create:
        create: function ({ $el, el }) {
            var that = this;
            return iCMS.api.submit("创建", $el, {
                rules: {
                    title: {
                        required: true,
                        maxlength: 20,
                    },
                    description: {
                        maxlength: 256,
                    },
                },
                messages: {
                    title: {
                        required: "请输入标题",
                        maxlength: "最多输入20字"
                    },
                    description: {
                        maxlength: "最多输入256字"
                    }
                }
            }, function () {
                iCMS.notify.success("创建成功！");
                that.ltvm.load();
                Layer.close(that.$widget['dialog:create:index']);
            });
        },
        //cms:favorite:dialog:create:
        dialog_create: function ({ $el, el }) {
            var that = this;
            iCMS.ui.dialogTpl(['favorite', 'dialog.create'], {
                title: Lang('Create Favorite'),
                success: function (layero, index) {
                    that.$widget['dialog:create:index'] = index;
                    $(layero).on('click', '[name="cancel"]', function () {
                        Layer.close(index)
                    });
                }
            });
        },
        //cms:favorite:dialog
        dialog: function ({ $el, el }) {
            //当前页面参数
            this.$widget['dialog'] = $el;
            iCMS.ui.dialogTpl(['favorite', 'dialog.list', true], {
                params,
                area: '360px',
                title: Lang('Add Favorite'),
                success: function (dom, index, that) {
                    // layer.resize(index);
                },
            });
        }
    }
    return factory;
});