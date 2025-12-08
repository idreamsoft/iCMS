define(['vue'], function (Vue) {
    return function ({ $el, el }) {
        let vm = new Vue({
            el: el,
            data: {
                blocks: [
                    {
                        "title": "文章数",
                        "count": 0,
                        "icon": "fa fa-briefcase"
                    },
                    {
                        "title": "节点/分类数",
                        "count": 0,
                        "icon": "fa fa-folder"
                    },
                    {
                        "title": "标签数",  
                        "count": 0,
                        "icon": "fa fa-tags"
                    },
                    {
                        "title": "评论数",
                        "count": 0,
                        "icon": "fa fa-comments"
                    },
                    {
                        "title": "用户数",
                        "count": 0,
                        "icon": "fa fa-users"
                    },
                    {
                        "title": "应用数",
                        "count": 0,
                        "icon": "fa fa-plug"
                    },
                    {
                        "title": "文件数",
                        "count": 0,
                        "icon": "fa fa-paint-brush"
                    }
                ]
            },
            mounted() {
                let that = this;
                $('.block-content').find('.d-none').removeClass('d-none');
                iCMS.api.get('admincp/stats', (json) => {
                    that.blocks = json.data['blocks'];
                });
            }
        });
    };
});
