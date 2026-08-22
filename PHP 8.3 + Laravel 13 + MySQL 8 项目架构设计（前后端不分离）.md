# PHP 8.3 + Laravel 13 + MySQL 8 项目架构设计（前后端不分离）

> 本文档为基于 Laravel 13（PHP 8.3）与 MySQL 8 的企业级应用架构规范，采用传统服务端渲染（Blade）为主，辅以 Vue 3 组件增强交互，适用于中大型业务系统。

---

## 一、总体架构原则

- **分层清晰**：严格区分表现层、业务逻辑层、数据访问层，避免 Controller 过重。
- **领域驱动设计（DDD）思想**：按业务领域划分模块（Module），降低耦合。
- **可测试性**：依赖注入、接口契约、Mock 友好。
- **性能优先**：合理使用缓存、索引、分页、延迟加载。
- **安全第一**：输入验证、SQL 注入防护、XSS 过滤、CSRF 保护（Laravel 内置）。

---

## 二、目录结构规范

采用 **模块化 + 领域分层** 的目录组织方式，基础 Laravel 目录结构不变，但扩展 `app/` 下的子目录。

```
app/
├── Console/                     # 自定义 Artisan 命令
├── Exceptions/                  # 异常处理器
├── Constants/                   # 错误码类
├── Http/
│   ├── Controllers/             # 控制器（仅负责请求响应与视图渲染）
│   │   ├── Backend/             # 后台控制器
│   │   ├── Frontend/            # 前台控制器
│   │   └── Api/                 # （如需）API 控制器
│   ├── Middleware/              # 中间件
│   ├── Requests/                # 表单请求验证（FormRequest）
│   └── ViewComposers/           # 视图数据共享（全局数据）
├── Models/                      # Eloquent 模型（仅数据映射，不含业务逻辑）
│   ├── Traits/                  # 模型公共 Trait（如软删除、时间戳）
│   └── Relations/               # 复杂关联定义（可选）
├── Modules/                     # **核心业务模块（按领域划分）**
│   ├── User/                    # 用户域
│   │   ├── Contracts/           # 接口契约（Repository 接口等）
│   │   ├── Services/            # 业务服务类
│   │   ├── Repositories/        # 数据仓库实现
│   │   ├── DTO/                 # 数据传输对象
│   │   ├── Actions/             # 单一操作类（如 RegisterUserAction）
│   │   ├── Events/              # 领域事件
│   │   └── Providers/           # 模块服务提供者（注册绑定）
│   ├── Product/                 # 商品域
│   ├── Order/                   # 订单域
│   └── ...                      # 其他业务域
├── Services/                    # 服务类-业务逻辑处理
├── Support/                     # 通用基础设施
│   ├── Helpers/                 # 自定义辅助函数
│   ├── Traits/                  # 通用 Trait
│   ├── Exceptions/              # 业务异常类
│   ├── Criteria/                # Repository 查询条件（配合 Laravel Repository）
│   └── Enums/                   # PHP 枚举类（PHP 8.1+）
├── Providers/                   # 服务提供者（全局注册）
└── ...
```

> **关键点**：  
> - 每个 `Module` 独立封装，可单独拆分到包（Package）。  
> - `Contracts` 与 `Repositories` 分离，便于替换实现或进行单元测试。  
> - `Services` 编排多个 `Actions` 或直接调用 `Repository`，但 `Action` 粒度更细，利于复用。

---

## 三、核心设计模式与实践

### 1. Repository 模式（数据仓库）

- **目的**：隔离数据访问逻辑，便于切换数据库或缓存策略。
- **位置**：每个模块下的 `Repositories` 目录。
- **规范**：
  - 定义接口 `UserRepositoryInterface`，实现类 `UserRepository` 继承 `BaseRepository`。
  - 使用 Laravel 的 `Eloquent` 作为默认驱动，但接口只暴露 `findByXX`、`paginate` 等方法。
  - 支持查询条件（`Criteria`）组合，避免臃肿的 `where` 链。

**示例**：
```php
// Modules/User/Contracts/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function paginate(int $perPage = 15, array $filters = []);
    public function create(array $data): User;
    // ...
}

// Modules/User/Repositories/UserRepository.php
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
}
```

### 2. Service 层（业务逻辑编排）

- 负责协调多个 `Repository` 或 `Action`，处理事务边界、事件分发。
- 不能直接处理 HTTP 请求或视图，只接收 DTO 或原生参数。
- 命名：`XxxService`（如 `UserService`）。

**示例**：
```php
class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected RoleRepositoryInterface $roleRepo
    ) {}

    public function assignRole(int $userId, string $roleName): void
    {
        DB::transaction(function () use ($userId, $roleName) {
            $user = $this->userRepo->findById($userId);
            $role = $this->roleRepo->findByName($roleName);
            $user->assignRole($role);
        });
    }
}
```

