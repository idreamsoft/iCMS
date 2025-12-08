define(["admincp"], function (admincp) {
    return function () {

        // 创建 Web Worker
        const worker = new Worker('/assets/js/app/libs/worker.js');

        // 当前处理的 URL 索引
        let currentIndex = 0;
        let api = iCMS.url('/spider/auto', false);

        // 向 Worker 发送第一个 URL
        postMessage(currentIndex);

        function postMessage(currentIndex) {
            let data = tasks[currentIndex];
            data['CSRF_TOKEN'] = $APP.CSRF_TOKEN;
            worker.postMessage({ api, data });
        }
        // 监听 Worker 返回的消息
        worker.addEventListener('message', (event) => {
            const {state, data, error } = event.data;
            if (error) {
                iCMS.ui.alert(error);
                return;
            } else {
                if(state=='success'){
                    iCMS.notify.success(data.text);
                }else if(state=='error'){
                    iCMS.notify.error(data.text);
                }else{
                    iCMS.notify.info(data.text);
                }
            }
    
            // 处理下一个 URL
            currentIndex++;
            if (currentIndex <= tasks.length) {
                postMessage(currentIndex);
                // 发送下一个 URL
            } else {
                iCMS.notify.success('所有任务完成')
                worker.terminate(); // 结束 Worker
                top.Layer.close(top.window.ui_modal_idx);
            }
        });

        // 监听 Worker 的错误
        worker.addEventListener('error', (error) => {
            console.error('Worker error:', error);
        });
    }

});