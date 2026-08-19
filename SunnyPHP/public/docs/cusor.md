SunnyPHP 轻量内核已经搭好，PHP 8.4，包名 `sunnyphp/framework`。

内核覆盖：PSR-11 容器（绑定 + 构造注入）、点语法配置、路由（`{name}` / `{id:\d+}`）、中间件管道、异常处理、PSR-3 日志、命令行。没有 Facade / AOP / 注解 DI。

本机默认 PHP 是 8.3，请用 **8.4** 运行（WAMP 里有 `D:\wamp64\bin\php\php8.4.15\php.exe`）：

```bash
composer install
php bin/sunny serve
```

- `http://127.0.0.1:8000/` → `{"name":"SunnyPHP","message":"Hello, World",...}`
- `http://127.0.0.1:8000/hello/sunny` → `{"message":"Hello, sunny"}`
- 未知路径返回 404 JSON

`php vendor/bin/phpunit`：**20 tests, 47 assertions**，全部通过。

常用命令：`php bin/sunny list`、`php bin/sunny route:list`。业务路由写在 `routes/web.php`，全局中间件在 `config/app.php`。数据库 / Cache / Queue 按计划留到第二期。


第二期能力已经接到 SunnyPHP 0.2.0，测试 **35 tests, 108 assertions** 全部通过。

| 模块 | 能力 | 用法 |
|------|------|------|
| **Database** | PDO + Query Builder + Model，默认 SQLite | `DB::table('users')->where(...)` / `User::create()` |
| **Cache** | array / file，PSR-16 + `remember` | `Cache::remember('k', 60, fn () => ...)` |
| **Session** | 文件/数组，`StartSession` 中间件 | `Session::set('locale', 'zh')` |
| **Validation** | `required/email/min/confirmed/...`，失败 422 | `Validator::validate($data, $rules)` |
| **Auth** | Session 登录，`Authenticate` 中间件 401 | `Auth::attempt(['email' => ..., 'password' => ...])` |
| **Queue** | sync / file / database | `Queue::push(PingJob::class, [...])` |
| **AOP** | `#[Pointcut]` + 运行时代理（目标类不能 `final`） | `config/aop.php` 注册切面 |
| **Facade** | `App/Config/DB/Cache/Session/Auth/Validator/Queue/Log` | 必须先 `boot()` |

新命令：`php bin/sunny migrate`、`cache:clear`、`queue:work --once`。

示例：`http://127.0.0.1:8000/demo/aop` 会走 `LogAspect` 拦截 `DemoService::ping()`。默认库是 `storage/database.sqlite`，改 MySQL 编辑 `config/database.php`。


