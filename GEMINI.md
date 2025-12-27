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

### 批量新增功能说明
- **批量新增的正确实现方式**：批量新增通常是通过 iframe 跳转到列表页面（如 `admin/products?_grid_iframe_=1&order_model=XXX&order_id=xx`）来选择数据。
- **修改位置**：需要修改对应 Controller 的 `iFrameGrid()` 方法中的 `$grid->filter()`，而不是 `creating()` 或 `form()` 方法。
- **筛选器添加**：在 `iFrameGrid()` 的 filter 中添加筛选条件，参考普通 `grid()` 方法中的 filter 实现。
- **示例**：如在申请单批量新增物料时，需要添加品牌/分类筛选，应修改 `ProductController::iFrameGrid()` 的 filter，而非 `ApplyForOrderController::creating()`。

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

## 一次性脚本开发偏好（2025-12-27 学习）

### 用户偏好
- **直接硬编码数据**：对于一次性数据导入/更新脚本，用户偏好将数据直接硬编码在命令文件中，而不是动态读取外部文件
- **最终方案优先**：不需要展示中间尝试步骤或临时文件，直接给出最终可用的方案
- **清理临时文件**：完成任务后需要删除所有中间产生的临时文件和命令
- **使用 Laravel Model**：批量更新数据库时优先使用 Model 方式而非原生 SQL，便于维护

### 项目技术栈细节
- **Excel 处理库**：项目使用 `Dcat\EasyExcel\Excel`，不需要安装 PhpOffice\PhpSpreadsheet
  - 读取方式：`Excel::import($filePath)->headingRow(1)->first()`
  - 获取表头：`$sheet->getOriginalHeadings()`
  - 获取数据：`$sheet->toArray()`
- **产品模型字段**：
  - 表名：`product`
  - 模型：`App\Models\ProductModel`
  - 物料编号字段：`item_no`
  - 销售价字段：`sale_price`

### Artisan 命令开发规范
- 命令文件存放位置：`app/Console/Commands/`
- 自动加载：`Kernel.php` 中已有 `$this->load(__DIR__.'/Commands')`，新命令会自动注册
- 模拟模式：建议添加 `--dry-run` 选项，支持模拟运行
- 使用事务：批量更新时使用 `DB::beginTransaction()` 和 `DB::commit()` 确保数据一致性

