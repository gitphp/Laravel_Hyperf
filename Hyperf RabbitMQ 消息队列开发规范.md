# Hyperf RabbitMQ 消息队列开发规范

> ⚠️ **重要提醒：必须先编写消费者，再编写生产者！**
>
> 这是消息队列开发的核心原则，确保消息能被正确处理，避免消息堆积和丢失。

## 目录结构

```
app/Amqp/
├── Consumer/          # 消息消费者
├── Producer/          # 消息生产者
└── readme.md         # 开发规范文档
```

## 1. 基本概念

### 1.1 RabbitMQ 组件
- **Producer**: 消息生产者，负责发送消息到 RabbitMQ
- **Consumer**: 消息消费者，负责从 RabbitMQ 消费消息
- **Exchange**: 交换机，负责消息路由（Direct、Fanout、Topic、Headers）
- **Queue**: 队列，存储消息的容器
- **Routing Key**: 路由键，用于消息路由
- **Virtual Host**: 虚拟主机，RabbitMQ 的逻辑隔离单元

### 1.2 RabbitMQ 交换机类型
- **Direct Exchange**: 直接交换机，根据路由键精确匹配
- **Fanout Exchange**: 扇形交换机，广播到所有绑定的队列
- **Topic Exchange**: 主题交换机，支持通配符匹配
- **Headers Exchange**: 头交换机，根据消息头属性路由

### 1.3 消息处理结果
- `Result::ACK`: 确认消息处理成功
- `Result::NACK`: 拒绝消息，不重新入队
- `Result::REJECT`: 拒绝消息，重新入队

## 2. 开发顺序规范

### 2.1 开发顺序原则
**重要：必须先编写消费者，再编写生产者**

#### 为什么先写消费者？
1. **确保消息能被正确处理** - 先确保有消费者能处理消息，避免消息堆积
2. **验证消息格式** - 通过消费者验证消息结构是否正确
3. **测试业务逻辑** - 先验证业务逻辑的正确性
4. **避免消息丢失** - 确保消息发送后能被及时消费
5. **便于调试** - 消费者先就位，便于调试生产者逻辑

#### 开发流程
```
1. 设计消息结构
2. 编写消费者（Consumer）
3. 测试消费者逻辑
4. 编写生产者（Producer）
5. 集成测试
6. 性能优化
```

## 3. 消费者开发规范

### 3.1 消费者类结构
请参照Demo

### 3.2 消费者开发步骤
1. **定义消息结构** - 明确消息的数据格式和字段
2. **创建消费者类** - 继承 `ConsumerMessage` 基类
3. **配置消费参数** - 使用 `#[Consumer]` 注解
4. **实现消费逻辑** - 编写 `consume()` 方法
5. **添加错误处理** - 处理异常和重试逻辑
6. **编写单元测试** - 测试消费者逻辑
7. **启动消费者** - 确保消费者正常运行

### 3.3 消费者配置规范
- 继承 `ConsumerMessage` 基类
- 使用 `#[Consumer]` 注解配置消费参数
- 实现 `consume()` 方法处理消息
- 消费者类名以 `Consumer` 结尾

### 3.4 消费者注解参数
- `exchange`: 交换机名称
- `routingKey`: 路由键
- `queue`: 队列名称（必须以服务名开始）
- `nums`: 消费者进程数量
- `enable`: 是否启用消费者

### 3.5 队列命名规范
**重要：所有队列名称必须以服务名（APP_NAME）开始**

#### 命名规则
```php
// 正确示例
queue: env('APP_NAME', 'skeleton') . '.project.demo.q'
queue: env('APP_NAME', 'skeleton') . '.user.notification.q'
queue: env('APP_NAME', 'skeleton') . '.order.process.q'

// 错误示例
queue: 'project.demo.q'  // 缺少服务名前缀
queue: 'user.notification.q'  // 缺少服务名前缀
```

#### 命名格式
```
{服务名}.{业务模块}.{具体功能}.q
```

