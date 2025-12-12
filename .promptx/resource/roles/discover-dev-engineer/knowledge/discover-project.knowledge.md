@knowledge
name: discover-project
description: Discover 项目私有约束与结构要点。

project:
- 技术栈：Laravel 8 + Dcat Admin。
- 后台：`app/Admin`（Grid/Form/Action/Controller），后台路由在 `app/Admin/routes.php`。
- Web/API：控制器/中间件在 `app/Http`。
- 领域模型/枚举：`app/Models`、`app/Enums`；Trait/Service/Helper：`app/Traits`、`app/Services`、`app/Helpers/helper.php`。
- 迁移/种子/工厂：`database/migrations`、`database/seeders`、`database/factories`。

hard_rules:
- 未获明确指示不得修改现有业务逻辑或数据源。
- Grid/Form 不改字段与查询，只做展示层（标签、显示回调、宽度等）。
- 尽量保持最小化改动，不做无关重构。
@end

