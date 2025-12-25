# 📘 CLAUDE.md

以下说明用于指导 **Claude Code / Claude AI** 在本项目中的行为规范，确保其自动编辑、生成代码时不破坏现有逻辑。

---

## 📂 项目简介

这是一个基于 **Laravel + Dcat Admin** 的后台管理系统，项目结构遵循 Laravel 目录规范，同时大量使用了 Dcat Admin 的：

* Grid 列表
* Form 表单
* Show 详情
* Repositories（如有）
* Actions、Extensions、Widgets 等功能

项目主要使用：

* PHP8.2（Laravel 框架）
* Mysql
* Dcat Admin（后台 UI 框架）

---

## 🧭 Claude 应该遵循的基本规则

### ✅ 1. 严禁破坏现有业务逻辑

* 修改时 **不得动已有 PHP 逻辑**，除非我明确说明。
* 遇到不确定的地方，必须先注释、提示或询问，而不是擅自修改。

---

### ✅ 2. 遵守 Laravel 与 Dcat Admin 的模式和约定

包括但不限于：

* 控制器放在 `app/Admin/Controllers`
* 表格 `grid()` 与 `form()` 必须保持 Dcat 风格
* Model 必须继承 `Model`
* 尽量不在控制器中写复杂业务逻辑，应放到 service 或 model

---

### ✅ 3. 自动修复或优化代码时的优先级（不要超出范围）

1. 明确的语法错误（可直接修复）
2. 明显不会影响业务的优化
3. Laravel 或 Dcat 的最佳实践（如 route、grid、form 的用法）

遇到业务逻辑相关代码，除非我明确要求，否则不要修改。

---

## 📝 Dcat Admin 特别规范

### Grid

* 不要改动 `$grid->model()->…` 里的查询逻辑
* 调整样式、设置列标题、设置开关、格式化列值是允许的

### Form

* 不得改变字段名
* 不得擅自增加/删除字段
* 验证规则仅在我要求时添加

### Controller

* 允许：

    * 追加注释
    * 格式化代码
    * 优化返回类型
* 不允许：

    * 修改数据源（model 或 repository）
    * 修改表单字段逻辑

---

## 🧪 当我给出一段 PHP 代码（尤其是 Dcat Admin 的 grid/form），Claude 需要：

* **保持逻辑不动**
* 仅做：

    * 样式调整
    * 重构布局
    * 优化可读性
    * 优化 UI 展示
* 不要重写整个文件

---

## 🖼️ 前端资源

如果需要修改前端（Blade / JS / CSS），遵循：

* 不破坏原功能
* 样式可优化，但不改动核心 DOM 结构

---

## 📦 环境说明

* Laravel 版本：依据项目实际，可自动识别
* PHP 版本：依据 composer.json
* Dcat Admin 版本：依据 vendor 自动识别

---

## ❗ Claude 需特别注意

* 出现不确定情况 → 要求先提问/确认
* 对于「样式调整」任务：**必须保证原本 PHP 逻辑完全不动**
* 对于「Bug 修复」任务：需要写出修复原因

---

## 示例：样式调整要求

当我说：

> 帮我调整下样式，但是不要动 PHP 逻辑

Claude 只能修改：

```php
->width('200px')
->style(...)
->display(function () {...})
```

❌ **不能** 修改：

```php
->model()->where(...)
$grid->column('id')
form->saving(...)
```

---

## 示例：允许 Claude 的安全行为

* 增加注释解释代码
* 格式化代码风格
* 拆分长函数为多个小函数（不改变逻辑）
* 提供重构建议（不直接重写）

---

## Context7 MCP规则

- 只要用户的问题与 **代码示例、配置步骤、或第三方库/框架的用法** 有关，
  就 **自动使用 Context7** 获取库文档，不要自己猜。

- 步骤：
    1. 用 `resolve-library-id` 查库 ID。
    2. 用 `get-library-docs` 取相关文档。
    3. 再根据文档回答问题。

- 非库/框架相关的问题则不用 Context7。

## 其它规则：
- 回复尽量以中文回复
- 如果没有显性需求，尽量不要写兼容代码

---

## ⚠️ Windows Bash 命令规则

- **禁止**: `2>NUL` (会创建 NUL 文件)
- **使用**: `2>/dev/null` 或直接省略错误重定向
