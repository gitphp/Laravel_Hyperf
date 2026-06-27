# 新功能开发一套完整的CURD指南

根据项目现有架构，我将为您提供一个完整的后端API开发流程，以"费用类型管理"为例进行演示。

---

## 一、开发流程概览

```
1. 创建 Model（数据模型）
2. 创建 Service（业务逻辑层）
3. 创建 Request（请求验证）
4. 创建 Resource（响应资源）
5. 创建 Controller（控制器）
```

---

## 二、详细步骤

### 1. 创建 Model（数据模型）

**文件路径**：`app/Model/ExpenseType.php`

```php
<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $code
 * @property string $description
 * @property int $is_enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ExpenseType extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'expense_type';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uid',
        'name',
        'code',
        'description',
        'is_enabled',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'is_enabled' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

---

### 2. 创建 Service（业务逻辑层）

**文件路径**：`app/Service/ExpenseTypeService.php`

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\ExpenseType;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Snowflake\IdGeneratorInterface;
use TgkwAdc\Exception\BusinessException;

class ExpenseTypeService extends BaseService
{
    #[Inject]
    protected IdGeneratorInterface $idGenerator;

    /**
     * 获取费用类型列表
     *
     * @param array<string, mixed> $params
     * @return \Hyperf\Paginator\Paginator
     */
    public function list(array $params)
    {
        $query = ExpenseType::query();

        // 按名称筛选
        if (!empty($params['name'])) {
            $query->where('name', 'like', '%' . $params['name'] . '%');
        }

        // 按编码筛选
        if (!empty($params['code'])) {
            $query->where('code', 'like', '%' . $params['code'] . '%');
        }

        // 按状态筛选
        if (isset($params['is_enabled'])) {
            $query->where('is_enabled', (int) $params['is_enabled']);
        }

        return $query->orderBy('created_at', 'desc')->paginate((int) ($params['per_page'] ?? 20));
    }

    /**
     * 获取单个费用类型详情
     */
    public function detail(string $uid): ExpenseType
    {
        $model = ExpenseType::query()->where('uid', $uid)->first();
        
        if (!$model) {
            throw new BusinessException('费用类型不存在');
        }

        return $model;
    }

    /**
     * 创建费用类型
     *
     * @param array<string, mixed> $params
     * @return ExpenseType
     */
    public function create(array $params): ExpenseType
    {
        // 检查编码是否重复
        if (ExpenseType::query()->where('code', $params['code'])->exists()) {
            throw new BusinessException('编码已存在');
        }

        return ExpenseType::create([
            'uid' => (string) $this->idGenerator->generate(),
            'name' => $params['name'],
            'code' => $params['code'],
            'description' => $params['description'] ?? '',
            'is_enabled' => $params['is_enabled'] ?? 1,
        ]);
    }

    /**
     * 更新费用类型
     *
     * @param array<string, mixed> $params
     * @return ExpenseType
     */
    public function update(string $uid, array $params): ExpenseType
    {
        $model = $this->detail($uid);

        // 检查编码是否重复（排除自身）
        if (!empty($params['code']) && ExpenseType::query()
            ->where('code', $params['code'])
            ->where('uid', '<>', $uid)
            ->exists()) {
            throw new BusinessException('编码已存在');
        }

        $model->update($params);

        return $model;
    }

    /**
     * 删除费用类型
     */
    public function delete(string $uid): void
    {
        $model = $this->detail($uid);
        $model->delete();
    }
}
```

---

### 3. 创建 Request（请求验证）