### 3. Action 类（单一职责操作）

- 每个 `Action` 只做一件事，可被多个 Service 复用。
- 命名：`XxxAction`（如 `SendWelcomeEmailAction`）。
- 支持依赖注入，便于单元测试。

**示例**：
```php
class RegisterUserAction
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected HashService $hashService
    ) {}

    public function execute(RegisterDTO $dto): User
    {
        $hashedPassword = $this->hashService->make($dto->password);
        $user = $this->userRepo->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $hashedPassword,
        ]);
        // 触发事件
        event(new UserRegistered($user));
        return $user;
    }
}
```

### 4. DTO（数据传输对象）

- 用于在层间传递结构化数据，避免传递过多参数。
- 使用 PHP 8 的 `readonly` 属性与构造方法。
- 可配合 `Spatie/DataTransferObject` 或原生类。

**示例**：
```php
class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $phone = null
    ) {}
}
```

### 5. 枚举（Enum）

- 使用 PHP 8.1 原生枚举表示常量集合（状态、类型等），替代类常量。
- 放在 `Support/Enums` 下。

**示例**：
```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => '待支付',
            self::PAID    => '已支付',
            // ...
        };
    }
}
```

### 6. 表单请求（FormRequest）

- 用于输入验证和授权，放在 `Http/Requests`。
- 使用规则数组，错误消息自定义。
- **禁止**在 Controller 中直接 `validate`。

**示例**：
```php
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ];
    }
}
```

---

## 四、数据库设计规范

### 1. 命名约定

- 表名：**小写复数**（`users`, `product_categories`）。
- 字段名：**小写蛇形**（`created_at`, `is_active`）。
- 主键：`id`（自增）或 `uuid`（推荐分布式系统使用）。
- 外键：`{引用表名}_id`（如 `user_id`）。
- 时间字段：`created_at`, `updated_at`, `deleted_at`（软删除）。
- 布尔字段：以 `is_` 或 `has_` 前缀（`is_active`）。

### 2. 索引策略

- 每个 `WHERE` 条件涉及的字段加索引。
- 复合索引遵循**最左前缀**原则。
- 避免冗余索引（使用 `pt-duplicate-key-checker` 检查）。
- 对 `text`/`blob` 字段使用前缀索引。

### 3. 迁移文件（Migration）

- 每个表单独一个迁移文件，命名清晰（`create_users_table`）。
- 使用 `->comment()` 为字段添加注释。
- 所有迁移必须支持回滚（`down` 方法）。

