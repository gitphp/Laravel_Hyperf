这一套是 **Hyperf + Nacos + JSON-RPC + 独立库** 的微服务体系。你写的 `ad-hr` / `ad-merchant` 实际仓库名是 **`adc-hr` / `adc-merchant`**。下面按「总览 → 各服务职责 → 通信 → 横切能力 → 依赖关系」说明。

---

## 1. 整体架构

```text
前端（Vue3 + MicroApp 微前端 + Element Plus）
        │  HTTP（按 URL 前缀）
        ▼
Nginx 网关（按 /public/ /user/ /hr/ /finance/ /merchant/ 转发）
        │
        ├─► adc-public   (9601 HTTP / 9701 RPC)  平台基础 + 总后台
        ├─► adc-user     (9602 HTTP / 9702 RPC)  租户 RBAC + 企业后台
        ├─► adc-hr       (9603 HTTP / 9703 RPC)  人事组织
        ├─► adc-finance  (9611 HTTP / 9711 RPC)  财务会计
        └─► adc-merchant (9622 HTTP / 9722 RPC)  商家中心

横向基础设施：
  Nacos（服务发现 + 配置）  RabbitMQ（异步）  Redis（缓存/会话）
  MySQL 每服务一库         XXL-Job（定时）     OSS（文件，经 public）
```

核心原则：

| 原则 | 落地 |
|------|------|
| 前后端分离 | 只走 HTTP API |
| 服务自治 | 每服务独立进程、独立 Docker、独立数据库 |
| 同步跨服务 | JSON-RPC over HTTP，经 Nacos 发现 |
| 异步跨服务 | RabbitMQ AMQP |
| 统一公共能力 | `vendor/tgkw-adc/helper` 基础包（注解、中间件、RPC 接口、FileSystemHelper 等） |

官方说明见：`docs-adc/docs/ADC-项目架构说明.md`、端口表见 `ADC-项目后端开发规范.md`。

---

## 2. 五个服务各自干什么

### 2.1 `adc-public` — 平台底座 + 总后台

| 项 | 值 |
|----|-----|
| APP_NAME | （公共/后台） |
| DB | 平台库（Admin/File/Config 等） |
| 对外 HTTP | 总后台登录、管理员/角色/菜单、区域、配置、文件预签名 |
| 对外 RPC | **`PublicService`**、**`SystemService`** |

职责要点：

- **文件中枢**：预签名直传 OSS、`files` 主表、`handleFilesUsed` / `getFileInfo`、临时 URL。
- **平台配置**：区域、基础配置、三方配置、OCR、企业核验等。
- **系统后台 RBAC**：管的是 **Admin**（运营总后台），与租户侧 `adc-user` 的 Org 权限是两套体系。
- 总后台通过 RPC 调 `SystemUserService` / `SystemTenantService` 等去操作 user 侧数据。

特殊点：包体上限约 150MB，方便大文件相关能力。

---

### 2.2 `adc-user` — 租户 RBAC 中心 + 企业后台

| 项 | 值 |
|----|-----|
| APP_NAME | `user` |
| DB | `adc_user` |
| 对外 HTTP | 登录/Token、租户、角色、权限组、通讯录入口、操作日志等 |
| 对外 RPC | **`UserService`**、`SystemUserService`、`SystemTenantService`… |

职责要点：

- 多租户 RBAC：`Tenant → Role → PermissionGroup → Menu/Action → User(+scope)`。
- **权限注册中心**：各业务服务启动时 RPC `UserService::addMenu()` 把 `#[OrgPermission]` 同步进 `menus` + Casbin。
- **`AppIdConstants::MICRO_DEFAULT_MAP_ID`**：`APP_NAME → app_id`（含 `finance`、`hr`、`merchant` 等），新服务必须登记。
- 企业后台「通讯录」HTTP 在 user，**员工/组织真数据在 hr**，user 通过 `HrEmployeesService` / `HrOrganizationsService` 等 RPC 拉数据。
- 也会消费 `FinanceService`（费款树、原币、结算相关等）。

---

### 2.3 `adc-hr` — 人事数据源

| 项 | 值 |
|----|-----|
| APP_NAME | `hr` |
| DB | `adc_hr` |
| 对外 HTTP | 员工、组织、考勤、薪资等业务 API |
| 对外 RPC | **`HrService`**、`HrEmployeesService`、`HrOrganizationsService`、`HrPositionsService` 等 |

职责要点：

- 员工全生命周期、组织架构、考勤、薪资。
- **员工/组织权威数据源**：finance、user、merchant、public 等只 RPC 引用，不在各自库维护完整 HR 主数据。
- 依赖 `UserService`（鉴权/用户）、`PublicService`（文件）、以及审批/消息/财务等。

