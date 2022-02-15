/*
 * Async Treeview 0.1 - Lazy-loading extension for Treeview
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-treeview/
 *
 * Copyright (c) 2007 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Revision: $Id: jquery.treeview.async.js 179 2013-03-29 03:21:28Z coolmoo $
 *
 */

;
(function($) {
    function load(settings, root, child, container) {
        function createNode(a, b) {
            var html = '<div class="row"></div>';
            if (a.data) {
                if (settings.tpl) {
                    html = settings.tpl(a.data);
                } else {
                    html = a.data;
                }
            }
            var current = $("<li/>")
                .attr("id", a.id)
                .html(html)
                .appendTo(b)
                .mouseover(function() {
                    $(this).css("background-color", "#E7E7E7");
                }).mouseout(function() {
                    $(this).css("background-color", "#FFFFFF");
                });

            if (settings.classname) {
                current.addClass(settings.classname);
            }
            if (settings.callback) {
                settings.callback(current);
            }
            if (a.expanded) {
                current.addClass("open");
            }
            if (a.hasChildren || a.children && a.children.length) {
                var branch = $("<ul/>").appendTo(current);
                if (a.hasChildren) {
                    current.addClass("hasChildren");
                }
                if (a.children && a.children.length) {
                    $.each(a.children, function(i, value) {
                        createNode(value, [branch]);
                    });
                }
            }
        }
        $.getJSON(settings.url, { root: root }, function(json) {
            $("#tree-loading").remove();

            if (json.code != 1) {
                alert(json.message);
                return;
            }

            $.each(json.data, function(i, value) {
                createNode(value, [child]);
            });

            $(container).treeview({
                add: child
            });


            if (settings.sortable) {
                $(container).sortable({
                    delay: 300,
                    helper: "clone",
                    placeholder: "ui-state-highlight",
                    start: function(event, ui) {
                        $(ui.item).show().css({
                            'opacity': 0.5
                        });
                    },
                    stop: function(event, ui) {
                        $(ui.item).css({
                            'opacity': 1
                        });
                        var update_sortnum = function(ui) {
                            var ul = ui.item.parent();
                            var sortnum = new Array();
                            $("input.sortnum", ul).each(function(i) {
                                $(this).val(i);
                                var cid = $(this).attr("cid");
                                sortnum.push(cid);
                            });
                            if (settings.updateApi) {
                                $.post(settings.updateApi, {
                                    sortnum: sortnum
                                });
                            }
                        }
                        update_sortnum(ui);
                    }
                }).disableSelection();
            }
        });
    }

    var proxied = $.fn.treeview;
    $.fn.treeview = function(settings) {
        if (!settings.url) {
            return proxied.apply(this, arguments);
        }
        var container = this;
        load(settings, 0, this, container);
        var userToggle = settings.toggle;
        var a = proxied.call(this, $.extend({}, settings, {
            collapsed: settings.collapsed,
            toggle: function() {
                var $this = $(this);
                if ($this.hasClass("hasChildren")) {
                    var childList = $this.removeClass("hasChildren").find("ul");
                    childList.empty();
                    childList.html('<p id="tree-loading"><img src="./assets/img/ajax_loader.gif" /></p>')
                    load(settings, this.id, childList, container);
                }
                if (userToggle) {
                    userToggle.apply(this, arguments);
                }
            }
        }));
        $('[data-toggle="tooltip"]:not(.js-tooltip-enabled)', container).each((index, element) => {
            console.log(aaa);
            let el = $(element);
            el.addClass('js-tooltip-enabled').tooltip();
        });
        return a;
    };

})(jQuery);