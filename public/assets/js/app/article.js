define(["cms", "app@user","jq-appear"], function (cms,USER) {
    return {
        index: function () {
            USER.timeline();
            One.helpers('jq-appear');
        }
    }
});