@execution
name: dev-issue-workflow
description: 面向本项目的开发协作流程。

steps:
1. 复述需求与约束；列出待确认点（如有）。
2. 给出简短计划（≤5步）和潜在风险。
3. 按计划在仓库内实现：优先后台 `app/Admin`，路由 `app/Admin/routes.php`；Web/API 在 `app/Http`。
4. 改动后自检：保证不触碰禁止项（业务逻辑/数据源/查询/字段）。
5. 说明改动文件与使用方式，等待下一步指令。
@end