**文件路径**：`app/Request/V1/ExpenseTypeRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Request\V1;

use App\Request\BaseRequest;

class ExpenseTypeRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 列表查询
            'list' => [
                'name' => 'nullable|string|max:50',
                'code' => 'nullable|string|max:20',
                'is_enabled' => 'nullable|integer|in:0,1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ],
            // 创建
            'create' => [
                'name' => 'required|string|max:50',
                'code' => 'required|string|max:20',
                'description' => 'nullable|string|max:200',
                'is_enabled' => 'nullable|integer|in:0,1',
            ],
            // 更新
            'update' => [
                'name' => 'nullable|string|max:50',
                'code' => 'nullable|string|max:20',
                'description' => 'nullable|string|max:200',
                'is_enabled' => 'nullable|integer|in:0,1',
            ],
            // 详情/删除
            'detail' => [],
        ][$this->getScene()] ?? [];
    }
}
```

---

### 4. 创建 Resource（响应资源）

**文件路径**：`app/Resource/Application/V1/ExpenseTypeResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Resource\Application\V1;

use TgkwAdc\Resource\BaseResource;

/**
 * 费用类型资源
 */
class ExpenseTypeResource extends BaseResource
{
    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_enabled' => (bool) $this->is_enabled,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

**文件路径**：`app/Resource/Application/V1/ExpenseTypeCollection.php`

```php
<?php

declare(strict_types=1);

namespace App\Resource\Application\V1;

use TgkwAdc\Resource\BaseCollection;

/**
 * 费用类型集合
 */
class ExpenseTypeCollection extends BaseCollection
{
    public $collects = ExpenseTypeResource::class;
}
```

---

### 5. 创建 Controller（控制器）

**文件路径**：`app/Controller/V1/Expense/ExpenseTypeController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\V1\Expense;

use App\Request\V1\ExpenseTypeRequest;
use App\Resource\Application\V1\ExpenseTypeCollection;
use App\Resource\Application\V1\ExpenseTypeResource;
use App\Service\ExpenseTypeService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\Validation\Annotation\Scene;
use Psr\Http\Message\ResponseInterface;
use TgkwAdc\Annotation\OrgPermission;
use TgkwAdc\Helper\ApiResponseHelper;

#[Controller('v1')]
class ExpenseTypeController extends AbstractController
{
    #[Inject]
    protected ExpenseTypeService $service;

