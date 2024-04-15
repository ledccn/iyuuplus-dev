// webman命令javascript

/**
 * 注册一个回调
 */
update_render_callable.push(
    function () {
        render_target();
    }
);

/**
 * 渲染命令名称
 */
function render_target() {
    let targetValue = layui.$("#target").attr("value");
    let targetSelect = layui.xmSelect.render({
        el: "#target",
        name: "target",
        initValue: targetValue ? targetValue.split(",") : [],
        filterable: true,
        data: namespaces,
        template({item, sels, name, value}) {
            return item.name + '<span style="position: absolute; right: 10px; color: #c2c2c2 !important;">' + item.description + '</span>'
        },
        value: "",
        model: {"icon": "hidden", "label": {"type": "text"}},
        clickClose: true,
        radio: true,
        disabled: false,
        done: function () {

        },
        on: function (data) {
            //arr:  当前多选已选中的数据
            let arr = data.arr;
            //change, 此次选择变化的数据,数组
            let change = data.change;
            //isAdd, 此次操作是新增还是删除
            let isAdd = data.isAdd;
            if (isAdd) {
                let item = change.shift();
                //console.log(item)
                writeDescriptionUsage(item.description, item.usage.join("\r\n"));
            } else {
                writeDescriptionUsage();
            }
            layui.$('input[name="parameter"]').val('');
        },
    });

    let change = targetSelect.getValue();
    if (change) {
        let item = change.shift();
        writeDescriptionUsage(item.description, item.usage.join("\r\n"));
    }
}

/**
 * 写提示文本
 * @param {string} description
 * @param {string} usage
 */
function writeDescriptionUsage(description = '', usage = '') {
    document.getElementById('description').innerText = description;
    document.getElementById('usage').innerText = usage;
}
