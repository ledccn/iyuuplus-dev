/**
 * 初始化元素的属性值
 * @param {string} selector
 * @param {string|int} value
 */
function init_attr_value(selector, value) {
    let $ = layui.$;
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

/**
 * 初始化表单参数
 * @returns {any|*[]}
 */
function init_parameter() {
    let value = layui.$("#parameter").attr("value");
    let parameter = value ? JSON.parse(value) : [];
    if (parameter) {
        layui.each(parameter, function (kk, vv) {
            // 基础类型(输入框、选择框、单选框)
            init_attr_value('*[name="parameter[' + kk + ']"]', vv)
        });
        layui.form.render();
    }
    return parameter;
}
