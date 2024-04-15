//嵌入JavaScript
layui.use(["form", "layer"], function () {
    let form = layui.form;
    let layer = layui.layer;
    form.on('checkbox(url_join-checkbox-filter)', function (data) {
        let elem = data.elem;   // 获得 checkbox 原始 DOM 对象
        let checked = elem.checked; // 获得 checkbox 选中状态
        let value = elem.value; // 获得 checkbox 值
        let that = data.othis;  // 获得 checkbox 元素被替换后的 jQuery 对象

        layer.msg(elem.getAttribute('title') + ' 状态: ' + elem.checked);
    });
});
