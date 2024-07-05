// RSS订阅
update_render_callable.push(
    function () {
        layui.use(["jquery", "xmSelect", "popup"], function () {
            let parameter = init_parameter()

            // 下载器 client_id
            layui.$.ajax({
                url: "/admin/client/select?format=select&enabled=1&limit=1000",
                dataType: "json",
                success: function (res) {
                    if (res.code) {
                        return layui.popup.failure(res.msg);
                    }

                    let initValue = parameter && parameter['client_id'] ? [parameter['client_id']] : [];
                    layui.xmSelect.render({
                        el: "#client_id",
                        name: "parameter[client_id]",
                        tips: '请选择下载器',
                        layVerify: 'required',
                        layVerType: 'tips',
                        layReqText: '下载器必填',
                        initValue: initValue,
                        filterable: true,
                        data: res.data,
                        model: {"icon": "hidden", "label": {"type": "text"}},
                        clickClose: true,
                        radio: true,
                    });

                }
            });

            let text_selector = parameter && parameter['text_selector'] ? [parameter['text_selector']] : [];
            layui.xmSelect.render({
                el: '#text_selector',
                name: "parameter[text_selector]",
                tips: '包含关键字',
                searchTips: '请输入包含关键字',
                initValue: text_selector,
                filterable: true,
                clickClose: true,
                max: 5,
                create: function(val, arr){
                    if(arr.length === 0){
                        return {
                            name: val,
                            value: val
                        }
                    }
                },
                data: text_selector ? text_selector.map((value) => ({name: value, value: value})) : []
            });

            let text_filter = parameter && parameter['text_filter'] ? [parameter['text_filter']] : [];
            layui.xmSelect.render({
                el: '#text_filter',
                name: "parameter[text_filter]",
                tips: '排除关键字',
                searchTips: '请输入排除关键字',
                initValue: text_filter,
                filterable: true,
                clickClose: true,
                max: 5,
                create: function(val, arr){
                    if(arr.length === 0){
                        return {
                            name: val,
                            value: val
                        }
                    }
                },
                data: text_filter ? text_filter.map((value) => ({name: value, value: value})) : []
            });
        })
    }
);
