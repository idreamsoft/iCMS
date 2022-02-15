var files = {
    //files,picbtn.html
    picbtn: function(opt) {
        this.opt = opt;
        // console.log(a);
        var el = $("#"+opt.id);
        opt.is_http && iCMS.setVueData(opt.vdata);
        
        this._create = function(idx, v) {
            var item = template(opt.template, v);
            item = item.replace('@a@',el.prop('name'));
            mp.append(item);
        };
        if (opt.is_multi) {
            var mp = $('.js-gallery',el.parent());
            // $(opt.multi.item, mp).on("mouseover", function() {
            //     $(this).find(".delete").show();
            // }).on("mouseout", function() {
            //     $(this).find(".delete").hide();
            // });
            mp.on("click", ".delete", function (event) {    
                var _this = this;
                iCMS.ui.dialog({
                    content: '确定要移除'+opt.title+'吗？',
                    label: 'warning',
                    icon: 'warning',
                    okValue: '确定',
                    ok: function() {
                        $(_this).closest('div').remove();
                        return true;
                    },
                    cancelValue: '取消',
                    cancel: function() {
                        return true;
                    }
                });
            });
            $.each(opt.data, this._create);
        }
        this.modal = function(e, b, flag) {
            console.log(b);
            if (opt.is_multi) {
                this._create(0, b);
            } else {
                el.val(b.data.value);
            }
            //不关闭窗口
            if (flag === false) {
                return true;
            }
            window.iCMS_MODAL.destroy();
        };
    }
}