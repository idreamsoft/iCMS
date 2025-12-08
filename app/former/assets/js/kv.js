define(["vue"], function (Vue) {
    return function ({ $el, el }) {
        const kvitem = { key: "", name: "", value: "" }; // 基础字段

        return new Vue({
            el,
            data: {
                items: [],
                extraColumns: 0, // 记录额外列数
                dragIndex: null
            },
            methods: {
                remove: function (index) {
                    this.$delete(this.items, index);
                },
                // 生成随机键名并填充到所有行，保证每行唯一
                fillRandomKeys: function () {
                    const existing = new Set();
                    this.items.forEach(it => {
                        if (it.key) existing.add(it.key);
                    });
                    const gen = () => {
                        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        let s = '';
                        for (let i = 0; i < 8; i++) {
                            s += chars.charAt(Math.floor(Math.random() * chars.length));
                        }
                        return s;
                    };
                    this.items.forEach((it, idx) => {
                        let candidate = gen();
                        while (existing.has(candidate)) {
                            candidate = gen();
                        }
                        this.$set(it, 'key', candidate);
                        existing.add(candidate);
                    });
                },
                load: function () {
                    let kvName = $el.data('name');
                    if (kvName) {
                        let dataElement = document.getElementById(kvName);
                        if (!dataElement.textContent) { return; }
                        this.items = JSON.parse(dataElement.textContent) || [];

                        // 计算当前有多少额外列（通过检查第一个对象的属性）
                        if (this.items.length > 0) {
                            const firstItem = this.items[0];
                            this.extraColumns = 0;
                            for (let key in firstItem) {
                                if (key.startsWith('value') && key !== 'value') {
                                    const num = parseInt(key.replace('value', ''));
                                    if (!isNaN(num) && num > this.extraColumns) {
                                        this.extraColumns = num;
                                    }
                                }
                            }
                            if (this.extraColumns > 0) this.extraColumns--; // 转换为索引
                        }
                    }
                    this.$el.classList.remove('hide');
                },
                handleKeyInput: function (item, event) {
                    const value = event.target.value.replace(/[^a-zA-Z0-9\_]/g, '');
                    item.key = value;
                },
                // 拖拽排序
                onDragStart: function (index, event) {
                    this.dragIndex = index;
                    if (event && event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        try {
                            event.dataTransfer.setData('text/plain', String(index));
                        } catch (e) {}
                    }
                },
                onDrop: function (index, event) {
                    const from = this.dragIndex;
                    if (from === null || from === index) return;
                    const moved = this.items.splice(from, 1)[0];
                    this.items.splice(index, 0, moved);
                    this.dragIndex = null;
                },
                onDragEnd: function () {
                    this.dragIndex = null;
                },
                addrow: function () {
                    var item = $.extend({}, kvitem);
                    // 添加额外的 value 字段
                    for (let i = 1; i <= this.extraColumns; i++) {
                        item[`value${i}`] = '';
                    }
                    this.items.push(item);
                },
                addcol: function () {
                    this.extraColumns++;
                    // 给现有所有行添加新的 value 字段
                    this.items.forEach(item => {
                        item[`value${this.extraColumns}`] = '';
                    });
                },
                // 删除指定列的功能
                removeColumn: function (colIndex) {
                    // 确保至少保留基础列（value1 就是 value，所以 extraColumns >= 1 才能删除）
                    if (this.extraColumns < 1) {
                        alert('至少需要保留基础的三列！');
                        return;
                    }

                    // 确认删除
                    if (!confirm(`确定要删除第 ${colIndex + 1} 列吗？`)) {
                        return;
                    }

                    // 从每行中删除对应的字段
                    this.items.forEach(item => {
                        // 删除指定的 value 字段
                        delete item[`value${colIndex}`];

                        // 将后面的字段向前移动
                        for (let i = colIndex; i < this.extraColumns; i++) {
                            item[`value${i}`] = item[`value${i + 1}`] || '';
                        }
                        // 删除最后一个多余的字段
                        delete item[`value${this.extraColumns}`];
                    });

                    // 减少列计数
                    this.extraColumns--;
                }
            },
            mounted: function () {
                this.load();
            }
        });
    };
});