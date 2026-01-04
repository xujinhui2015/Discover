# Repository Guidelines

## 项目结构与模块
- 基于 Laravel 8 + Dcat Admin；后台网格/表单/动作/控制器位于 `app/Admin`，路由在 `app/Admin/routes.php`；Web 相关控制器和中间件在 `app/Http`。
- 领域模型与枚举位于 `app/Models` 和 `app/Enums`；通用 Trait/Service/Helper 分别在 `app/Traits`、`app/Services`、`app/Helpers/helper.php`。
- 数据库迁移与种子在 `database/migrations`、`database/seeders`，工厂在 `database/factories`。
- 视图/静态资源遵循 Laravel 约定，位于 `resources/views` 与 `public`；测试位于 `tests/Feature`、`tests/Unit`。

## 安装与运行
- `composer install` 安装依赖。
- 复制环境：`cp .env.example .env`（PowerShell 可用 `Copy-Item .env.example .env`），再填写数据库/缓存/存储配置。
- `php artisan key:generate` 生成 APP_KEY。
- `php artisan migrate`，`php artisan db:seed --class=InitSeeder` 初始化表与数据。
- 本地开发：`php artisan serve`；修改 env/config 后可执行 `php artisan config:clear && php artisan cache:clear`。
- 如需前端构建：`npm install`，`npm run dev` 或 `npm run prod`（配置见 `webpack.mix.js`）。

## 代码风格与命名
- 遵循 `.editorconfig`：UTF-8、LF、4 空格缩进、行尾空白裁剪、文件末尾换行。
- PHP 代码按 PSR-12/Laravel 习惯；使用具语义的类与方法名（如 `InventoryAdjustService`）。
- 保持 Dcat Grid/Form 业务查询与字段名不变；复杂业务宜放 Service/Model，控制器避免堆逻辑。
- 可用 `php-cs-fixer fix --config=.php_cs.dist` 进行格式化。

## 测试规范
- 使用 PHPUnit（配置见 `phpunit.xml`），命令：`vendor/bin/phpunit`。
- Feature 测试放 `tests/Feature`，Unit 测试放 `tests/Unit`；命名体现意图，如 `InventoryFlowTest`。
- 涉及数据库建议使用事务或刷新数据库 Trait 保持隔离。

## 提交与 PR
- 提交信息使用简短祈使句（例："Add purchase order grid filters"），相关变更尽量同一提交。
- PR 描述需说明改了什么/为何改，注明迁移或种子影响；UI 变更附截图/GIF；关联 issue。
- 保持最小化改动：未经要求不要更动现有业务逻辑、模型或 Dcat 查询；若需调整，优先添加说明性注释。

## Agent 注意事项
- 未获明确指示，不得修改业务逻辑或数据源；不确定时先沟通。
- Grid/Form 仅可做展示层调整（标签、显示回调、宽度等）；不得增删字段或改 `$grid->model()` 查询。
- 复杂流程放在 Service/Model；遇到晦涩流程可添加简短注释以便维护。

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
- 代码提示信息（命令行输出、异常提示、日志文案等）默认使用中文
