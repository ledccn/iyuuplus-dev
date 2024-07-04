// 自动转移做种客户端
update_render_callable.push(
    function () {
        layui.use(["jquery", "xmSelect", "popup"], function () {
            let parameter = init_parameter()

            /**
             * 设置来源下载器 目标下载器
             */
            layui.$.ajax({
                url: "/admin/client/select?format=select&enabled=1&limit=1000",
                dataType: "json",
                success: function (res) {
                    if (res.code) {
                        layui.popup.failure(res.msg);
                        return;
                    }

                    const original = JSON.stringify(res.data);
                    const result = res.data;
                    let initFormValue = parameter && parameter['from_clients'] ? [parameter['from_clients']] : [];
                    let initToValue = parameter && parameter['to_clients'] ? [parameter['to_clients']] : [];
                    let fromClientId = null, toClientId = null;

                    // 来源下载器
                    let xmFromSelect = layui.xmSelect.render({
                        el: "#from_clients",
                        name: "parameter[from_clients]",
                        tips: '请选择来源下载器',
                        layVerify: 'required',
                        layVerType: 'tips',
                        layReqText: '来源下载器必填',
                        initValue: initFormValue,
                        filterable: true,
                        data: JSON.parse(original),
                        //model: {"icon": "hidden", "label": {"type": "text"}},
                        clickClose: true,
                        radio: true,
                        on: function (data) {
                            //arr:  当前多选已选中的数据
                            let arr = data.arr;
                            //change, 此次选择变化的数据,数组
                            let change = data.change;
                            //isAdd, 此次操作是新增还是删除
                            let isAdd = data.isAdd;
                            if (isAdd) {
                                fromClientId = change.shift();
                                //xmFromSelect.update({disabled: true});
                                result.forEach((item, index) => {
                                    item.disabled = fromClientId.value === item.value;
                                });
                                xmToSelect.update({disabled: false});
                            }
                            xmToSelect.setValue([]);
                        },
                    });

                    // 目标下载器
                    let xmToSelect = layui.xmSelect.render({
                        el: "#to_clients",
                        name: "parameter[to_clients]",
                        tips: '请选择目标下载器',
                        layVerify: 'required',
                        layVerType: 'tips',
                        layReqText: '目标下载器必填',
                        initValue: initToValue,
                        filterable: true,
                        data: result,
                        //model: {"icon": "hidden", "label": {"type": "text"}},
                        clickClose: true,
                        radio: true,
                        disabled: true,
                        on: function (data) {
                            //arr:  当前多选已选中的数据
                            let arr = data.arr;
                            //change, 此次选择变化的数据,数组
                            let change = data.change;
                            //isAdd, 此次操作是新增还是删除
                            let isAdd = data.isAdd;
                            if (isAdd) {
                                toClientId = change.shift();
                                if (fromClientId.value.toString() === toClientId.value.toString()) {
                                    layer.msg('来源下载器与目标下载器，不能相同');
                                    return [];
                                }
                                xmToSelect.update({disabled: true});
                            }
                        },
                    });
                }
            });

            /**
             * 路径过滤器 路径选择器
             */
            layui.use(["jquery", "xmSelect", "popup"], function () {
                layui.$.ajax({
                    url: "/admin/folder/select?format=select&limit=1000",
                    dataType: "json",
                    success: function (res) {
                        // 路径过滤器
                        let path_filter = layui.$("#path_filter").attr("value");
                        let initPathFilterValue = path_filter ? path_filter.split(",") : [];
                        layui.xmSelect.render({
                            el: "#path_filter",
                            name: "parameter[path_filter]",
                            initValue: initPathFilterValue,
                            filterable: true,
                            tips: '请选择排除目录',
                            data: res.data,
                            //model: {"icon": "hidden", "label": {"type": "text"}},
                        });

                        // 路径选择器
                        let path_selector = layui.$("#path_selector").attr("value");
                        let initPathSelectorValue = path_selector ? path_selector.split(",") : [];
                        layui.xmSelect.render({
                            el: "#path_selector",
                            name: "parameter[path_selector]",
                            initValue: initPathSelectorValue,
                            filterable: true,
                            data: res.data,
                            //model: {"icon": "hidden", "label": {"type": "text"}},
                        });
                        if (res.code) {
                            layui.popup.failure(res.msg);
                        }
                    }
                });
            });
        });
    }
);