---

### 2.4 `adc-finance` — 财务域

| 项 | 值 |
|----|-----|
| APP_NAME | `finance` |
| DB | `adc_finance` |
| 对外 HTTP | 凭证、科目、账期、银行、结算、报销、报表等 |
| 对外 RPC | **`FinanceService`**（费款树、币种、结算单、报销落库、租户财务默认数据 bootstrap 等） |

职责要点：

- 会计核算闭环：账套/科目/凭证/多币种/银行对账/结算付款/报销/报表模板。
- 业务附件只存 `file_key`，文件主数据与 OSS 在 public。
- 强依赖：`UserService`（权限）、`HrService`（员工组织）、`PublicService`（文件）、以及审批 `ApprovalService`、合同 `ClmService` 等。
- 也会被 user/hr 反向调用 `FinanceService`（例如租户开通默认财务数据、费款业务树）。

---

### 2.5 `adc-merchant` — 商家中心（业务骨架期）

| 项 | 值 |
|----|-----|
| APP_NAME | `merchant` |
| DB | `adc_merchant` |
| 端口 | HTTP 9622 / RPC 9722 |
| 现状 | 通用框架 + 基础模块为主，业务表仍在演进 |

职责要点：

- 已接入同一套 Nacos / OrgPermission / RPC 消费模式。
- 当前主要消费：`PublicService`、`UserService`、`HrService`、`SystemService`。
- Providers 目录尚空或很少——对外 RPC 能力仍在建设中，但 HTTP 侧已按平台规范接入。

---

## 3. 请求是怎么进来的（南北向）

### 3.1 Nginx 按前缀转发

本地/部署约定类似：

```nginx
location /public/   { proxy_pass ...:9601; }
location /user/     { proxy_pass ...:9602; }
location /hr/       { proxy_pass ...:9603; }
location /finance/  { proxy_pass ...:9611; }
location /merchant/ { proxy_pass ...:9622; }
```

前端微应用按业务域调对应前缀；**不是**所有流量先进一个 BFF 再二次转发业务（网关主要做路由/静态）。

### 3.2 各服务内分层（统一写法）

```text
Controller（注解路由）
  → Request（场景校验）
  → Service（业务）
  → Model / RPC Consumer / AMQP
  → Resource/Application/V1（HTTP 出参）
     Resource/Domain（RPC 出参）
```

约定：

- 路由用注解，不用大段 `routes.php`。
- 租户业务接口：`#[OrgPermission]` + `OrgMiddleware`。
- i18n：`en` / `zh_cn` / `zh_hk`。

---

## 4. 服务间怎么通信（东西向）

### 4.1 JSON-RPC + Nacos（同步）

每个服务同时开两个端口：

- **HTTP**：给前端 / Nginx
- **jsonrpc-http**：给其他微服务

配置在各仓 `config/autoload/services.php`：

- `enable.discovery / register = true`
- `consumers`：声明要调谁（如 finance 声明 `UserService`、`PublicService`、`HrService`…）
- 驱动：`nacos`，group 默认 `adc`，实例 ephemeral + 心跳

Provider 用注解注册，例如 finance：

```php
#[RpcService(name: 'FinanceService', protocol: 'jsonrpc-http',
             server: 'jsonrpc-http', publishTo: 'nacos')]
```

调用方在 `dependencies.php` 把 Interface 绑到 Consumer，业务代码只依赖接口。

接口定义大量在共享包 `TgkwAdc\JsonRpc\...`，各服务再包一层本地 Consumer/Provider，保证契约统一。

### 4.2 RabbitMQ（异步）

典型场景：批量导入、结算单创建/更新结果回传、登录/操作日志投递。

规范：**先写 Consumer，再写 Producer**；队列名常带 `APP_NAME` 前缀隔离。

finance 示例：银行对账单导入、结算单 AMQP 消费/结果生产。

### 4.3 Redis

共用实例，**key 前缀按服务隔离**；用于缓存、会话/Token 校验等。

---

## 5. 横切能力如何串起来

### 5.1 权限（租户侧）——以 `adc-user` 为中心

```text
各服务启动
  → 扫描 #[OrgPermission]
  →（Nacos needAddMenuSrv 含本服务）
  → RPC UserService::addMenu
  → menus + Casbin

请求进来
  → OrgMiddleware（helper 包）
  → 校验 Org Token / 租户
  → RPC UserService::checkAccessPermission(act = {APP_NAME}:{Controller}@{method})
```

两套权限不要混：

| 体系 | 服务 | 主体 |
|------|------|------|
| 租户 Org | adc-user | 租户用户 / 角色 / 菜单 |
| 系统 Admin | adc-public | 总后台管理员 |

