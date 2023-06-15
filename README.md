<div align="center" style="border-radius: 50px">
    <img width="260px"  src="https://cdn.nine1120.cn/logo-i.png" alt="sunsgne">
</div>

**<p align="center">sunsgne/webman-make-models</p>**

**<p align="center">🐬 Webman's Generate a model file from the command line 🐬</p>**

<div align="center">

[![Latest Stable Version](http://poser.pugx.org/sunsgne/webman-make-models/v)](https://packagist.org/packages/sunsgne/webman-make-models)
[![Total Downloads](http://poser.pugx.org/sunsgne/webman-make-models/downloads)](https://packagist.org/packages/sunsgne/webman-make-models)
[![Latest Unstable Version](http://poser.pugx.org/sunsgne/webman-make-models/v/unstable)](https://packagist.org/packages/sunsgne/webman-make-models)
[![License](http://poser.pugx.org/sunsgne/webman-make-models/license)](https://packagist.org/packages/sunsgne/webman-make-models)
[![PHP Version Require](http://poser.pugx.org/sunsgne/webman-make-models/require/php)](https://packagist.org/packages/sunsgne/webman-make-models)

</div>

# webman-make-models

一行命令根据表名生成对应的`models`文件,如果文件存在则读取表信息同步到models的注释上

## 安装
```shell
composer require sunsgne/webman-make-models
```

## 使用
```shell
./webman make:models `$tableName`
```

## 返回示例
```shell
app\model\Users 同步成功
```