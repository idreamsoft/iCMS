define(function () {
    return function () {
        var n = 0, timer = 0;
        timer = setInterval(function () {
            if (n > msg.length - 1) {
                n = -1;
                clearIntervals();
            }
            if (n > -1) {
                play(msg[n]);
                window.scrollTo(0, $(document.body).outerHeight(true));
                n++;
            }
        }, 100);

        function play(text) {
            text = text.replace('#', '<hr />');
            document.getElementById('log').innerHTML += text + '<br /><a name="last"></a>';
        }

        function clearIntervals() {
            clearInterval(timer);
            $('#btn').removeClass('hide');
            if (upgrade) {
                play('<div class="alert alert-success">源码更新完成!</div>');
                play('<div class="alert alert-primary">现在开始执行升级程序! >>>></div>');
                window.setTimeout(function () {
                    window.location.href = upgrade_url;
                }, 1000);
            }
        }
    }
});