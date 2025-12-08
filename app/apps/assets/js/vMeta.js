define(["vue"], function (Vue) {
    return function ({ $el, el }) {
        const kvitem = { key: "", name: "", value: "" };
        let appid = $("[name='metadata_appid']", $el).val();
        let id = $("[name='metadata_id']", $el).val();
        return new Vue({
            el,
            data: {
                formCallFunc: 'saveMetaDataKVs',
                name: 'metadata',
                items: []
            },
            methods: {
                remove: function (index) {
                    this.$delete(this.items, index);
                },
                load: function () {
                    var self = this;
                    iCMS.api.post('appsMeta/getKv', { appid, id },
                        function (ret) {
                            self.items = ret.data
                            self.$el.classList.remove('hide');
                        }
                    )
                },
                save: function () {
                    var data = $("[name^='" + this.name + "']", this.$el).serializeJSON();
                    iCMS.api.post('appsMeta/saveKv', {
                        appid, id,
                        metadata: data[this.name]
                    }, function (ret) {
                        iCMS.notify.success(ret.message);
                        // self.load();
                    });
                },
                add: function () {
                    var item = $.extend({}, kvitem);
                    this.items.push(item);
                }
            },
            mounted: function () {
                window[this.formCallFunc] = this.save;
                this.load();
            }
        });
    };
});