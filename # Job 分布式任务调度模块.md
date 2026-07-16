# Job 分布式任务调度模块

## 概述

本模块基于 XXL-Job 提供分布式任务调度功能，支持可视化管理、分片执行、失败重试等高级特性。

## 适用场景

优先使用 XXL-Job 的场景：
- 系统是分布式架构（多服务实例部署）
- 需要可视化管理任务（配置、监控、日志）
- 任务需要分片执行、依赖调度或复杂的失败重试策略
- 需避免任务在多实例上重复执行

## 特点


- **分布式调度**：支持多实例部署，任务统一调度
- **可视化管理**：通过 Web 界面管理任务配置、监控执行状态
- **分片执行**：支持大数据量任务分片处理
- **失败重试**：支持任务失败自动重试机制
- **日志管理**：完整的任务执行日志记录和查看
- **动态配置**：支持任务动态启停和参数调整

## 配置说明

### 1. 环境变量配置

在 `.env` 文件中配置以下参数：

```env
# 启用 XXL-Job
XXL_JOB_ENABLE=true

# XXL-Job 管理端地址
XXL_JOB_ADMIN_ADDRESS=http://127.0.0.1:8080/xxl-job-admin

# 应用名称（在管理端注册的名称）
XXL_JOB_APP_NAME=adc-skeleton

# 访问令牌
XXL_JOB_ACCESS_TOKEN=your_access_token

# 心跳间隔（秒）
XXL_JOB_HEARTBEAT=30

# 执行器路由前缀
XXL_JOB_EXECUTOR_PREFIX_URL=php-xxl-job
```

### 2. 配置文件

文件位置：`config/autoload/xxl_job.php`

```php
return [
    'enable' => env('XXL_JOB_ENABLE', true),
    'admin_address' => env('XXL_JOB_ADMIN_ADDRESS', 'http://127.0.0.1:8080/xxl-job-admin'),
    'app_name' => env('XXL_JOB_APP_NAME', 'xxl-job-demo'),
    'access_token' => env('XXL_JOB_ACCESS_TOKEN', ''),
    'heartbeat' => env('XXL_JOB_HEARTBEAT', 30),
    'log_retention_days' => 30,
    'executor_server' => [
        'prefix_url' => env('XXL_JOB_EXECUTOR_PREFIX_URL', 'php-xxl-job'),
    ],
    'guzzle_config' => [
        'headers' => [
            'charset' => 'UTF-8',
        ],
        'timeout' => 10,
    ],
    'file_logger' => [
        'dir' => BASE_PATH.'/runtime/xxl_job/logs/',
    ],
];
```

## 使用方式

### 1. Bean 模式（推荐）

#### 创建任务类

```php
<?php

namespace App\Job;

use Hyperf\Di\Annotation\Inject;
use Hyperf\XxlJob\Annotation\XxlJob;
use Hyperf\XxlJob\Logger\JobExecutorLoggerInterface;
use Hyperf\XxlJob\Requests\RunRequest;

class DemoJob
{
    #[Inject]
    protected JobExecutorLoggerInterface $jobExecutorLogger;
    
    /**
     * 示例任务处理器
     */
    #[XxlJob('demoJobHandler')]
    public function demoJobHandler(RunRequest $request)
    {
        // 获取任务参数
        $params = $request->getExecutorParams();
        $logId = $request->getLogId();
        
        $this->jobExecutorLogger->info('任务开始执行，参数: ' . $params);
        
        // 任务逻辑
        for ($i = 1; $i <= 5; $i++) {
            sleep(1);
            $this->jobExecutorLogger->info("执行步骤 {$i}/5");
        }
        
        $this->jobExecutorLogger->info('任务执行完成');
    }
    
    /**
     * 分片广播任务示例
     */
    #[XxlJob('shardingJobHandler')]
    public function shardingJobHandler(RunRequest $request)
    {
        $shardIndex = $request->getShardingParam();
        $shardTotal = $request->getShardingTotal();
        
        $this->jobExecutorLogger->info("分片任务执行: {$shardIndex}/{$shardTotal}");
        
        // 根据分片索引处理对应的数据
        // 例如：处理 ID % shardTotal == shardIndex 的数据
    }
}
```

#### 使用自定义注解

```php
<?php

namespace App\Job;

use App\Annotation\XxlJobTask;
use Hyperf\XxlJob\Logger\JobExecutorLoggerInterface;
use Hyperf\XxlJob\Requests\RunRequest;

#[XxlJobTask(
    jobDesc: '用户数据同步任务',
    cron: '0 0 2 * * ?',
    jobHandler: 'userSyncHandler',
    jobTimeout: 3600,
    jobRetry: 3
)]
class UserSyncJob
{
    #[Inject]
    protected JobExecutorLoggerInterface $jobExecutorLogger;
    
    public function userSyncHandler(RunRequest $request)
    {
        $this->jobExecutorLogger->info('开始同步用户数据');
        
        // 同步逻辑
        // ...
        
        $this->jobExecutorLogger->info('用户数据同步完成');
    }
}
```

