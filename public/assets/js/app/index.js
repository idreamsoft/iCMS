define(["cms", "app@user"], function (cms,USER) {
    return {
        index: function () {
            USER.timeline();
        }
    }
});