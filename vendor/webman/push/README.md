# push
webman push plugin   
具体使用请看：https://www.workerman.net/plugin/2

## js文件说明

---

```sh
  push-uniapp.js #适用于uniapp项目内使用
  push-vue.js #适用于vue项目内使用
  push.js #适用于直接引入js常规项目内使用
```

### push-vue.js 使用说明

---

1、将文件 push-vue.js复制到项目目录下，如：src/utils/push-vue.js

2、在vue页面内引入
```js

<script lang="ts" setup>
import {  onMounted } from 'vue'
import { Push } from '../utils/push-vue'

onMounted(() => {
  console.log('组件已经挂载') 

  //实例化webman-push

  // 建立连接
  var connection = new Push({
    url: 'ws://127.0.0.1:3131', // websocket地址
    app_key: '<app_key，在config/plugin/webman/push/app.php里获取>',
    auth: '/plugin/webman/push/auth' // 订阅鉴权(仅限于私有频道)
  });

  // 假设用户uid为1
  var uid = 1;
  // 浏览器监听user-1频道的消息，也就是用户uid为1的用户消息
  var user_channel = connection.subscribe('user-' + uid);

  // 当user-1频道有message事件的消息时
  user_channel.on('message', function (data) {
    // data里是消息内容
    console.log(data);
  });
  // 当user-1频道有friendApply事件时消息时
  user_channel.on('friendApply', function (data) {
    // data里是好友申请相关信息
    console.log(data);
  });

  // 假设群组id为2
  var group_id = 2;
  // 浏览器监听group-2频道的消息，也就是监听群组2的群消息
  var group_channel = connection.subscribe('group-' + group_id);
  // 当群组2有message消息事件时
  group_channel.on('message', function (data) {
    // data里是消息内容
    console.log(data);
  });


})

</script>

```
 


