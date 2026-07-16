Elasticsearch 功能已按计划全部实现，摘要如下。

## 实现概览

### 基础设施层
- 扩展 [`ElasticsearchSearchService`](app/Service/Elasticsearch/ElasticsearchSearchService.php)：`bulkIndex`、`ensureIndex`、`indexExists`、`mget`，`search()` 支持 `sort`
- 新增 [`config/autoload/elasticsearch_indices.php`](config/autoload/elasticsearch_indices.php)：4 个索引 mapping
- 扩展 [`config/autoload/elasticsearch.php`](config/autoload/elasticsearch.php)：特性开关与分词器配置

### 索引与搜索层
- **Index**：`Product/Brand/Category/Specification` DocumentBuilder + [`ElasticsearchIndexService`](app/Service/Elasticsearch/Index/ElasticsearchIndexService.php)
- **Search**：4 个 SearchService + [`ElasticsearchSearchSupport`](app/Service/Elasticsearch/Search/ElasticsearchSearchSupport.php)
- **Trait**：[`ElasticsearchSearchTrait`](app/Service/Elasticsearch/Traits/ElasticsearchSearchTrait.php)（特性开关 + MySQL 回退）

### 数据同步
- **AMQP**：4 组 Consumer/Producer（`{APP_NAME}.product.index.q` 等）
- **Publisher**：[`ElasticsearchIndexPublisher`](app/Service/Elasticsearch/ElasticsearchIndexPublisher.php)
- **XXL-Job**：[`ElasticsearchReindexJob`](app/Job/ElasticsearchReindexJob.php)（ensure indices + 分片全量重建）

### 业务接入
- `ProductService`、`ProductBrandService`、`ProductCategoryService`、`ProductSpecificationService` 的 `list()` 在有关键词且开关开启时走 ES
- 增删改后通过 AMQP 异步同步索引
- 级联更新：品牌/分类改名、分类转移、规格变更时触发关联商品 reindex

### 运维
- 新增 [`DemoController`](app/Controller/V1/Demo/DemoController.php)：`GET /v1/demo/ping`、`GET /v1/demo/health`（含 ES ping）
- 更新 [`.env.example`](.env.example)

## 启用方式

```env
ELASTICSEARCH_SEARCH_ENABLED=true
ELASTICSEARCH_FALLBACK_ON_ERROR=true
ELASTICSEARCH_ANALYZER=standard   # 生产装 IK 后改为 ik_max_word
```

1. 启动 ES：`docker-compose -f docker-compose-es-dev.yaml up -d`
2. 执行 XXL-Job `merchantEsEnsureIndicesJob` 创建索引
3. 执行各 `merchant*ReindexJob` 灌库

## 验证

- PHPStan：通过
- 单元测试：7 个 ES 相关测试全部通过

**说明**：`composer test` 因项目 bootstrap 缺少 `Hyperf\Engine\DefaultOption` 无法整体运行，与本次改动无关；ES 相关测试已用 `phpunit` 单独验证通过。