### 2. 方法模式

在任意类的公共方法上添加注解：

```php
<?php

namespace App\Service;

use Hyperf\XxlJob\Annotation\XxlJob;
use Hyperf\XxlJob\Logger\JobExecutorLoggerInterface;
use Hyperf\XxlJob\Requests\RunRequest;

class DataProcessService
{
    #[Inject]
    protected JobExecutorLoggerInterface $jobExecutorLogger;
    
    #[XxlJob('dataProcessHandler')]
    public function processData(RunRequest $request)
    {
        $params = $request->getExecutorParams();
        $this->jobExecutorLogger->info('数据处理任务: ' . $params);
        
        // 数据处理逻辑
    }
}
```

## 注解参数说明

### XxlJobTask 注解参数

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `xxlVersion` | string | '3.2.0' | XXL-Job 版本 |
| `author` | string | '机器注册(adc)' | 任务负责人 |
| `jobDesc` | string | '' | 任务描述 |
| `scheduleType` | string | 'CRON' | 调度类型 |
| `cron` | string | '' | Cron 表达式 |
| `jobHandler` | string | '' | 任务处理器名称 |
| `jobParam` | string | '' | 任务参数 |
| `routeStrategy` | string | '' | 路由策略 |
| `jobTimeout` | int | 0 | 任务超时时间（秒） |
| `jobRetry` | int | 0 | 失败重试次数 |

## 管理端配置

### 1. 在 XXL-Job 管理端创建任务

1. 登录 XXL-Job 管理端
2. 进入"任务管理"页面
3. 点击"新增"按钮
4. 填写任务信息：
   - **任务描述**：任务说明
   - **Cron**：调度表达式
   - **运行模式**：选择 "BEAN模式"
   - **JobHandler**：填写注解中定义的处理器名称
   - **执行器**：选择对应的应用
   - **路由策略**：选择任务分发策略
   - **任务超时时间**：设置任务执行超时时间
   - **失败重试次数**：设置失败重试次数

### 2. 常用路由策略

- **第一个**：固定选择第一个执行器
- **最后一个**：固定选择最后一个执行器
- **轮询**：依次选择执行器
- **随机**：随机选择执行器
- **一致性HASH**：根据任务参数进行一致性HASH
- **最不经常使用**：选择最不经常使用的执行器
- **最近最久未使用**：选择最近最久未使用的执行器
- **故障转移**：按照顺序依次进行心跳检测，第一个心跳检测成功的机器选定为目标执行器并发起调度
- **忙碌转移**：按照顺序依次进行空闲检测，第一个空闲检测成功的机器选定为目标执行器并发起调度
- **分片广播**：广播触发对应集群中所有机器执行一次任务

## Cron 表达式

XXL-Job 使用 6 位 Cron 表达式：`秒 分 时 日 月 周`

常用示例：
- `0 0 2 * * ?` - 每天凌晨2点执行
- `0 0/30 * * * ?` - 每30分钟执行一次
- `0 0 12 * * ?` - 每天中午12点执行
- `0 15 10 ? * MON-FRI` - 周一到周五上午10:15执行
- `0 0 0 1 * ?` - 每月1号凌晨执行

## 日志管理

### 1. 任务日志

任务执行日志会记录到以下位置：
- 管理端：可在任务管理页面查看
- 本地文件：`runtime/xxl_job/logs/` 目录

### 2. 日志级别

- **INFO**：一般信息
- **WARN**：警告信息
- **ERROR**：错误信息

### 3. 日志示例

```php
$this->jobExecutorLogger->info('任务开始执行');
$this->jobExecutorLogger->warn('发现异常情况，继续执行');
$this->jobExecutorLogger->error('任务执行失败: ' . $exception->getMessage());
```

## 注意事项

1. **任务幂等性**：确保任务可以重复执行而不产生副作用
2. **异常处理**：在任务中妥善处理异常，避免任务异常退出
3. **资源管理**：及时释放数据库连接、文件句柄等资源
4. **超时设置**：根据任务复杂度合理设置超时时间
5. **分片任务**：分片任务需要确保数据处理的完整性
6. **日志记录**：合理使用日志记录，避免日志过多影响性能

## 与 Task 模块对比

| 特性 | Task 模块 | Job 模块 |
|------|-----------|----------|
| 部署方式 | 单体部署 | 分布式部署 |
| 任务管理 | 代码配置 | 可视化配置 |
| 任务监控 | 控制台日志 | Web 界面监控 |
| 失败重试 | 不支持 | 支持 |
| 分片执行 | 不支持 | 支持 |
| 动态配置 | 不支持 | 支持 |
| 适用场景 | 简单定时任务 | 复杂分布式任务 |

## 示例文件

- `XxlJobTask.php`: 自定义 XXL-Job 注解
- 参考 `Task` 模块了解基础定时任务用法