    /**
     * 获取费用类型列表
     */
    #[OrgPermission(
        module: '费用管理:费用类型',
        type: 'MENU',
        i18nName: ['en' => 'Expense Type List', 'zh_cn' => '费用类型列表', 'zh_hk' => '費用類型列表'],
        sort: 100,
        parentAccessCode: 'expense',
        accessCode: 'expense:type-list',
        frontRouteAlias: 'expense.type-list',
    )]
    #[GetMapping(path: 'expense-types')]
    #[Scene(scene: 'list')]
    public function list(ExpenseTypeRequest $request): ResponseInterface
    {
        $params = $request->all();
        $result = $this->service->list($params);

        return ApiResponseHelper::success(ExpenseTypeCollection::make($result));
    }

    /**
     * 获取费用类型详情
     */
    #[OrgPermission(
        module: '费用管理:费用类型:详情',
        type: 'BUTTON',
        i18nName: ['en' => 'Expense Type Detail', 'zh_cn' => '费用类型详情', 'zh_hk' => '費用類型詳情'],
        sort: 101,
        parentAccessCode: 'expense',
        accessCode: 'expense:type-detail',
        frontRouteAlias: 'expense.type-detail',
    )]
    #[GetMapping(path: 'expense-types/{uid}')]
    #[Scene(scene: 'detail')]
    public function detail(string $uid): ResponseInterface
    {
        $result = $this->service->detail($uid);

        return ApiResponseHelper::success(ExpenseTypeResource::make($result));
    }

    /**
     * 创建费用类型
     */
    #[OrgPermission(
        module: '费用管理:费用类型:创建',
        type: 'BUTTON',
        i18nName: ['en' => 'Create Expense Type', 'zh_cn' => '创建费用类型', 'zh_hk' => '創建費用類型'],
        sort: 102,
        parentAccessCode: 'expense',
        accessCode: 'expense:type-create',
        frontRouteAlias: 'expense.type-create',
    )]
    #[PostMapping(path: 'expense-types')]
    #[Scene(scene: 'create')]
    public function create(ExpenseTypeRequest $request): ResponseInterface
    {
        $params = $request->validated();
        $result = $this->service->create($params);

        return ApiResponseHelper::success(ExpenseTypeResource::make($result));
    }

    /**
     * 更新费用类型
     */
    #[OrgPermission(
        module: '费用管理:费用类型:更新',
        type: 'BUTTON',
        i18nName: ['en' => 'Update Expense Type', 'zh_cn' => '更新费用类型', 'zh_hk' => '更新費用類型'],
        sort: 103,
        parentAccessCode: 'expense',
        accessCode: 'expense:type-update',
        frontRouteAlias: 'expense.type-update',
    )]
    #[PutMapping(path: 'expense-types/{uid}')]
    #[Scene(scene: 'update')]
    public function update(string $uid, ExpenseTypeRequest $request): ResponseInterface
    {
        $params = $request->validated();
        $result = $this->service->update($uid, $params);

        return ApiResponseHelper::success(ExpenseTypeResource::make($result));
    }

    /**
     * 删除费用类型
     */
    #[OrgPermission(
        module: '费用管理:费用类型:删除',
        type: 'BUTTON',
        i18nName: ['en' => 'Delete Expense Type', 'zh_cn' => '删除费用类型', 'zh_hk' => '刪除費用類型'],
        sort: 104,
        parentAccessCode: 'expense',
        accessCode: 'expense:type-delete',
        frontRouteAlias: 'expense.type-delete',
    )]
    #[DeleteMapping(path: 'expense-types/{uid}')]
    #[Scene(scene: 'detail')]
    public function delete(string $uid): ResponseInterface
    {
        $this->service->delete($uid);

        return ApiResponseHelper::success(['deleted' => true]);
    }
}
```

---

## 三、API 接口汇总

| 方法 | 路由 | 场景 | 说明 |
|------|------|------|------|
| GET | `/v1/expense-types` | list | 获取列表 |
| GET | `/v1/expense-types/{uid}` | detail | 获取详情 |
| POST | `/v1/expense-types` | create | 创建 |
| PUT | `/v1/expense-types/{uid}` | update | 更新 |
| DELETE | `/v1/expense-types/{uid}` | detail | 删除 |

---

## 四、关键注意事项

### 1. 依赖注入
使用 `#[Inject]` 注解注入 Service：
```php
#[Inject]
protected ExpenseTypeService $service;
```

### 2. 权限控制
使用 `#[OrgPermission]` 注解配置权限：
```php
#[OrgPermission(
    module: '模块名称',
    type: 'MENU|BUTTON',
    i18nName: ['en' => '...', 'zh_cn' => '...', 'zh_hk' => '...'],
    sort: 100,
    parentAccessCode: '父权限码',
    accessCode: '权限码',
    frontRouteAlias: '前端路由别名',
)]
```

### 3. 请求验证
使用 `#[Scene]` 注解指定验证场景：
```php
#[Scene(scene: 'create')]
```

### 4. 响应格式
使用 `ApiResponseHelper::success()` 返回统一格式：
```php
return ApiResponseHelper::success(Resource::make($data));
```

### 5. 资源转换
使用 Resource 类统一响应格式，便于维护和版本管理。

---

## 五、数据库迁移（可选）

如果需要创建新表，需要编写迁移文件：

```php
<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateExpenseTypeTable extends Migration
{
    public function up(): void
    {
        Schema::create('expense_type', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uid', 36)->unique()->comment('唯一标识');
            $table->string('name', 50)->comment('名称');
            $table->string('code', 20)->unique()->comment('编码');
            $table->string('description', 200)->nullable()->comment('描述');
            $table->tinyInteger('is_enabled')->default(1)->comment('是否启用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_type');
    }
}
```