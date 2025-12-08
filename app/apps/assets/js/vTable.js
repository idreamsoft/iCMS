// define(["admincp", "vue", "former", "./menuTree.js"], function (admincp, Vue, former, menuTree) {
define(["vue",], function (Vue) {
    return function ({ $el, el }) {

        new Vue({
            el,
            data: {
                vtype: 0,
                tables: {},
            },
            methods: {
                add: function (e) {
                    this.tables.push({
                        label: "",
                        name: "",
                        primary: "",
                        table: "",
                        union: "",
                    });
                },
                remove: function (index) {
                    this.tables.splice(index, 1);
                }
            },
            mounted: function () {
                let me = this;
                this.vtype = $('#vtype').val();
                $('#vtype').on('change', function () {
                    me.vtype = this.value;
                    $('#apptype').val(this.value == 3 ? 3 : 4);
                });
                var vtext = $("#vTable").text().trim();
                if (vtext) {
                    this.tables = JSON.parse(vtext)||[];
                }
            },
            updated: function () {
            }
        });
    }
})