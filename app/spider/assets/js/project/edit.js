define(["admincp"], function (admincp) {
    return function () {
        var box = document.getElementById("mkurls");
        $('#makeurls').click(function () {
            iCMS.ui.dialog({
                title: '添加采集地址',
                content: box,
                height:'auto',
                yes: function () {
                    var urls_text = $("#urls").val();
                    if (urls_text) {
                        urls_text += "\n";
                    }
                    var urls_rs = $("#urls_rs").val();
                    $("#urls").val(urls_text + urls_rs);
                },
            });
        });
        $(":text", box).keyup(function (event) {
            if (this.id == 'url_pattern') {
                return;
            }
            var pp = $(this).closest("div.form-group");
            var a = $("[name='format']", pp);
            preview(a[0]);
        });
        $(":radio,:checkbox", box).click(function (event) {
            preview(this);
        });

        function preview(a) {
            var pp = $(a).closest("div.form-group"),
                format = parseInt($("[name='format']", pp).val()),
                begin = $("[name='begin']", pp).val() || 0,
                num = $("[name='num']", pp).val() || 0,
                step = $("[name='step']", pp).val() || 1,
                zeroize = $("[name='zeroize']", pp).prop("checked"),
                reverse = $("[name='reverse']", pp).prop("checked"),
                url = $('#url_pattern').val(),
                start = 0,
                end = 0,
                pattern = '<' + format + ',' + begin + ',' + num + ',' + step + ',' + zeroize + ',' + reverse + '>';
            if (format == "2") {
                pattern = '<' + format + ',' + begin + ',' + num + ',' + reverse + '>';
            }
            $("[name='format']").prop("checked", false).parent().removeClass('checked');
            $("[name='format']", pp).prop("checked", true).parent().addClass('checked');

            if (url == "") {
                iCMS.ui.alert("请先输入网址");
                return;
            }
            if (!url.has("(*)")) {
                iCMS.ui.alert("请使用(*)格式通配符匹配网址");
                return;
            }

            if (format == 0) {
                var num = parseInt(num) - 1;
                if (num < 0) num = 0;
                start = parseInt(begin);
                // end   = start+parseInt(num);
                end = start + step * num;
            } else if (format == 1) {
                // if(parseInt(step)==1){
                //   iCMS.ui.alert("等比不能为1");
                //   return;
                // }
                if (num > 32) {
                    iCMS.ui.alert("等比数列数值太大,请重新设置初始值,项数,比值");
                    return;
                }
                start = parseInt(begin);
                // end   = start*Math.pow(parseInt(step), num-1);//parseInt(num)*parseInt(step);
                end = start + step * num;
            } else if (format == 2) {
                start = begin.charCodeAt(0);
                end = num.charCodeAt(0);
            }
            urls = urlist(format, start, end, url, reverse, parseInt(step), zeroize, num);
            $("#urls_rs").val(url.replace('(*)', pattern));
            $("#preview").html(urls);
        }

        function urlist(format, start, end, url, reverse, step, zeroize, num) {
            var urls = '',
                n = 0;
            if (format == 2) {
                num = end - start;
            }
            if (reverse) {
                for (var i = end; i >= start;) {
                    if (n < 5) {
                        urls += _url(url, format, i, zeroize, end);
                    } else {
                        break;
                    }
                    n++;
                    // if(format==1){
                    //   i=i/step;
                    // }else{
                    // }
                    i = i - step;
                }

                if (num > 5) {
                    urls += '..................<br />';
                    urls += _url(url, format, start, zeroize, end);
                }
            } else {
                for (var i = start; i <= end;) {
                    if (n < 5) {
                        urls += _url(url, format, i, zeroize, end)
                    } else {
                        break;
                    }
                    n++;
                    // if(format==1){
                    //   i=i*step;
                    // }else{
                    // }
                    i = i + step;
                }
                if (num > 5) {
                    urls += '..................<br />';
                    urls += _url(url, format, end, zeroize, end);
                }
            }
            return urls;
        };

        function _url(url, format, i, zeroize, end) {
            var ii = i
            if (format == 2) {
                ii = String.fromCharCode(i);
            } else {
                if (zeroize) {
                    var len = end.toString().length;
                    if (len == 1) {
                        len = 2;
                    }
                    ii = pad(i, len);
                }
            }
            return url.replace('(*)', ii) + '<br />';
        }
    };
});