define(["cms"], function (cms) {
    return {
        add: function ({ $el, el }) {
            var $wrap = $el.closest("[data-id]");
            var id = $wrap.data("id");
            if (id) {
                iCMS.api.spost('UserNode/get', { id }, function (json) {
                    var form = iCMS.$('cms:UserNode:addbox');
                    $('[name="name"]', form).val(json.data.name);
                    $('[name="description"]', form).val(json.data.description);
                    $('[name="mode"]', form).val(json.data.mode);
                    dialog(id);
                })
            } else {
                dialog(id);
            }
            function dialog(id) {
                iCMS.ui.dialog(el, {
                    height: 'auto',
                    content: iCMS.$('cms:UserNode:addbox'),
                    yes: function (index, dom) {
                        var data = $("form", dom).serializeJSON();
                        id && (data['id'] = id);

                        console.log(data);
                        if (!data['name']) {
                            $('[name="name"]', dom).addClass('is-invalid');
                            iCMS.notify.warning('请输入分类名称');
                            return false;
                        }
                        iCMS.api.post('UserNode/create', data, function (json) {
                            iCMS.notify.success(json.message);
                            Layer.close(index);
                            window.location.reload();
                        }, function (json) {
                            iCMS.notify.warning(json.message);
                        });
                    }
                });
            }

        },
        delete: function ({ $el, el }) {
            var $wrap = $el.closest("[data-id]");
            var id = $wrap.data("id");
            if (!confirm(Lang('Confirm to delete?'))) {
                return false;
            }
            iCMS.api.spost('UserNode/delete', { id }, function (json) {
                iCMS.notify.success(json.message);
                $wrap.remove();
            }, function (json) {
                iCMS.notify.error(json.message, 3000);
            })
        }
    }
});