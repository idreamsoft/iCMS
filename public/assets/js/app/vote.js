define(["cms"], function (cms) {
    const params = $APP.params || {};
    const factory = {
        $widget: {},
        good_num: function ({ $el, el }) {
            this.$widget['good:num'] = $el;
        },
        good: function ({ $el, el }) {
            var that = this;
            var event = 'good'
            iCMS.api.spost('vote/update', { event, params }, function ({ data, message }) {
                iCMS.notify.info(message);
                var numObj = that.$widget['good:num'];
                var count = parseInt(numObj.text()) || 0;
                numObj.text(count + data['num']);

                $("i", $el).removeClass();
                if (data['num'] > 0) {
                    $("i", $el).addClass("fa fa-thumbs-up text-primary");
                } else {
                    $("i", $el).addClass("far fa-thumbs-up");
                }
            });
        }
    }
    return factory;
});