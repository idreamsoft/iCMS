define(["validation"], function () {
    const factory = {
        index: function (params) {

        },
        hooks_remove: function ({ $el, el }) {
            $el.parents('tr').remove();
            let config = $('input', $("tbody")).serializeJSON();
            iCMS.api.post('/appsHooks/save', { config, remove: 1 });
        },
        hooks_add: function ({ $el, el }) {
            let $form = $("#hooks_form");
            iCMS.ui.dialog({
                title: '添加钩子',
                content: '#hooks_box',
                height: 'auto',
                yes: function () {
                    let config = $form.serializeJSON();
                    if (!config.app) {
                        iCMS.notify.alert('请选择应用')
                        return false;
                    }
                    if (!config.field) {
                        iCMS.notify.alert('请选择字段')
                        return false;
                    }
                    if (!config.method) {
                        iCMS.notify.alert('method')
                        return false;
                    }
                    iCMS.api.post('/appsHooks/save', { hooks: config });
                },
                end: function () {
                    $form[0].reset();
                }
            });
        }
    }
    return factory;
})