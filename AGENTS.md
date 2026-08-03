# AGENTS.md — Discover 项目指令

## 语言与输出

- **回复默认使用中文。**
- 代码中的提示信息（命令行输出、异常消息、日志文案）默认使用中文。
- 不要写兼容代码，除非用户明确要求。

## Agent 行为红线

> 以下规则优先级最高，任何其他指令不得覆盖。

1. **未获明确指示，不得修改业务逻辑或数据源**；不确定时先问。
2. Grid/Form 仅可做展示层调整（标签、显示回调、宽度等）；**不得增删字段或改 `$grid->model()` 查询**。
3. 保持最小化改动：不要顺手重构、补注释、加类型标注——只做用户要求的事。
4. 复杂业务逻辑放 Service 或 Model，控制器保持薄层。

## 技术栈

| 层 | 技术 |
|---|---|
| 框架 | Laravel 8 (PHP 8.2+) |
| 后台 | Dcat Admin 1.7 |
| 前端构建 | Laravel Mix (`webpack.mix.js`) |
| 测试 | PHPUnit (`vendor/bin/phpunit`) |
| 格式化 | `php-cs-fixer fix --config=.php_cs.dist` |

## 项目结构

```
app/
├── Admin/              # Dcat 后台：Controllers, Actions, Forms, Widgets, routes.php
├── Console/            # Artisan 命令
├── Enums/              # 枚举类
├── Exceptions/         # 异常处理
├── Helpers/            # helper.php 全局辅助函数
├── Http/               # Web 控制器、中间件、请求验证
├── Models/             # Eloquent 模型
├── Observers/          # 模型观察者
├── Providers/          # 服务提供者
├── Repositories/       # 数据仓库
├── Services/           # 业务服务层
└── Traits/             # 通用 Trait
database/
├── migrations/         # 数据库迁移
├── seeders/            # 数据填充（初始化用 InitSeeder）
└── factories/          # 模型工厂
tests/
├── Feature/
└── Unit/
```

## 代码风格

- 遵循 `.editorconfig`：UTF-8、LF、4 空格缩进、文件末尾换行。
- PHP 代码按 PSR-12 / Laravel 惯例；类与方法名语义明确（如 `InventoryAdjustService`）。

## 测试

- Feature 测试放 `tests/Feature`，Unit 测试放 `tests/Unit`。
- 测试命名体现意图，如 `InventoryFlowTest`。
- 涉及数据库使用事务或 `RefreshDatabase` Trait 保持隔离。

## 提交与 PR

- 提交信息用简短祈使句，例：`Add purchase order grid filters`。
- PR 说明改了什么、为何改；涉及迁移/种子须注明；UI 变更附截图。

## 本地运行（参考）

```bash
composer install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=InitSeeder
php artisan serve
```
