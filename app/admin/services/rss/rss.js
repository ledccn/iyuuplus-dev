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
        })

        layui.use(['tag', 'element'], function() {
            let $ = layui.jquery,
                tag = layui.tag; //Tag的切换功能，切换事件监听等，需要依赖tag模块

            tag.render("tag_selector", {
                skin: 'layui-btn layui-btn-primary layui-btn-sm layui-btn-radius', //标签样式
                tagText: '<i class="layui-icon layui-icon-add-1"></i>添加' //标签添加按钮提示文本
            });

            tag.on('click(tag_selector)', function(data) {
                console.log('点击');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
            });

            tag.on('add(tag_selector)', function(data) {
                console.log('新增');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
                console.log(data.othis); //得到新增的DOM对象
                //return false; //返回false 取消新增操作； 同from表达提交事件。
            });

            tag.on('delete(tag_selector)', function(data) {
                console.log('删除');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
            });

            tag.render("tag_filter", {
                skin: 'layui-btn tag-item-normal layui-btn-primary layui-btn-sm layui-btn-radius', //标签样式
                tagText: '<i class="layui-icon layui-icon-add-1"></i>添加' //标签添加按钮提示文本
            });

            tag.on('click(tag_filter)', function(data) {
                console.log('点击');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
            });

            tag.on('add(tag_filter)', function(data) {
                console.log('新增');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
                console.log(data.othis); //得到新增的DOM对象
                //return false; //返回false 取消新增操作； 同from表达提交事件。
            });

            tag.on('delete(tag_filter)', function(data) {
                console.log('删除');
                console.log(this); //当前Tag标签所在的原始DOM元素
                console.log(data.index); //得到当前Tag的所在下标
                console.log(data.elem); //得到当前的Tag大容器
            });
        });
    }
);