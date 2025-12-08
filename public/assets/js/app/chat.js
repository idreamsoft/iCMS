define(["cms", "vue"], function (cms, Vue) {
    var pollingId, lastId;
    iCMS.ui.addcss('/assets/css/public/chat');
    const factory = {
        vm: null,
        // polling: function (friend, lastId) {
        //     pollingId = window.setInterval(function () {
        //         iCMS.api.spost('message/room', { friend, lastId }, function (json) {
        //             lastId = json.data.lastId;
        //             if (json.data.rows.length > 0) {
        //                 factory.vm.messages = factory.vm.messages.concat(json.data.rows);
        //                 factory.vm.scrollToBottom();
        //                 const lastMsgEl = iCMS.$(`chat:lastMsg:${friend}`);
        //                 lastMsgEl.text(json.data.rows[0].content);
        //             }
        //         })
        //     }, 1000);
        // },
        // 开始轮询新消息
        polling(friend) {
            if (!friend || typeof friend !== 'number') {
                console.warn('polling: friend 参数无效');
                return;
            }

            this.stopPolling(); // 防止重复启动

            pollingId = window.setInterval(() => {
                iCMS.api.spost('message/room', { friend, lastId }, function (json) {
                    if (!json || !json.data) return;

                    const { rows, lastId: newLastId } = json.data;

                    if (rows.length > 0) {
                        lastId = newLastId;

                        if (factory.vm) {
                            factory.vm.messages = factory.vm.messages.concat(rows);
                            factory.vm.scrollToBottom();

                            const lastMsgEl = iCMS.$(`chat:lastMsg:${friend}`);
                            if (lastMsgEl.length) {
                                lastMsgEl.text(rows[rows.length - 1].content);
                            }
                        }
                    }
                });
            }, 2000); // 可根据需求调整频率
        },

        stopPolling() {
            if (pollingId) {
                clearInterval(pollingId);
                pollingId = null;
            }
        },

        // 初始化聊天界面
        wrapper({ $el }) {
            const firstRoom = iCMS.$("cms:chat:room").first();
            if (firstRoom.length) {
                firstRoom.trigger('click');
            }

        },
        room: function ({ $el, el }) {
            $('.chat-room', $el.parent()).removeClass("active");
            $el.addClass("active");

            const friend = $el.data('userid');
            if (!friend) {
                console.error('无法获取 friend ID');
                return;
            }
            this.stopPolling();

            iCMS.api.spost('message/room', { friend }, function (json) {
                if (!json || !json.data) {
                    console.error('获取聊天室数据失败');
                    return;
                }
                lastId = json.data.lastId;
                var room = json.data.room;
                var messages = json.data.rows;
                var sentForm = iCMS.$("cms:message:send");

                iCMS.$("cms@chat:room:name").html(room.name || '');
                $('[name="userid"]', sentForm).val(room.idHash);
                $('[name="username"]', sentForm).val(room.nickname);

                // $('#chat-messages').css("visibility","visible");
                if (factory.vm) {
                    factory.vm.room = room;
                    factory.vm.messages = messages;
                    factory.vm.scrollToBottom();
                } else {
                    factory.vm = new Vue({
                        el: '#chat-messages',
                        methods: {
                            format_date: function (date, format) {
                                return format_date(date, format);
                            },
                            scrollToBottom: function () {
                                this.$nextTick(() => {
                                    // var container = this.$el.querySelector("#chat-messages");
                                    var container = this.$el;
                                    // if (container.scrollHeight - container.scrollTop <= container.clientHeight + 50) {
                                        container.scrollTop = container.scrollHeight;
                                    // }
                                    // console.log(container.scrollTop, container.scrollHeight);
                                });
                            }
                        },
                        mounted: function () {
                            this.$el.style.visibility = "visible";
                            // this.$el.style.display = "block";

                            this.scrollToBottom();
                        },
                        data: {
                            room, messages
                        }
                    })
                }
                factory.polling(friend);
            });
        }
    }
    return factory;
});