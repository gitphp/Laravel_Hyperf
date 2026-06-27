

## Hyperf 3.1 项目菜单系统解析、流程

### 一、菜单的定义方式

本项目采用**注解驱动**的菜单定义方式，核心是通过 `#[OrgPermission]` 注解在控制器（Controller）上声明菜单。

#### 1. 核心注解：`OrgPermission`

位于 [OrgPermission.php](file:///home/wwwroot/adc-finance/vendor/tgkw-adc/helper/src/Annotation/OrgPermission.php)，主要属性：

| 属性 | 说明 |
|------|------|
| `module` | 菜单模块层级标识，如 `报表:损益统计表` |
| `accessCode` | 唯一权限标识，格式：`account-report:pnl-statistics` |
| `parentAccessCode` | 父级菜单标识，如 `account-report` |
| `type` | 类型：`MENU`（菜单/页面）或 `BUTTON`（按钮） |
| `i18nName` | 国际化名称 `{en, zh_cn, zh_hk}` |
| `sort` | 排序权重 |
| `frontRouteAlias` | 前端路由别名，如 `account-report.pnl-statistics` |

#### 2. 使用示例

在控制器方法上使用（以 [PnlStatisticsReportController.php:L50-58](file:///home/wwwroot/adc-finance/app/Controller/V1/Report/PnlStatisticsReportController.php#L50-58) 为例）：

```php
#[OrgPermission(
    module: '报表:损益统计表',
    type: 'MENU',
    i18nName: ['en' => 'Profit and loss statistics', 'zh_cn' => '损益统计表'],
    sort: 2030,
    parentAccessCode: 'account-report',
    accessCode: 'account-report:pnl-statistics',
    frontRouteAlias: 'account-report.pnl-statistics',
)]
#[GetMapping(path: 'pnl-statistics')]
public function list(PnlStatisticsReportRequest $request): ResponseInterface { ... }
```

### 二、菜单的解析过程

菜单解析由 [OrgPermissionHelper::build()](file:///home/wwwroot/adc-finance/vendor/tgkw-adc/helper/src/Helper/OrgPermissionHelper.php) 完成：

```php
public static function build(): array
{
    // 1. 收集类级别注解
    $classAnnotations = self::collectClassAnnotations();
    
    // 2. 收集方法级别注解
    $methodAnnotations = self::collectMethodAnnotations();
    
    // 3. 合并并去重
    $merged = array_merge($classAnnotations, $methodAnnotations);
    
    return [
        'micro' => env('APP_NAME'),  // 微服务名，如 'finance'
        'annotations' => self::deduplicate($merged),
        'version' => time()
    ];
}
```

**关键机制**：利用 Hyperf 的 `AnnotationCollector` 扫描所有已加载类，收集带有 `#[OrgPermission]` 注解的类和方法。

### 三、菜单的同步机制

在服务启动时（MainWorkerStart），通过 [MainWorkerStartListener](file:///home/wwwroot/adc-finance/vendor/tgkw-adc/helper/src/Listener/MainWorkerStartListener.php) 自动同步菜单：

```php
// 1. 解析菜单注解
$orgMenuData = OrgPermissionHelper::build();

// 2. 调用用户服务 RPC 接口同步菜单
$userService->addMenu($orgMenuData);

// 3. 调用系统服务 RPC 接口同步系统后台菜单
$systemService->addMenu($sysMenuData);
```

### 四、菜单表

**菜单数据存储在用户服务（UserService）中**，本项目不直接管理菜单表，而是通过 RPC 调用用户服务的接口来同步菜单定义。

菜单表结构（由用户服务管理）大致包含：
- `id` - 主键
- `access_code` - 权限标识
- `parent_access_code` - 父级标识
- `module` - 模块路径
- `i18n_name` - 多语言名称
- `type` - MENU/BUTTON
- `sort` - 排序
- `front_route_alias` - 前端路由别名
- 其他字段...

### 五、工作流程图

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. 开发者在 Controller 方法上添加 #[OrgPermission] 注解          │
│    例: PnlStatisticsReportController::list()                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. 服务启动时，MainWorkerStartListener 触发菜单同步              │
│    - 调用 OrgPermissionHelper::build() 扫描所有注解             │
│    - 获取 APP_NAME 和时间戳                                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. OrgPermissionHelper 遍历所有类/方法的 #[OrgPermission]        │
│    - 按 accessCode 去重                                          │
│    - 组装结构化菜单数据                                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. 调用 RPC 接口同步到用户服务/系统服务                          │
│    $userService->addMenu($menuData)                             │
│    $systemService->addMenu($menuData)                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. 用户服务将菜单存入 menus 表                                    │
│    - 前端根据 menus 表渲染左侧菜单树                              │
│    - 权限校验时对比 accessCode                                    │
└─────────────────────────────────────────────────────────────────┘
```

### 六、校验规则

启动时还会校验注解参数的格式：
- `accessCode`：全小写，层级用 `:` 分隔（如 `account-report:pnl-statistics`）
- `parentAccessCode`：格式同上
- `frontRouteAlias`：全小写，层级用 `.` 分隔（如 `account-report.pnl-statistics`）