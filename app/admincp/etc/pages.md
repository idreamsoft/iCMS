 Available classes for #page-container:

    GENERIC

      'remember-theme'                            Remembers active color theme and dark mode between pages using localStorage when set through
                                                  - Theme helper buttons [data-toggle="theme"],
                                                  - Layout helper buttons [data-toggle="layout" data-action="dark_mode_[on/off/toggle]"]
                                                  - ..and/or One.layout('dark_mode_[on/off/toggle]')

    SIDEBAR & SIDE OVERLAY

      'sidebar-r'                                 Right Sidebar and left Side Overlay (default is left Sidebar and right Side Overlay)
      'sidebar-mini'                              Mini hoverable Sidebar (screen width > 991px)
      'sidebar-o'                                 Visible Sidebar by default (screen width > 991px)
      'sidebar-o-xs'                              Visible Sidebar by default (screen width < 992px)
      'sidebar-dark'                              Dark themed sidebar

      'side-overlay-hover'                        Hoverable Side Overlay (screen width > 991px)
      'side-overlay-o'                            Visible Side Overlay by default

      'enable-page-overlay'                       Enables a visible clickable Page Overlay (closes Side Overlay on click) when Side Overlay opens

      'side-scroll'                               Enables custom scrolling on Sidebar and Side Overlay instead of native scrolling (screen width > 991px)

    HEADER

      ''                                          Static Header if no class is added
      'page-header-fixed'                         Fixed Header

    HEADER STYLE

      ''                                          Light themed Header
      'page-header-dark'                          Dark themed Header

    MAIN CONTENT LAYOUT

      ''                                          Full width Main Content if no class is added
      'main-content-boxed'                        Full width Main Content with a specific maximum width (screen width > 1200px)
      'main-content-narrow'                       Full width Main Content with a percentage width (screen width > 1200px)

    DARK MODE

      'sidebar-dark page-header-dark dark-mode'   Enable dark mode (light sidebar/header is not supported with dark mode)

{
    
    "side.overlay":null,    // 侧栏 留空默认关闭 side-overlay-hover 鼠标悬停 side-overlay-o 默认打开
    "side.scroll" :true,    //侧栏滚动条使用自定义样式
    "sidebar"     :true,    //是否显示侧边栏
    "sidebar.right"   :false,   //侧边栏右侧
    "sidebar.mini":false,    //侧边栏最小化
    "sidebar.dark":true,    //暗色侧边栏
    "page.overlay":true,
    "page.header" :true,    //是否显示页头
    "page.header.fixed" :false,    //页头 true固定 false滚动
    "page.header.dark" :false,    //暗色页头
    "page.common" :true,
    "modal.dialog":true,
    "page.footer" :true,
    "main.content":null,  //默认为空 main-content-boxed 最大100% main-content-narrow 最大95%
    "breadcrumb"  :true,  //是否显示 面包屑
    "page.classes":null,
    "head.begin":null,//在base/head模板前 加载head.begin模板
    "head.after":null,//在base/head模板后 加载head.begin模板
    "foot.begin":null,//在base/foot模板前 加载foot.begin模板
    "foot.after":null,//在base/foot模板后 加载foot.begin模板
    
}
