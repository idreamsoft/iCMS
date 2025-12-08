define(["jq-lazyload", "jq-appear"], function () {
    "use strict";
    console.log("cms.js is loaded");
    const factory = {
        init: function () {
            console.log("cms.init");
        },
        finish: function () {
            $("img.lazyload").lazyload({
                effect: "fadeIn",
            });
            One.helpers('jq-appear');
            console.log("cms.finish");
        },
    };
    return factory;
});
if (!$APP.debug) {
    console.log = function () { };
}