- `{服务名}`: 使用 `env('APP_NAME', 'skeleton')` 获取
- `{业务模块}`: 业务模块名称，如 public、user、order 等
- `{具体功能}`: 具体功能描述，如 demo、notification、process 等
- `.q`: 队列标识后缀

#### 命名示例
```php
// 示例1：公共模块演示队列
#[Consumer(exchange: 'public.demo.ex', routingKey: 'public.demo.key', queue: 'public.demo.q', nums: 1, enable: true)]
class DemoConsumer extends ConsumerMessage
{
    
}

```


## 4. 生产者开发规范

### 4.1 生产者开发时机
**只有在消费者开发完成并测试通过后，才能开始编写生产者**

#### 生产者开发前置条件
- ✅ 消费者已开发完成
- ✅ 消费者已通过单元测试
- ✅ 消费者已启动并正常运行
- ✅ 消息格式已确定
- ✅ 业务逻辑已验证

### 4.2 生产者类结构


### 4.3 生产者使用规范
- 继承 `ProducerMessage` 基类
- 使用 `#[Producer]` 注解配置交换机和路由键
- 在构造函数中设置消息载荷
- 生产者类名以 `Producer` 结尾

### 4.4 发送消息示例
```php
use App\Amqp\Producer\DemoProducer;
use Hyperf\Amqp\Producer;

// 在服务中发送消息
$producer = ApplicationContext::getContainer()->get(Producer::class);
$producer->produce(new DemoProducer(['id' => 1, 'name' => 'test']));
```

## 5. 消息处理规范

### 5.1 消息处理流程
1. 接收消息
2. 验证消息格式
3. 执行业务逻辑
4. 返回处理结果

### 5.2 错误处理
```php
public function consume($data): Result
{
    try {
        // 业务逻辑处理
        $this->processMessage($data);
        return Result::ACK;
    } catch (\Throwable $e) {
        $this->logger->error('消息处理失败', [
            'data' => $data,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // 根据错误类型决定是否重新入队
        return $this->shouldRequeue($e) ? Result::REJECT : Result::NACK;
    }
}
```

### 5.3 消息幂等性
- 使用唯一标识符避免重复处理
- 实现消息去重机制
- 记录已处理消息状态


## 7. 监控和日志

### 7.1 日志记录
- 记录消息发送和接收日志
- 记录处理时间和结果
- 记录错误和异常信息

### 7.2 RabbitMQ 监控指标
- 消息发送速率
- 消息消费速率
- 消息处理成功率
- 队列长度监控
- 连接数监控
- 内存使用情况
- 磁盘使用情况
- 交换机状态

## 8. 最佳实践

### 8.1 性能优化
- 合理设置消费者数量
- 使用消息批处理
- 避免长时间阻塞操作
- 合理设置心跳时间

### 8.2 RabbitMQ 可靠性保证
- 实现消息确认机制（Publisher Confirms）
- 设置消息过期时间（TTL）
- 实现死信队列（Dead Letter Queue）
- 定期清理过期消息
- 使用持久化队列和消息
- 配置集群和高可用

### 8.3 开发建议
- 消息体保持简洁
- 使用版本控制消息格式
- 实现消息重试机制
- 编写单元测试
- **队列命名必须以服务名开始** - 使用 `env('APP_NAME', 'skeleton')` 作为前缀

## 9. RabbitMQ 常见问题

### 9.1 消息丢失
- 检查消息确认机制（Publisher Confirms）
- 验证交换机配置
- 检查网络连接
- 确认队列持久化设置
- 检查虚拟主机权限

### 9.2 消息重复
- 实现消息去重
- 检查消费者配置
- 验证消息处理逻辑
- 使用消息ID进行幂等性控制

### 9.3 性能问题
- 调整并发设置
- 优化消息处理逻辑
- 检查资源使用情况
- 调整 RabbitMQ 内存和磁盘限制
- 优化交换机类型选择

### 9.4 连接问题
- 检查 RabbitMQ 服务状态
- 验证用户权限和虚拟主机
- 检查防火墙设置
- 调整心跳和超时配置

### 9.5 队列积压
- 增加消费者数量
- 优化消息处理速度
- 检查消费者是否正常运行
- 考虑使用消息优先级