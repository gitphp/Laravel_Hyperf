# SunnyPHP

基于 PHP 8.4 的轻量微框架：容器、路由、中间件、配置、异常、日志、命令行，以及数据库、Cache、Session、Validation、Auth、Queue、AOP、Facade。

## 要求

- PHP 8.4+
- Composer 2
- PDO（默认 SQLite，可选 MySQL）

## 安装与运行

```bash
composer install
php bin/sunny migrate
php bin/sunny serve
```

访问：

- `http://127.0.0.1:8000/` — Hello World JSON
- `http://127.0.0.1:8000/hello/sunny` — 路径参数 JSON
- `http://127.0.0.1:8000/demo/aop` — AOP 拦截示例

```bash
php bin/sunny list
php bin/sunny route:list
php bin/sunny cache:clear
php bin/sunny queue:work --once
php vendor/bin/phpunit
```

## 目录

```text
public/index.php     HTTP 入口
bin/sunny            CLI 入口
config/              应用配置
routes/web.php       路由
database/migrations  迁移
app/                 示例应用
src/                 框架内核
tests/               单元测试
```

## 数据库

```php
use SunnyPHP\Facade\DB;
use App\Model\User;

DB::table('users')->where('email', 'ada@example.com')->first();
User::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);
```

默认 SQLite：`storage/database.sqlite`。`config/database.php` 可改为 MySQL。

## Cache / Session

```php
use SunnyPHP\Facade\Cache;
use SunnyPHP\Facade\Session;

Cache::remember('stats', 60, fn () => ['ok' => true]);
Session::set('locale', 'zh');
```

Session 由 `StartSession` 中间件启动，默认写文件。

## Validation / Auth

```php
use SunnyPHP\Facade\Validator;
use SunnyPHP\Facade\Auth;

$validated = Validator::validate($request->request, [
    'email' => 'required|email',
    'password' => 'required|min:6',
]);

Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']]);
```

校验失败抛出 422。路由中间件 `SunnyPHP\Auth\Authenticate` 可保护需要登录的接口。

## Queue

```php
use SunnyPHP\Facade\Queue;
use App\Jobs\PingJob;

Queue::push(PingJob::class, ['message' => 'hello']);
```

驱动：`sync`（默认立即执行）、`file`、`database`。

## AOP

切面实现 `SunnyPHP\Aop\AspectInterface`，用 `#[Pointcut]` 标注目标类/方法，在 `config/aop.php` 注册。目标类不能是 `final`。

## Facade

`App`、`Config`、`DB`、`Cache`、`Session`、`Auth`、`Validator`、`Queue`、`Log` 通过容器解析，无魔术服务定位器之外的隐藏依赖：必须先 `Application::boot()`。
