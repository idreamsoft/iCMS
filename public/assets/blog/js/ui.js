
define(["cms"], function (cms) {
    const factory = {
        hots: function ({$el ,el}) {
            // console.log($el,el);
            var width = $el.width();
            factory.scrollBox($el,width);
        },
        scrollBox: function (target,width) {
            if (target.length > 0) {
                $(window).scroll(function (event) {
                    event.preventDefault();
                    
                    var height = target.height();
                    var ntop = target.next().offset().top;
                    var stop = $(window).scrollTop();
          
                    if (stop > ntop + height) {
                        target.css({ position: "fixed", top: "5" + "px"});
                    } else {
                        target.css({ position: "static" });
                    }
                });
            }
        }
    }
    return factory;
});