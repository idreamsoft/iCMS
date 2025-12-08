define(["vue"], function (Vue) {
    return function ({ $el, el }) {
        const $wrap = $el.parent();
        const defitem = { key: "", name: "", value: "" };
        const items = $el.data("items") || [];
        const from = $el.data("from") || '';

        // Helper function to merge items
        function mergeItems(existingItems, nodeItems) {
            const nodeItemMap = {};
            nodeItems.forEach(item => {
                if (item.key) {
                    nodeItemMap[item.key] = item;
                }
            });

            // Merge existing items with new items
            const mergedItems = existingItems.map(item => {
                if (nodeItemMap[item.key]) {
                    const nodeItem = nodeItemMap[item.key];
                    return {
                        key: item.key,
                        name: item.name || nodeItem.name,
                        value: item.value != nodeItem.value ? item.value : nodeItem.value
                    };
                }
                return item; // Retain existing item if no match in new items
            });

            // Add new items that don't exist in existing items
            nodeItems.forEach(nodeItem => {
                if (!mergedItems.some(item => item.key === nodeItem.key)) {
                    mergedItems.push(nodeItem);
                }
            });

            return mergedItems;
        }

        return new Vue({
            el,
            data: {
                hide: false,
                items
            },
            methods: {
                hidden() {
                    this.hide = !this.hide;
                },
                close() {
                    this.items = [];
                    $wrap.hide();
                },
                remove(index) {
                    this.$delete(this.items, index);
                },
                load() {
                    const ids = $(from).attr('value');
                    if (ids) {
                        this.getAttrs(ids);
                    }

                    if (this.items.length > 0) {
                        $wrap.show();
                    }

                    if (from) {
                        $(document).on('change', from, e => {
                            e.preventDefault();
                            const ids = $(e.target).data('ids');
                            this.getAttrs(ids);
                        });
                    }
                },
                getAttrs(ids) {
                    iCMS.api.post('/node/attrs', { ids }, json => {
                        if (json.data.length > 0) {
                            // Merge json.data into items
                            this.items = mergeItems(this.items, json.data);

                            // Ensure the wrapper is shown
                            $wrap.show();
                        }
                    });
                },
                add() {
                    this.items.push({ ...defitem });
                }
            },
            mounted() {
                this.load();
            },
            beforeDestroy() {
                // Clean up event listeners to prevent memory leaks
                if (from) {
                    $(document).off('change', from);
                }
            }
        });
    };
});