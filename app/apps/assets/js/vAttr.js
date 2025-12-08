define(["vue"], function (Vue) {
    return function ({ $el, el }) {
        const defitem = { key: "", name: "", value: "" };
        var items = $el.data("items")||[];
        return new Vue({
            el,
            data: {
                items
            },
            methods: {
                remove: function (index) {
                    this.$delete(this.items, index);
                },
                add: function () {
                    var item = $.extend({}, defitem);
                    this.items.push(item);
                }
            },
            mounted: function () {
                this.$el.classList.remove('hide')
            }
        });
    };
});