### 5.2 文件

```text
前端 → public 预签名 → 直传 OSS → public.files
业务服务只存 object_key/file_key
占用：PublicService::handleFilesUsed
展示：FileSystemHelper::genFileTempUrl
```

### 5.3 共享基础包 `tgkw-adc/helper`

各服务 `composer` 依赖同一 helper，提供：

- `OrgPermission` / `OrgMiddleware`
- `FileSystemHelper`、LogHelper
- 公共 JsonRpc Interface
- BaseResource、异常、部分 Listener 等

业务差异留在各仓；横切能力升级靠 `composer update tgkw-adc/helper`。

---

## 6. 五服务依赖关系（简图）

```text
                    ┌─────────────┐
                    │ adc-public  │  PublicService / SystemService / 文件 / 配置
                    └──────▲──────┘
           ┌───────────────┼────────────────┐
           │               │                │
    ┌──────┴──────┐ ┌──────┴──────┐  ┌──────┴──────┐
    │  adc-user   │ │   adc-hr    │  │ adc-finance │
    │ UserService │◄┤ HrService…  │  │FinanceService│
    │ 权限中心    │ │ 人事主数据  │  │ 财务主数据   │
    └──────▲──────┘ └──────▲──────┘  └──────▲──────┘
           │               │                │
           └───────────────┼────────────────┘
                           │
                    �──────┘ └──────▲──────┘  └──────▲──────┘
           │               │                │
           └───────────────┼────────────────┘
                           │
                    ┌──────┴──────┐
                    │adc-merchant │  主要消费上述 RPC，业务仍在建设
                    └─────────────┘
```

更具体的消费关系（来自各仓 `services.php`）：

| 调用方 → | Public | User | Hr | Finance | 其它 |
|----------|--------|------|-----|---------|------|
| **public** | — | ✓ System* | ✓ | | 操作日志等 |
| **user** | ✓ | ✓ | ✓ Employees/Orgs/Positions | ✓ | |
| **hr** | ✓ | ✓ | — | ✓ | Approval / Notice… |
| **finance** | ✓ | ✓ | ✓ (+ Orgs) | — | Approval / Clm… |
| **merchant** | ✓ | ✓ | ✓ | | System |

特征：

- **user / public / hr** 是平台底座三角：身份、公共能力、组织人事。
- **finance / merchant** 是业务域服务：自己的库 + 自己的 HTTP，跨域数据走 RPC，不直连别人的库。

---

## 7. 数据与边界

| 数据 | 权威归属 |
|------|----------|
| 租户用户、角色、菜单、Casbin | `adc_user` |
| 员工、组织、考勤、薪资 | `adc_hr` |
| 文件元数据、区域、平台配置、Admin | public 库 |
| 凭证、科目、结算、报销、银行 | `adc_finance` |
| 商家业务 | `adc_merchant` |

跨库原则：**禁止跨服务直连对方 MySQL**；需要别人的数据就 RPC（或 MQ 事件）。业务表可冗余展示字段（如文件名），权威仍以归属服务为准。

---

## 8. 部署形态

- 每服务：`Dockerfile` + `docker-compose-dev/pro.yml`，容器内 `composer start` / `restart.sh`。
- 生产倾向：Nginx + 多容器；可上 k8s 水平扩展。
- 单节点与多节点示意见架构文档；中间件（Nacos/Redis/MQ/MySQL）可共享，应用进程按服务拆开。

---

## 9. 和「单体」比，这套设计在解决什么

1. **按域拆分**：财务改结算不影响 HR 发薪部署。
2. **权限集中、菜单分散声明**：业务服务只标注解，user 统一落库与 enforce。
3. **公共能力下沉 public**：文件、配置、OCR 不复制多份。
4. **契约化协作**：Nacos 服务名 + JsonRpc Interface，而不是硬编码 IP。
5. **异步解耦导入/跨域写**：大导入、结算回传走 MQ，避免拖垮 HTTP。

---

## 10. 一句话对照表

| 服务 | 一句话 |
|------|--------|
| **adc-public** | 平台公共能力 + 总后台；文件与配置的家 |
| **adc-user** | 登录与租户权限中枢；企业后台壳，通讯录数据靠 HR |
| **adc-hr** | 人与组织的权威数据源 |
| **adc-finance** | 会计核算与资金结算业务域 |
| **adc-merchant** | 商家域服务，已挂上平台框架，业务仍在长 |

若你接下来要看某一条链路的代码级走读（例如「登录 → OrgMiddleware → checkAccessPermission」或「finance 创建结算单如何回调 CLM」），可以说一下我按链路拆开讲。