// 自动转移做种客户端
update_render_callable.push(
    function () {
        layui.use(["jquery", "xmSelect", "popup", "laytpl"], function () {
            let laytpl = layui.laytpl;

            /**
             * 初始化元素的属性值
             * @param {string} selector
             * @param {string|int} value
             */
            function init_attr_value(selector, value) {
                let obj = $(selector);
                if (typeof obj[0] === "undefined" || !obj[0].nodeName) return;

                console.log('初始化' + obj[0].nodeName.toLowerCase() + '属性值', selector, value)
                if (obj[0].nodeName.toLowerCase() === "textarea") {
                    obj.val(value);
                } else {
                    if (1 === obj.length) {
                        obj.attr("value", value);
                        obj[0].value = value;
                    }
                    if ('checkbox' === obj.attr('type')) {
                        obj.attr('checked', true);
                    } else if ('radio' === obj.attr('type')) {
                        obj.each(function () {
                            $(this).prop('checked', $(this).val() === value);
                        });
                    } else if (obj[0].nodeName.toLowerCase() === "select") {
                        obj.find('option[value="' + value + '"]').attr('selected', true);
                    }
                }
            }

            let value = layui.$("#parameter").attr("value");
            let parameter = value ? JSON.parse(value) : [];
            if (parameter) {
                //console.log('自动转移做种调用参数', parameter)
                layui.each(parameter, function (kk, vv) {
                    // 基础类型(输入框、选择框、单选框)
                    init_attr_value('*[name="parameter[' + kk + ']"]', vv)
                });
                layui.form.render();
            }

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
