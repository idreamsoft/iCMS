define(["admincp", "vue"], function (admincp, Vue) {
    return function ({ $el, el }) {
        var $wrap = $el;
        new Vue({
            el,
            data: {
                newKey: '',
                newValue: '',
                editingIndex: null,
                dictionary: []
            },
            mounted() {
                this.fetchData();
                this.show();
            },
            methods: {
                show() {
                    $wrap.show().removeClass('hide');
                },
                // 获取数据
                async fetchData() {
                    var _this = this;
                    try {
                        iCMS.api.post('config/dist/all', {}, function (res) {
                            _this.dictionary = res.data.map(d => ({
                                id: d.id,
                                key: d.name || 'key' + d.id,
                                value: d.body
                            }));
                        });

                    } catch (err) {
                        alert('获取数据失败');
                        console.error(err);
                    }
                },

                // 添加数据
                async addItem() {
                    var _this = this;
                    const key = this.newKey.trim();
                    const value = this.newValue.trim();

                    if (!key || !value) {
                        alert('请填写 Key 和 Value');
                        return;
                    }

                    const exists = this.dictionary.some(item => item.key === key);
                    if (exists) {
                        alert('该 Key 已存在，请勿重复添加');
                        return;
                    }

                    try {
                        iCMS.api.post('config/dist/add', {
                            name: key,
                            body: value
                        }, function (res) {
                            _this.dictionary.push({
                                id: res.data.id,
                                key,
                                value
                            });
                        });

                        this.newKey = '';
                        this.newValue = '';
                    } catch (err) {
                        alert('添加失败');
                        console.error(err);
                    }
                },

                // 编辑数据
                editItem(index) {
                    this.editingIndex = index;
                },

                // 保存修改
                async saveItem(index) {
                    const item = this.dictionary[index];
                    const key = item.key.trim();
                    const value = item.value.trim();

                    if (!key || !value) {
                        alert('Key 和 Value 都不能为空');
                        return;
                    }

                    const duplicate = this.dictionary.some((d, i) => i !== index && d.key === key);
                    if (duplicate) {
                        alert('该 Key 已存在，请修改');
                        return;
                    }

                    try {
                        iCMS.api.post('config/dist/edit', {
                            id: item.id,
                            name: key,
                            body: value
                        });

                        this.editingIndex = null;
                    } catch (err) {
                        alert('保存失败');
                        console.error(err);
                    }
                },

                // 删除数据
                async removeItem(index) {
                    const item = this.dictionary[index];

                    if (!confirm('确定要删除这项吗？')) return;

                    try {
                        iCMS.api.post('config/dist/delete', {
                            id: item.id,
                        });
                        this.dictionary.splice(index, 1);
                    } catch (err) {
                        alert('删除失败');
                        console.error(err);
                    }
                }
            }
        });
    };
});