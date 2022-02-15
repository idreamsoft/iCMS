var $VueData = iCMS.$SET['Vue.data'] || {};
var $VueConfig = iCMS.$SET['Vue.config'] || {};
if ($VueData || $VueConfig) {
    var $VueConfig = $.extend({
        el: '#main-container',
        data: $VueData,
        watch: {},
        updated: function() {
            console.log(this.$data);
            // this.$data.forEach(element => {
            //     console.log(element);
            // });
            // console.log(this);
            // // this.$nextTick(function() {
            // //     // Code that will run only after the
            // //     // entire view has been re-rendered
            // // })
        }
    }, $VueConfig);

    if (iCMS.$SET['Vue.methods']) {
        $VueConfig.methods = $.extend($VueConfig.methods, iCMS.$SET['Vue.methods']);
    }
    if (iCMS.$SET['Vue.watch']) {
        $VueConfig.watch = $.extend($VueConfig.watch, iCMS.$SET['Vue.watch']);
    }

    console.log($VueConfig);
    Vue.config.silent = true;
    if (!$.isEmptyObject($VueConfig.data)) {
        iCMS.Vue = new Vue($VueConfig);
    }
}