- 从网页中抽取数据需要用XPath ( [XPath选择器教程](http://www.w3school.com.cn/xpath/index.asp) )
- 当然我们还可以使用CSS选择器 ( [CSS选择器教程](http://www.w3school.com.cn/cssref/css_selectors.asp) )
- 很多情况下都会用到正则表达式 ( [正则表达式教程](https://www.w3cschool.cn/regexp/) )

# selector类

`selector`是页面元素选择器类，下面介绍此类可以调用的方法

### `select($html, $selector, $selector_type = 'xpath')`

> @param $html 需筛选的网页内容
> @param $selector 选择器规则
> @param $selector_type 选择器类型: xpath、regex、css, 默认为xpath选择类型

栗子1:

通过xpath选择器提取网页内容的标题

```php
$html = requests::get("http://www.epooll.com/archives/806/");
$data = selector::select($html, "//div[contains(@class,'page-header')]//h1//a");
var_dump($data);
```

栗子2:

通过css选择器提取网页内容的标题

```php
$html = requests::get("http://www.epooll.com/archives/806/");
$data = selector::select($html, ".page-header > h1 > a", "css");
var_dump($data);
```

栗子3:

通过正则匹配提取网页内容的标题

```php
$html = requests::get("http://www.epooll.com/archives/806/");
$data = selector::select($html, '@<title>(.*?)</title>@', "regex");
var_dump($data);
```

### `remove($html, $selector, $selector_type = 'xpath')`

> @param $html 需过滤的网页内容
> @param $selector 选择器规则
> @param $selector_type 选择器类型: xpath、regex、css, 默认为xpath选择类型

举个例子:

```php
$html =<<<STR
    <div id="demo">
        aaa
        <span class="tt">bbb</span>
        <span>ccc</span>
        <p>ddd</p>
    </div>
STR;

// 获取id为demo的div内容
$html = selector::select($html, "//div[contains(@id,'demo')]");
// 在上面获取内容基础上，删除class为tt的span标签
$data = selector::remove($html, "//span[contains(@class,'tt')]");
print_r($data);
```

# 爬虫进阶开发——xpath选择器常见用法

俗话说，工欲上其事，必先利其器，学好xpath选择器，能极高的提升在爬虫的数据提取环节中的提取速度，下面我们来认识认识xpath。

**选取节点**

XPath 使用路径表达式在 XML 文档中选取节点。节点是通过沿着路径或者 step 来选取的。

**下面列出了最有用的路径表达式**

| 表达式      | 描述                            |
|:---------|:------------------------------|
| nodename | 选取此节点的所有子节点。                  |
| /        | 从根节点选取。                       |
| //       | 从匹配选择的当前节点选择文档中的节点，而不考虑它们的位置。 |
| .        | 选取当前节点。                       |
| ..       | 选取当前节点的父节点。                   |
| @        | 选取属性。                         |

**实例**

**1、精确查询**

```
$html =<<<STR
    <div id="demo">
        <span class="tt">bbb</span>
        <span>ccc</span>
        <p rel="pnode">ddd</p>
    </div>
STR;

// 获取id为demo的div内容
$data = selector::select($html, "//div[@id='demo']");
// 获取class为tt的span内容
$data = selector::select($html, "//div[@class='tt']");
// 获取rel为pnode的p内容
$data = selector::select($html, "//div[@rel='pnode']");
```

**2、模糊查询**

contains 匹配一个属性值中包含的字符串

```
$html =<<<STR
    <div id="demo1">
        demo1
    </div>
    <div id="demo2">
        demo2
    </div>
STR;

// 查找id属性中包含demo关键字的页面元素
// 这里能获取id为demo1和demo2的内容
$data = selector::select($html, "//div[contains(@id,'demo')]");
```

**3、获取节点属性**

```
$html =<<<STR
    <td data-value="3.80">3.80</td>    
    <td data-value="3.80">3.80</td>    
    <td data-value="3.80">3.80</td>    
    <td data-value="3.80">3.80</td>    
STR;

// 获取 td 的 data-value 属性
$data = selector::select($html, "//td@data-value");
```

**XPATH的几个常用函数**

1.contains ()： //div[contains(@id, 'in')] ,表示选择id中包含有’in’的div节点

2.text()：由于一个节点的文本值不属于属性，比如`<a class=”baidu“ href=”http://www.baidu.com“>baidu</a>`,所以，用text()
函数来匹配节点：//a[text()='baidu']

3.last()：//div[contains(@id, 'in')][las()]，表示选择id中包含有'in'的div节点的最后一个节点

4.starts-with()： //div[starts-with(@id, 'in')] ，表示选择以’in’开头的id属性的div节点

5.not()函数，表示否定，//input[@name=‘identity’ and not(contains(@class,‘a’))] ，表示匹配出name为identity并且class的值中不包含a的input节点。
not()函数通常与返回值为true or false的函数组合起来用，比如contains(),starts-with()
等，但有一种特别情况请注意一下：我们要匹配出input节点含有id属性的，写法如下：//input[@id]
，如果我们要匹配出input节点不含用id属性的，则为：//input[not(@id)]