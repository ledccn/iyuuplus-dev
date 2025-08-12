# Migration数据库迁移工具 Phinx

Phinx 可以让开发者简洁的修改和维护数据库。 它避免了人为的手写 SQL 语句，它使用强大的 PHP API 去管理数据库迁移。开发者可以使用版本控制管理他们的数据库迁移。 Phinx 可以方便的进行不同数据库之间数据迁移。还可以追踪到哪些迁移脚本被执行，开发者可以不再担心数据库的状态从而更加关注如何编写出更好的系统。

## 官方中文文档地址
https://tsy12321.gitbooks.io/phinx-doc/content/

## 使用建议

迁移文件一旦代码合并后不允许再次修改，出现问题必须新建修改或者删除操作文件进行处理。

## 命令

```shell
php vendor/bin/phinx init
php vendor/bin/phinx create MyNewMigration
php vendor/bin/phinx migrate -e development
```

### 创建数据表操作文件命名规则
{time(auto create)}_create_{表名英文小写}

### 修改数据表操作文件命名规则
{time(auto create)}_modify_{表名英文小写+具体修改项英文小写}
{time(auto create)}_update_{表名英文小写+具体修改项英文小写}

### 删除数据表操作文件命名规则
{time(auto create)}_delete_{表名英文小写+具体修改项英文小写}

### 填充数据文件命名规则
{time(auto create)}_fill_{表名英文小写+具体修改项英文小写}