// 自动转移做种客户端
update_render_callable.push(
    function () {
        layui.use(["jquery", "xmSelect", "popup", "laytpl"], function () {
            //let laytpl = layui.laytpl;
            // 设置来源下载器 目标下载器
            layui.$.ajax({
                url: "/admin/client/select?format=select&enabled=1&limit=1000",
                dataType: "json",
                success: function (res) {
                    if (res.code) {
                        layui.popup.failure(res.msg);
                        return;
                    }

                    let value = layui.$("#parameter").attr("value");
                    let parameter = value ? JSON.parse(value) : [];
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
                        data: res.data,
                        model: {"icon": "hidden", "label": {"type": "text"}},
                        clickClose: true,
                        radio: true,
                        on: function(data){
                            //arr:  当前多选已选中的数据
                            let arr = data.arr;
                            //change, 此次选择变化的数据,数组
                            let change = data.change;
                            //isAdd, 此次操作是新增还是删除
                            let isAdd = data.isAdd;
                            if (isAdd) {
                                fromClientId = change.shift();
                                xmFromSelect.update({disabled: true});
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
                        data: res.data,
                        model: {"icon": "hidden", "label": {"type": "text"}},
                        clickClose: true,
                        radio: true,
                        disabled: true,
                        on: function(data){
                            //arr:  当前多选已选中的数据
                            let arr = data.arr;
                            //change, 此次选择变化的数据,数组
                            let change = data.change;
                            //isAdd, 此次操作是新增还是删除
                            let isAdd = data.isAdd;
                            if (isAdd) {
                                toClientId = change.shift();
                                if (fromClientId === toClientId) {
                                    layer.msg('来源下载器与目标下载器，不能相同');
                                    return [];
                                }
                                xmToSelect.update({disabled: true});
                            }
                        },
                    });
                }
            });
        });
    }
);
