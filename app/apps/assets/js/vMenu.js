define(["vue"], function (Vue) {
    return function ({ $el, el }) {
        const dataElement = document.getElementById('menu-data');
        let $data = JSON.parse(dataElement.value || '[]');

        // 递归转换对象结构为数组结构
        function convertObjectToArray(data) {
            if (Array.isArray(data)) {
                return data.map(item => convertObjectToArray(item));
            }

            if (data && typeof data === 'object') {
                // 如果是菜单项对象，保持其结构但转换children
                if (data.caption || data.href || data.icon || data.id || data.access) {
                    const item = { ...data };
                    if (item.children && typeof item.children === 'object' && !Array.isArray(item.children)) {
                        item.children = Object.values(item.children).map(child => convertObjectToArray(child));
                    } else if (Array.isArray(item.children)) {
                        item.children = item.children.map(child => convertObjectToArray(child));
                    }
                    return item;
                }
                // 如果是包含菜单项的对象，转换为数组
                return Object.values(data).map(item => convertObjectToArray(item));
            }

            return data;
        }

        $data = convertObjectToArray($data);

        // 确保最终结果是数组
        if (!Array.isArray($data)) {
            $data = [];
        }

        // 注册菜单项组件
        Vue.component('menu-item', {
            template: '#menu-item-template',
            props: ['item', 'index', 'parent'],
            data() {
                return {
                    isDragOver: false
                };
            },
            computed: {
                isDragging() {
                    return this.$parent.isDragging;
                }
            },
            methods: {
                // 传递事件到父组件
                emitEvent(eventName, ...args) {
                    this.$emit(eventName, ...args);
                }
            }
        });

        return new Vue({
            el,
            data: {
                items: $data || [],
                draggedItem: null,
                draggedIndex: null,
                draggedParent: null,
                isDragging: false,
                editingItem: null,
                showAddForm: false,
                showJsonEditor: false,
                newItem: {
                    caption: '',
                    href: '',
                    icon: '',
                    id: '',
                    sort: '1'
                }
            },
            methods: {
                emitEvent(eventName, ...args) {
                    this.$emit(eventName, ...args);
                },
                // 添加菜单项
                addItem(parent = null) {
                    this.showAddForm = true;
                    this.newItem = {
                        caption: '',
                        href: '',
                        icon: '',
                        id: '',
                        sort: '1'
                    };
                },

                // 保存新菜单项
                saveNewItem(parent = null) {
                    if (!this.newItem.caption.trim()) return;

                    const item = {
                        ...this.newItem,
                        id: this.newItem.id || this.generateId(),
                        children: []
                    };

                    if (parent) {
                        if (!parent.children) parent.children = [];
                        parent.children.push(item);
                    } else {
                        this.items.push(item);
                    }

                    this.showAddForm = false;
                    this.saveData();
                },

                // 取消添加
                cancelAdd() {
                    this.showAddForm = false;
                },

                // 编辑菜单项
                editItem(item) {
                    this.editingItem = { ...item, _original: item };
                },

                // 保存编辑
                saveEdit(originalItem) {
                    Object.assign(originalItem, this.editingItem);
                    this.editingItem = null;
                    this.saveData();
                },

                // 取消编辑
                cancelEdit() {
                    this.editingItem = null;
                },

                // 删除菜单项
                deleteItem(item, parent = null) {
                    if (!confirm('确定要删除这个菜单项吗？')) return;

                    console.log('deleteItem"', {item, parent});
                    
                    if (parent && parent.children) {
                        const index = parent.children.findIndex(i => i.id === item.id);
                        if (index > -1) {
                            parent.children.splice(index, 1);
                        }
                    } else {
                        const index = this.items.findIndex(i => i.id === item.id);
                        if (index > -1) {
                            this.items.splice(index, 1);
                        }
                    }
                    this.saveData();
                },

                // 拖拽开始
                onDragStart(event, item, index, parent = null) {
                    this.draggedItem = item;
                    this.draggedIndex = index;
                    this.draggedParent = parent;
                    this.isDragging = true;
                    event.dataTransfer.effectAllowed = 'move';
                },

                // 拖拽结束
                onDragEnd() {
                    this.draggedItem = null;
                    this.draggedIndex = null;
                    this.draggedParent = null;
                    this.isDragging = false;
                },

                // 拖拽悬停
                onDragOver(event) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                },

                // 放置
                onDrop(event, targetItem, targetIndex, targetParent = null, dropInside = false) {
                    event.preventDefault();

                    if (!this.draggedItem || this.draggedItem === targetItem) {
                        return;
                    }

                    // 防止将父菜单拖拽到自己的子菜单中
                    if (dropInside && this.isDescendant(targetItem, this.draggedItem)) {
                        return;
                    }

                    // 移除原位置的项目
                    if (this.draggedParent && this.draggedParent.children) {
                        this.draggedParent.children.splice(this.draggedIndex, 1);
                    } else {
                        this.items.splice(this.draggedIndex, 1);
                    }
                    console.log({
                        draggedItem: this.draggedItem,
                        draggedIndex: this.draggedIndex,
                        draggedParent: this.draggedParent,
                        targetItem: targetItem,
                        targetIndex: targetIndex,
                        targetParent: targetParent,
                        dropInside: dropInside
                    });

                    // 插入到新位置
                    if (dropInside) {
                        // 拖拽到菜单项内部，作为子菜单
                        if (!targetItem.children) {
                            this.$set(targetItem, 'children', []);
                        }
                        targetItem.children.push(this.draggedItem);
                        // 自动展开目标菜单
                        this.$set(targetItem, 'expanded', true);
                    } else {
                        // 拖拽到菜单项之间，作为同级菜单
                        if (targetParent && targetParent.children) {
                            targetParent.children.splice(targetIndex, 0, this.draggedItem);
                        } else {
                            this.items.splice(targetIndex, 0, this.draggedItem);
                        }
                    }

                    this.saveData();
                },

                // 检查是否为后代节点
                isDescendant(ancestor, descendant) {
                    if (!ancestor || !descendant) return false;

                    if (ancestor.children) {
                        for (let child of ancestor.children) {
                            if (child === descendant || this.isDescendant(child, descendant)) {
                                return true;
                            }
                        }
                    }
                    return false;
                },

                // 添加子菜单
                addChild(parent) {
                    if (!parent.children) {
                        this.$set(parent, 'children', []);
                    }

                    const newChild = {
                        caption: '新子菜单',
                        href: '',
                        icon: '',
                        id: this.generateId(),
                        sort: '1',
                        children: []
                    };

                    parent.children.push(newChild);
                    // 自动展开父菜单
                    this.$set(parent, 'expanded', true);
                    this.saveData();
                },

                // 生成ID
                generateId() {
                    return 'menu_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                },

                // 保存数据
                saveData() {
                    const dataElement = document.getElementById('menu-data');
                    if (dataElement) {
                        // 创建一个深拷贝，排除循环引用的属性
                        const cleanData = JSON.parse(JSON.stringify(this.items, (key, value) => {
                            // 排除Vue内部属性和我们添加的_original属性
                            if (key.startsWith('_') || key.startsWith('$')) {
                                return undefined;
                            }
                            return value;
                        }));
                        dataElement.value = JSON.stringify(cleanData, null, 4);
                    }
                },

                // 从textarea加载数据
                loadFromJson() {
                    const dataElement = document.getElementById('menu-data');
                    if (dataElement && dataElement.value.trim()) {
                        try {
                            let jsonData = JSON.parse(dataElement.value);

                            // 递归转换对象结构为数组结构
                            function convertObjectToArray(data) {
                                if (Array.isArray(data)) {
                                    return data.map(item => convertObjectToArray(item));
                                }

                                if (data && typeof data === 'object') {
                                    // 如果是菜单项对象，保持其结构但转换children
                                    if (data.caption || data.href || data.icon || data.id || data.access) {
                                        const item = { ...data };
                                        if (item.children && typeof item.children === 'object' && !Array.isArray(item.children)) {
                                            item.children = Object.values(item.children).map(child => convertObjectToArray(child));
                                        } else if (Array.isArray(item.children)) {
                                            item.children = item.children.map(child => convertObjectToArray(child));
                                        }
                                        return item;
                                    }
                                    // 如果是包含菜单项的对象，转换为数组
                                    return Object.values(data).map(item => convertObjectToArray(item));
                                }

                                return data;
                            }

                            jsonData = convertObjectToArray(jsonData);

                            // 确保最终结果是数组
                            if (!Array.isArray(jsonData)) {
                                jsonData = [];
                            }

                            this.items = jsonData;

                            // 初始化展开状态
                            if (Array.isArray(this.items)) {
                                this.items.forEach(item => {
                                    this.$set(item, 'expanded', true);
                                });
                            }

                            alert('JSON数据加载成功！');
                        } catch (e) {
                            alert('JSON格式错误，请检查数据格式！');
                        }
                    }
                },

                // 切换展开/折叠
                toggleExpand(item) {
                    this.$set(item, 'expanded', !item.expanded);
                },
                assignIds(items) {
                    if (!Array.isArray(items)) return;
                    items.forEach(item => {
                        if (!item.id) {
                            item.id = this.generateId();
                        }
                        if (item.children && Array.isArray(item.children)) {
                            this.assignIds(item.children);
                        }
                    });
                }
            },
            
            mounted: function () {
                this.assignIds(this.items);
                // 确保items是数组并初始化展开状态
                if (Array.isArray(this.items)) {
                    this.items.forEach(item => {
                        this.$set(item, 'expanded', true);
                    });
                }
            }
        });
    };
});