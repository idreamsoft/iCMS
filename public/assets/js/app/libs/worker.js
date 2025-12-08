self.addEventListener('message', function (event) {
    let { api, data, ctype } = event.data;

    // console.log(api, data);
    // self.postMessage({ url: url, error: 'error' });
    // self.postMessage({ url: 'asd', data: ['data'] });
    // console.log(data);

    let options = {
        method: 'POST', // 指定请求方法为 POST
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded', // 设置请求头
            // 'Content-Type': 'application/json', // 设置请求头
        },
        // body: JSON.stringify(data), // 设置请求体
        // body: data, // 设置请求体
    }
    if (ctype == 'json') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    } else {
        // 将多维对象转换为 URLSearchParams
        const params = new URLSearchParams();
        // 递归处理对象属性
        function serializeParams(params, key, value) {
            if (value === null || value === undefined) {
                return; // 忽略 null 和 undefined
            }

            // 处理数组
            if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    serializeParams(params, `${key}[${index}]`, item);
                });
            }
            // 处理对象
            else if (typeof value === 'object') {
                for (const subKey in value) {
                    if (value.hasOwnProperty(subKey)) {
                        serializeParams(params, `${key}[${subKey}]`, value[subKey]);
                    }
                }
            }
            // 处理基本类型
            else {
                params.append(key, value);
            }
        }

        // 遍历顶层属性
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                serializeParams(params, key, data[key]);
            }
        }
        options.body = params.toString();
    }

    fetch(api, options).then(response => response.json())
        .then(data => {
            if (data.code === 1) {
                self.postMessage(data);
            } else {
                self.postMessage({ error: data.message });
            }
        })
        .catch(error => {
            self.postMessage({ error });
        });
});