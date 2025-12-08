define(["admincp", 'vue'], function (admincp, Vue) {
    return function () {
        var doc = $(document);
        // doc.on('click', '.helper-btn', function () {
        //     iCMS.ui.dialog(this,{
        //         title:'sdf',height:'auto',
        //         content: document.getElementById("helper-box"),
        //         yes:function(index, dom, row){

        //         }
        //     });
        // });
        doc.on("mouseover", 'a.helper-btn', function () {
            var pp = $(this).parent();
            var li = $("li.dropdown-submenu", "#helper-box").clone(true);
            $(".dropdown-menu", pp).empty().html(li);
        })
        doc.on("click", 'a[data-helper]', function () {
            var pp = $(this).parents(".process-btns");
            var idx = pp.data("index");
            var helper = $(this).data("helper")||[];
            var title = $(this).attr("title");
            var name = $(this).attr("name");
            // console.log({helper,name,title,rule:''});

            addProcess({ helper, name, title, rule: '' }, idx);
        })
        new Vue({
            el: '#DataRuleWrap',
            data() {
                return {
                    items: [] // 数据项列表
                };
            },
            watch: {
                // 可以在这里添加需要监听的属性
            },
            methods: {
                // 处理数据源复选框变化
                hasDataSource(e, item) {
                    if (!e.target.checked) {
                        item.data_source = '';
                    }
                },
                // 处理分页复选框变化
                hasPage(e) {
                    if (e.target.checked) {
                        iCMS.ui.alert("此数据项您选择有分页,\n\n请记得设置[分页设置]选项卡的内容!");
                    }
                },
                // 处理正则表达式或 DOM 选择器切换
                pregOrdom(e, item) {
                    const isDom = e.target.name.includes('[dom]');
                    item.preg = !isDom && e.target.checked;
                    item.dom = isDom && e.target.checked;
                },
                // 添加处理过程
                addProcess(helper, idx) {
                    if (!this.items[idx].process) {
                        this.$set(this.items[idx], 'process', []);
                    }
                    this.items[idx].process.push(helper);
                },
                // 删除处理过程
                deleteProcess(item, pidx) {
                    this.$delete(item.process, pidx);
                },
                // 删除数据项
                deleteItem(index) {
                    if (confirm(Lang('Confirm to delete?'))) {
                        this.$delete(this.items, index);
                    }
                },
                // 添加数据项
                addItem(e) {
                    const name = e.target.dataset.name;
                    this.items.push({ name });
                },
                // 加载初始数据
                load() {
                    try {
                        const jtext = document.getElementById('DataRuleJson').textContent;
                        if (jtext) {
                            this.items = JSON.parse(jtext) || [];
                        }
                    } catch (error) {
                        console.error('Failed to load data:', error);
                    }
                }
            },
            mounted() {
                this.load(); // 初始化加载数据
                window.addProcess = this.addProcess.bind(this); // 将方法挂载到全局
            },
            updated() {
                // 可以在这里添加更新后的逻辑
            }
        });
    };
});