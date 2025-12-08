define(["admincp", "vue", "metadata"], function (admincp, Vue, meta) {
    //cms:config:vTemplate
    return function ({ $el, el }) {
        const item = { name: "", ua: "", domain: "", tpl: "", index: "{iTPL}/index.htm" };
        var $wrap = $el;
        new Vue({
            el,
            data: {
                url: 'config/devices',
                eitem: {},
                items: [],
                saveTimer: null
            },
            methods: {
                dialog: function (index) {
                    var that = this;
                    var box = document.getElementById("edit-box");
                    iCMS.ui.dialog({
                        title: '编辑',
                        content: box,
                        yes: function (idx, dom) {
                            let data = $('input', box).serializeJSON();
                            iCMS.api.post(that.url, { i: data['i'], index });
                            that.load();
                        }
                    });
                },
                edit: function (item, index) {
                    this.eitem = item;
                    this.dialog(index);
                },
                add: function (e) {
                    // this.devices.push(item);
                    this.eitem = $.extend({}, item);
                    this.dialog();
                },
                save: function () {
                    iCMS.api.post(this.url, { items: this.items });
                },
                debounceSave: function () {
                    // 可自定义延迟时间的防抖保存方法
                    if (this.saveTimer) {
                        clearTimeout(this.saveTimer);
                    }
                    this.saveTimer = setTimeout(() => {
                        iCMS.api.post(this.url, { items: this.items });
                    }, 500);
                },
                show: function () {
                    $wrap.show().removeClass('hide');
                },
                remove: function (index) {
                    var that = this;
                    this.$delete(this.items, index);
                    iCMS.api.post(this.url, { remove: index }, function (json) {
                        that.load();
                    });
                },
                load: function () {
                    var that = this;
                    iCMS.api.post(this.url, { flag: 1 }, function (json) {
                        that.items = json.data;
                        that.show();
                    });
                    if (isEmpty(this.items)) {
                        this.items = [item];
                    }
                }
            },
            mounted: function () {
                this.load();
                this.show();
            }
        })
    };
});