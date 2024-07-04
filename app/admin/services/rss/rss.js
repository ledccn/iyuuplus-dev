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
        });
    }
);