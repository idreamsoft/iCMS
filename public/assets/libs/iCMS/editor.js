/**
 * 编辑器工厂模块 - 负责编辑器的初始化和管理
 * @module EditorFactory
 */
define(function () {
    "use strict";

    const LOADING_CLASS = "js-editor-enabled";
    const TEXTAREA_FALLBACK_CLASS = "form-control js-editor-textarea";

    // --- 全局编辑器管理器 ---
    if (!window.iEditor) {
        window.iEditor = {
            ids: [],
            instances: new Map(),

            // 通用方法调用器
            _call(eid, method, defaultVal, ...args) {
                const inst = this.instances.get(eid);
                if (inst && typeof inst[method] === 'function') {
                    try { return inst[method](...args); } catch (e) { console.error(`[iEditor] ${method} 失败:`, e); }
                }
                return defaultVal;
            },

            // 遍历所有实例执行命令
            _execAll(cmd, successMsg) {
                for (const [, inst] of this.instances) {
                    if (inst?.execCommand) {
                        try { inst.execCommand(cmd); if (successMsg) iCMS.notify.success(successMsg); } 
                        catch (e) { console.error(`[iEditor] ${cmd} 失败:`, e); }
                    }
                }
            },

            get(eid) { return this.instances.get(eid); },

            getContent(eid) {
                if (eid) return this._call(eid, 'getContent', '');
                const result = {};
                for (const [id, inst] of this.instances) {
                    result[id] = inst?.getContent?.() ?? '';
                }
                return result;
            },

            setContent(eid, content, flag) { this._call(eid, 'setContent', undefined, content, flag); },
            hasContents(eid) { return this._call(eid, 'hasContents', false); },
            insPageBreak(eid) { return this._call(eid, 'insPageBreak', false); },
            delPageBreak(eid) { return this._call(eid, 'delPageBreak', false); },
            cleanup(eid) { return this._call(eid, 'cleanup', false); },

            autoSave() { this._execAll('set_auto_save_content', '内容保存草稿成功'); },
            restore() { this._execAll('auto_save_restore', '内容恢复草稿成功'); },

            destroy(eid) {
                this._call(eid, 'destroy', undefined);
                this.instances.delete(eid);
                this.ids = this.ids.filter(id => id !== eid);
            }
        };
    }

    // --- 编辑器工厂 ---
    const factory = {
        paths: {},
        loading: new Set(),

        loadConfig() {
            iCMS.api.spost("editor/load", (json) => {
                if (json?.data?.paths) this.paths = { ...this.paths, ...json.data.paths };
            });
        },

        init({ $el, el } = {}) {
            const $target = $el instanceof $ ? $el : el ? $(el) : null;
            if (!$target?.length) return;

            // 如果没有 id，自动生成一个
            let id = $target.attr('id');
            if (!id) {
                id = 'editor_' + Math.random().toString(36).slice(2, 11);
                $target.attr('id', id);
            }

            // 防止重复初始化
            if (this.loading.has(id) || $target.hasClass(LOADING_CLASS)) return;

            this._run($target, id);
        },

        _run($el, id) {
            this.loading.add(id);
            $el.addClass(LOADING_CLASS);

            const markdown = $el.data('markdown');
            const widget = markdown ? 'vditor' : ($el.data('widget') || 'ueditor');
            const boot = $el.data('boot') || 'admincp';
            const mod = widget + 'Boot';

            let path = this.paths[mod] || `${$APP.SCRIPT_NAME}/editor/assets/${widget}/boot.js`;
            if (!path.endsWith('.js')) path += '.js';

            const loadIdx = Layer.load(1);

            require([path],
                (bootstrap) => {
                    this.loading.delete(id);
                    Layer.close(loadIdx);

                    if (typeof bootstrap !== 'function') {
                        this._fallback($el, id);
                        return;
                    }

                    try {
                        bootstrap($el, boot, (eid, instance) => {
                            if (eid && instance) {
                                window.iEditor.instances.set(eid, instance);
                                if (!window.iEditor.ids.includes(eid)) window.iEditor.ids.push(eid);
                            } else {
                                this._fallback($el, id);
                            }
                        });
                    } catch (e) {
                        console.error('[EditorFactory] bootstrap error:', e);
                        this._fallback($el, id);
                    }
                },
                () => {
                    this.loading.delete(id);
                    Layer.close(loadIdx);
                    this._fallback($el, id);
                }
            );
        },

        _fallback($el, id) {
            $el.removeClass(LOADING_CLASS).addClass(TEXTAREA_FALLBACK_CLASS);
            console.info(`[EditorFactory] ${id} 降级为文本域`);
        }
    };

    factory.loadConfig();
    return factory;
});