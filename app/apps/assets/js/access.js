define(['ui', 'vue', 'treeview'], function (UI, Vue) {
    window['accessAppData'] = function (data) {
        if (isEmpty(data['access']) || isEmpty(data['access']['app'])) {
            data['access'] = { 'app': [] };
        }
        // data['access']['app'] = data['access']['app'].concat(factory.vm1.accessAll||[])
        data['access']['app'] = factory.vm1.accessAll || [];
    }

    const factory = {
        vm1: {},
        AccessAppVue: function () {
            Vue.component('tree-node', {
                props: ['node'],
                template: `
                  <li :class="{
                    'cursor-pointer p-1 hover:text-blue-300': true,
                  }">
                    <span class="badge bg-gray-dark">{{ node.caption }}</span>
                    <ul class="overflow-y-scroll" style="height: 500px;" v-if="node.children && node.children.length">
                      <tree-node v-for="child in node.children" :key="child.id" :node="child"></tree-node>
                    </ul>
                  </li>`
            });
            return new Vue({
                el: '#AccessAppVue',
                data: {
                    s1: '',
                    s2: '',
                    s3: '',
                    m1: '',
                    sall3: false,
                    accessAll: [],
                    menus: [],
                    children: [],
                    children2: [],
                    applications: [],
                    classs: [],
                    permissions: []
                },
                methods: {
                    is_empty(items) {
                        return (Array.isArray(items) && items.length > 0) || (typeof items === 'object' && Object.keys(items).length > 0)
                    },
                    toggleAll(items) {
                        const urls = items.map(item => item.url); // 获取当前 permissions 的 URL 列表
                        if (!this.sall3) {
                            // 全选：将未包含的 URL 添加到 accessAll
                            urls.forEach(url => {
                                if (!this.accessAll.includes(url)) {
                                    this.accessAll.push(url);
                                }
                            });
                        } else {
                            // 取消全选：移除 accessAll 中与当前 permissions 匹配的 URL
                            this.accessAll = this.accessAll.filter(url => !urls.includes(url));
                        }
                    },

                    selectMenu2: function (e) {
                        this.children2 = e.children||[];
                    },
                    selectMenu: function (e) {
                        // 使用 reduce 合并所有子项，避免修改原数组
                        this.children = (e.children || []).reduce((acc, item) => {
                            acc.push(item);
                            // 将每个子项的 children 合并到 acc 中
                            if (item.children && item.children.length > 0) {
                                acc = acc.concat(item.children);
                            }
                            return acc;
                        }, []);
                    },
                    selectApp: function (e) {
                        this.s1 = e.id;
                        this.classs = e.classs;
                        this.permissions = [];
                        this.sall3 = false;
                        e.classs.forEach(item => {
                            this.permissions = this.permissions.concat(item.methods)
                            // Object.assign(that.permissions, item.methods);
                        });
                        // console.log(this.permissions);
                    },
                    selectCla(e) {
                        this.s2 = e.id;
                        this.permissions = e.methods;
                    },
                    selectPerm(e) {
                        this.s3 = e.id;
                    },
                    load: function () {
                        var self = this;

                        iCMS.api.post('apps/accessData',
                            function (ret) {
                                self.applications = ret.data.apps
                                self.menus = ret.data.menus
                                self.$el.classList.remove('hide')
                            }
                        );
                        iCMS.api.post('./access_app', { id: $APP.get.id, key: 'app' }, function (ret) {
                            self.accessAll = ret.data.access || []
                        });
                    },

                },
                mounted: function () {
                    this.load();
                }
            });
        },
        access_app: function ({ el }) {
            factory.vm1 = this.AccessAppVue();
        },
        access_app1119: function () {

            // UI.tooltip(params);
            var $el = iCMS.$("cms:app:access:select");
            $el.each(function (index, element) {
                UI.run($(element), 'tooltip');
            });
            iCMS.api.post('./access_app', { id: $APP.get.id, key: 'app' }, function (json) {
                $.each(json.data.access, function (idx, ev) {
                    $("[value='" + ev + "']").prop("checked", true);
                });
            });
            $("#tree").treeview({
                collapsed: false,
                animated: "medium",
                control: "#tree_control",
                callback: function (a) {
                    // console.log('sdfsdf');
                }
            });
            let doc = $(document);
            // $('[name="access[app][]"]').change(
            doc.on('change', '[name="access[app][]"]', function (e) {
                e.preventDefault();
                var checked = $(this).prop("checked");
                // 使用 split 方法将字符串分割成数组
                let parts = this.value.split('/');
                // 获取数组的前两个元素
                let firstTwoStrings = parts.slice(0, 2);
                // 重新拼接字符串
                let value = firstTwoStrings.join('/');
                console.log(value);

                $("[value='" + value + "']").prop("checked", checked);
            });

        },
        access_select: function ({ $el, el }) {
            var ev = $el.data("value");
            var type = $el.data("type");
            var checked = $el.prop("checked")
            console.log(checked);
            if (type == 'e') {
                $("[value$='" + ev + "']", "#node-list").prop("checked", checked);
            }
            if (type == 's') {
                $("[value^='" + ev + "']", "#node-list").prop("checked", checked);
            }
            if (type == 'r') {
                $.each(ev, function (idx, nodeid) {
                    $("[value^='" + nodeid + "']", "#node-list").prop("checked", checked);
                });
            }
        }
    }
    return factory;
});