**示例**：
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('order_no')->unique()->comment('订单编号');
    $table->enum('status', ['pending','paid','shipped','cancelled'])->default('pending');
    $table->unsignedDecimal('total_amount', 10, 2);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'status']);
});
```

### 4. 查询优化

- 使用 `select` 仅取需要的字段。
- 关联查询使用 `with` 预加载（N+1 问题）。
- 大数据量分页使用 `cursor` 或 `chunk` 处理。
- 复杂统计使用数据库视图或临时表。

---

## 五、前后端不分离技术选型

- **服务端渲染**：Laravel Blade 模板引擎，布局继承 + 组件（`@include` / `@component`）。
- **前端增强**：Vue 3（通过 Vite）编写独立组件，挂载到指定 DOM 节点，实现局部交互。
- **资源管理**：使用 Vite 编译 CSS/JS，启用版本号（`mix` 替代方案）。
- **数据传递**：Blade 直接输出变量到视图，Vue 组件通过 `props` 接收后端数据（如 `@json($data)`）。
- **Ajax 交互**：使用 `axios` 调用 Laravel 路由（Web 路由返回 JSON），CSRF 令牌自动处理。

> **注意**：不采用 SPA（Inertia 或 Vue Router），保持传统多页面应用，但利用 Vue 增强表单、表格、弹窗等交互。

---

## 六、安全规范

1. **SQL 注入防护**：使用 Eloquent ORM / DB 查询构造器，绝对避免字符串拼接。
2. **XSS 防护**：Blade 默认 `{{ }}` 转义，如需输出 HTML 使用 `{!! !!}` 但务必确认内容安全。
3. **CSRF**：所有 POST/PUT/DELETE 表单需 `@csrf`，Ajax 请求自动携带 token。
4. **输入验证**：所有外部输入（GET/POST/JSON）都要经过 FormRequest 或 Validator。
5. **敏感数据**：密码使用 `Hash::make()` 存储，API 密钥使用 Laravel 的 `encrypt()`。
6. **权限控制**：使用 Laravel 的 Gate/Policy 定义权限，在 Controller/Blade 中使用 `@can` / `@cannot`。

---

## 七、错误处理与日志

- **异常分类**：
  - 业务异常（`BusinessException`）：可预知的错误（如库存不足），返回友好提示。
  - 系统异常（`SystemException`）：技术错误，记录日志并返回 500。
- **日志策略**：
  - 使用 Laravel 的 `Log` 门面，按日切割（`daily`）。
  - 日志级别：`emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`。
  - 关键操作（支付、注册、登录）必须记录 INFO 级别以上。
- **全局异常处理**：
  - 在 `App\Exceptions\Handler` 中重写 `render` 方法，根据请求类型返回 JSON 或视图。

**示例**：
```php
public function render($request, Throwable $e)
{
    if ($e instanceof BusinessException) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return back()->withErrors(['error' => $e->getMessage()]);
    }
    return parent::render($request, $e);
}
```

---

## 八、性能优化策略

### 1. 缓存机制
- **查询缓存**：使用 `Cache::remember` 缓存常用查询结果（如配置、分类）。
- **页面缓存**：针对频繁访问但变化少的页面，使用 `Cache::put` 存储渲染后的 HTML。
- **模型缓存**：使用 `laravel-model-caching` 包自动缓存模型查询。

### 2. 会话与数据库
- 使用 Redis 作为 Session、Cache 驱动，减少文件/数据库查询。
- 配置 `SESSION_DRIVER=redis`，`CACHE_DRIVER=redis`。

### 3. 队列
- 耗时任务（邮件发送、图片处理、报表生成）异步处理，使用 Redis 驱动。

### 4. 前端优化
- Vite 启用 `build.rollupOptions.output.manualChunks` 拆分依赖。
- 使用 `<link rel="preload">` 预加载关键资源。
- 图片使用 WebP 格式，并启用 CDN。

### 5. 数据库优化
- 定期分析慢查询日志，使用 `EXPLAIN` 优化索引。
- 对历史数据归档（分区或分表）。

---

## 九、测试策略

- **单元测试**（`phpunit`）：测试 Service、Action、Repository（使用 `RefreshDatabase`）。
- **功能测试**：测试 Controller 路由、表单请求、视图响应。
- **测试覆盖率目标**：核心业务逻辑 ≥ 80%。
- **测试环境**：使用 SQLite 内存数据库或独立测试 DB。

**示例测试代码**：
```php
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_user()
    {
        $dto = new RegisterDTO('John', 'john@example.com', 'secret');
        $service = app(UserService::class);
        $user = $service->register($dto);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }
}
```

---

## 十、开发规范与工具

### 1. 代码风格
- 遵循 PSR-12 编码规范。
- 使用 Laravel 官方提供的 `laravel/pint` 或 `PHP_CodeSniffer` 自动修复。
- 提交前运行 `pint` 或配置 Git Hook。

### 2. 命名规范
- 类名：**帕斯卡**（`UserController`）。
- 方法/属性：**驼峰**（`getUserById`）。
- 变量：**驼峰**（`$userName`）。
- 常量：**大写蛇形**（`MAX_ATTEMPTS`）。

### 3. 文档注释
- 所有类、方法、属性必须包含 PHPDoc，标明参数类型、返回值、异常。

### 4. 版本控制
- 使用 Git Flow 分支模型（`main`, `develop`, `feature/*`, `hotfix/*`）。
- Commit 信息遵循约定式（`feat:`, `fix:`, `docs:`, `refactor:`）。

---

## 十一、部署与运维

- **环境配置**：`.env` 分环境（本地、测试、生产），敏感信息使用环境变量。
- **构建流程**：
  - `composer install --no-dev --optimize-autoloader`
  - `php artisan config:cache` / `route:cache` / `view:cache`
  - `npm run build` 构建前端资源
- **Web 服务器**：Nginx + PHP-FPM，配置 `opcache` 与 `realpath_cache`。
- **监控**：集成 Laravel Horizon（队列监控）、Telescope（调试）、Sentry（错误追踪）。

---

## 十二、扩展性与演化

- **模块独立**：各 Module 可独立升级或替换为包。
- **事件驱动**：使用 Laravel Events 解耦模块间通信。
- **多语言**：使用 Laravel 本地化功能，`lang/` 目录支持多语言。
- **API 演进**：如需提供 API，可使用 Laravel Sanctum 或 Passport，并独立 `Api/` 控制器。

---

## 结语

本架构设计遵循 **简洁、分层、可测试** 的原则，充分利用 Laravel 生态与 PHP 8.3 新特性（枚举、构造器属性、只读类），为长期维护与团队协作提供坚实基础。实际开发中可根据业务复杂度酌情增减层次，但核心思想应贯穿始终。

---

**版本**：v1.0  
**最后更新**：2026-08-22  
**维护者**：架构组