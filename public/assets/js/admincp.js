define(["jq-lazyload","jq-appear"],function () {
    "use strict";
    console.log("cms.js is loaded");

    // 将常量提升到 factory 作用域顶部
    const FRAME_PARAM = "frame=main";
    const MAIN_IFRAME_ID = "main-iframe";
    const PAGE_FOOTER_ID = "#page-footer";
    const INVALID_URLS = ["javascript:", "#", "###"];
    const TAB_ID_PREFIX = "-tab";
    const PANE_ID_PREFIX = "-pane";
    const IFRAME_ID_PREFIX = "-iframe";

    const factory = {
        // hashTag: 'main',//框架模式
        init: function () {
            console.log("admincp.init");
            One.init();
            // this.getMenu();
            this.activeMenu();
            // console.log(window.location);
            this.startFrame(); // 调用优化后的 startFrame
        },
        observer: function (find, funct, not) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.addedNodes.length > 0) {
                        var nodes = $(find, mutation.addedNodes);
                        if (not) nodes = nodes.not(not);
                        if (nodes.length > 0) {
                            funct(nodes, find);
                        }
                    }
                });
            });
            observer.observe(document, { childList: true, subtree: true, attributes: true });
        },

        // 优化后的框架模式2
        startFrame: function () {

            // URL参数处理工具
            const urlUtils = {
                // 移除指定参数
                removeParam: function (url, param) {
                    if (!url || !param) return url;
                    const regex = new RegExp(`[?&]${param.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(&|$)`);
                    return url.replace(regex, function (match, p1) {
                        return p1 === '&' ? '&' : '';
                    }).replace(/[?&]$/, '');
                },
                // 添加参数
                addParam: function (url, param) {
                    if (!url || !param) return url;
                    if (url.includes(param)) return url;
                    const separator = url.includes('?') ? '&' : '?';
                    return url + separator + param;
                },
                // 检查URL是否有效
                isValidUrl: function (url) {
                    if (!url || typeof url !== 'string') return false;
                    return !INVALID_URLS.some(invalid => url.startsWith(invalid));
                }
            };

            // --- 核心逻辑函数 ---

            /**
             * 处理链接点击事件
             * @param {Event} event - 点击事件对象
             * @returns {string|null} 处理后的 URL 或 null
             */
            function handleLinkClick(event) {
                event.preventDefault();
                const $element = $(this);
                let url = $element.attr("href");

                // 验证URL有效性
                if (!urlUtils.isValidUrl(url)) {
                    console.warn('Invalid URL detected:', url);
                    return null;
                }

                // 清理URL（移除已存在的frame参数）
                url = urlUtils.removeParam(url, FRAME_PARAM);
                // 为历史记录准备URL（添加frame参数）
                const historyUrl = urlUtils.addParam(url, FRAME_PARAM);

                // 更新浏览器历史记录
                if (top.history && top.history.pushState) {
                    top.history.pushState({}, document.title, historyUrl);
                }
                return url;
            }

            /**
             * 创建或激活一个 iframe 标签页
             * @param {string} src - iframe 的源 URL
             * @param {string} title - 标签页标题
             * @param {string} iconClass - 标签页图标类名
             */
            function createIframe(src, title, iconClass) {
                if (!src) return;

                const md5Hash = md5(src);
                const tabId = md5Hash + TAB_ID_PREFIX;
                const tabPaneId = md5Hash + PANE_ID_PREFIX;
                const iframeId = md5Hash + IFRAME_ID_PREFIX;

                // 缓存 jQuery 对象
                const $mainTabsNav = $('#main-tabs-nav');
                const $mainTabsContent = $('#main-tabs-content');

                // 1. 检查并创建/激活 Tab
                let $existingTab = $(`#${tabId}`);
                if ($existingTab.length === 0) {
                    const navItemHtml = `
                        <li class="nav-item" role="presentation">
                            <button class="nav-link border-t" id="${tabId}" data-bs-toggle="tab" data-bs-target="#${tabPaneId}" type="button" role="tab" aria-controls="${tabPaneId}" aria-selected="false">
                                <span class="tab-title"> <i class="${iconClass}"></i> ${title}</span>
                                <span class="tab-close" title="关闭标签页"><i class="fa fa-fw fa-times"></i></span>
                            </button>
                        </li>
                    `;
                    const $navItem = $(navItemHtml);

                    // 插入到 settings tab 之前，或追加到末尾
                    const $settingsTab = $('#main-settings-tab').closest('li');
                    if ($settingsTab.length > 0) {
                        $navItem.insertBefore($settingsTab);
                    } else {
                        $mainTabsNav.append($navItem);
                    }
                    $existingTab = $(`#${tabId}`); // 更新 jQuery 对象
                }

                // 2. 检查并创建/激活 Tab Pane 和 Iframe
                let $existingPane = $(`#${tabPaneId}`);
                if ($existingPane.length === 0) {
                    // 隐藏其他 pane
                    $mainTabsContent.find('.tab-pane').removeClass('active show');

                    const tabPaneHtml = `
                        <div class="tab-pane fade active show" id="${tabPaneId}" role="tabpanel" aria-labelledby="${tabId}">
                            <iframe id="${iframeId}" name="${iframeId}" src="about:blank" class="mainIframe w-100 h-100 border-0"></iframe>
                        </div>
                    `;
                    $mainTabsContent.append(tabPaneHtml);
                    $existingPane = $(`#${tabPaneId}`); // 更新 jQuery 对象
                } else {
                    // 如果 pane 已存在，确保它被激活
                    $mainTabsContent.find('.tab-pane').removeClass('active show');
                    $existingPane.addClass('active show');
                }


                // 3. 设置 iframe src 并绑定 load 事件
                const $iframe = $(`#${iframeId}`);
                if ($iframe.length > 0) {
                    $iframe.attr('src', src); // 设置 src

                    // 激活对应的 tab 按钮
                    if ($existingTab.length > 0) {
                        // 使用 Bootstrap 的 tab 方法
                        const tabInstance = new bootstrap.Tab($existingTab[0]);
                        tabInstance.show();
                    }

                    // 移除旧的监听器并绑定新的 load 事件监听器
                    $iframe.off('load.historyUpdate').on('load.historyUpdate', function () {
                        try {
                            const iframeWindow = this.contentWindow;
                            // 更安全地检查 iframeWindow 和 location
                            if (iframeWindow && iframeWindow.location && iframeWindow.location.href) {
                                const currentSrc = iframeWindow.location.href;
                                // 检查src是否真的发生了变化 (避免初始加载也触发)
                                if (currentSrc && currentSrc !== 'about:blank' && currentSrc !== src) {
                                    // 为新的URL添加frame参数
                                    const historyUrl = urlUtils.addParam(currentSrc, FRAME_PARAM);
                                    // 更新浏览器历史记录
                                    if (top.history && top.history.pushState) {
                                        top.history.pushState({}, document.title, historyUrl);
                                    }
                                    console.log('Iframe src changed, history updated:', historyUrl);
                                }
                            }
                        } catch (e) {
                            // 跨域访问限制，无法获取iframe内容
                            // console.warn('Cannot access iframe content due to cross-origin restrictions:', e.message);
                            // 可以选择在这里处理跨域情况，例如不更新历史记录
                        }
                    });
                }

                // 4. 移除页脚
                $(PAGE_FOOTER_ID).remove();
            }

            /**
             * 关闭指定的标签页
             * @param {Event} event - 点击关闭按钮的事件
             * @param {string} tabId - 要关闭的标签页按钮 ID
             */
            function closeTab(event, tabId) {
                event.stopPropagation();
                event.preventDefault();

                const tabButton = document.getElementById(tabId);
                if (!tabButton) return; // 安全检查

                const tabItem = tabButton.closest('.nav-item');
                const tabPaneId = tabButton.getAttribute('data-bs-target').substring(1); // 安全获取
                const tabPane = tabPaneId ? document.getElementById(tabPaneId) : null;

                // 检查是否是最后一个可关闭的标签页
                const allTabs = document.querySelectorAll('#main-tabs .nav-item:not(#main-settings-tab)');
                if (allTabs.length <= 1) {
                    alert('至少需要保留一个标签页');
                    return;
                }

                const isActive = tabButton.classList.contains('active');
                let nextTabToActivate = null;

                // 如果关闭的是当前激活的标签页，需要激活其他标签页
                if (isActive) {
                    // 查找下一个或上一个标签页
                    let nextTab = tabItem.nextElementSibling;
                    if (!nextTab || nextTab.id === 'main-settings-tab') {
                        nextTab = tabItem.previousElementSibling;
                    }
                    nextTabToActivate = nextTab ? nextTab.querySelector('.nav-link') : null;
                }

                // 移除标签页和对应的内容面板
                if (tabItem) tabItem.remove();
                if (tabPane) tabPane.remove();

                // 激活下一个标签页
                if (nextTabToActivate) {
                    // 使用 Bootstrap 的 tab 方法激活
                    const tabInstance = new bootstrap.Tab(nextTabToActivate);
                    tabInstance.show();
                    // 或者简单地触发点击: nextTabToActivate.click();
                }
            }

            // --- 事件绑定 ---

            /**
             * 初始化标签页相关的事件绑定
             */
            function initializeTabHandling() {
                // 绑定标签页关闭事件 (使用命名空间)
                $(document).off('click.cms.tabClose').on('click.cms.tabClose', 'span.tab-close', function (event) {
                    const tabId = this.closest('.nav-link').id;
                    if (tabId) {
                        closeTab(event, tabId);
                    }
                });

                // 绑定指向主 iframe 的链接点击事件 (排除已标记 'i' 的)
                $(document).off('click.cms.iframeLink').on('click.cms.iframeLink', `[target="${MAIN_IFRAME_ID}"]:not([i])`, function (event) {
                    const url = handleLinkClick.call(this, event);
                    if (url) {
                        const $this = $(this);
                        const title = $this.text().trim() || '新标签页'; // 提供默认标题
                        const iconClass = $this.find('i').first().attr('class') || 'si si-link'; // 提供默认图标
                        createIframe(url, title, iconClass);
                    }
                });
            }

            // --- 初始化 ---
            initializeTabHandling(); // 启动事件监听
        },
        finish: function () {
            $("img.lazyload").lazyload({
                effect: "fadeIn",
            });
            One.helpers('jq-appear');
            this.resizeIframe();
            console.log("admincp.finish");
        },
        resizeIframe: function () {
            window.addEventListener('message', function (event) {
                if (event.data.type === 'resizeIframe') {
                    var iname = event.data.iframeName;
                    var iframe = document.getElementsByName(iname)[0];
                    if (iframe) {
                        // iframe.style.height = event.data.height + 'px';
                        var index = Layer.getFrameIndex(iname);
                        // Layer.iframeAuto(index);
                        Layer.resize(index);
                    }
                }
            });
            //判断当前页是否在一个name='layui-layer-iframe'的框架中,如果是,则设置框架的高度为当前页的高度
            if (window.name.includes('layui-layer-iframe')) {
                // 获取当前页面body的高度
                var bodyHeight = $("body").height();
                if (window.parent && window.parent !== window) {
                    // 通过postMessage方式通知父窗口调整高度
                    window.parent.postMessage({
                        type: 'resizeIframe',
                        height: bodyHeight,
                        iframeName: window.name
                    }, '*');
                }
            }
        },
        // getMenu: function () {
        //     iCMS.get("menu/nav", function(ret) {
        //         $("#Menu-wrap").html(ret.data.html);
        //         One._uiHandleSidebars();
        //     });
        // },
        activeMenu: function () {
            var $link = $APP.DO_URL || false;
            $("li.nav-main-item:not(.force_open)", "ul.nav-main").removeClass("open");
            if ($APP.name) {
                var $li = $("li#Menu-" + $APP.name).addClass("open");
                openNav($li);
            }
            activeNav(window.location.pathname);
            function activeNav($href) {
                var $a = $(
                    'a.nav-main-link[href="' + $href + '"]',
                    "ul.nav-main"
                ).addClass("active");
                openNav($a);
            }
            if ($link !== "javascript:;") {
                // Handle non-javascript links if needed
            }
            function openNav($element) {
                var $wrap = $element.closest("ul.nav-main-submenu:not(.open)");
                if ($wrap.length) {
                    $wrap.addClass("open").parent().addClass("open");
                    openNav($wrap);
                }
            }
        },
    };
    return factory;
});