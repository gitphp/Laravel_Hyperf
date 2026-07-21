# 一、逐行参数完整解析 http://172.24.65.40:9200/ 主URL地址返回信息
访问 `9200` 是 Elasticsearch **服务根节点健康信息接口**，不是数据查询页面，只会返回集群/版本元信息，不会展示索引数据。
```json
{
  "name": "43aef8dc7e1d",
  // 当前ES节点名称，Docker容器随机生成的ID，每个节点唯一标识
  "cluster_name": "docker-cluster",
  // 集群名称，多节点ES归属于同一个集群的标识，默认docker-cluster
  "cluster_uuid": "wH9oFC-2TcyZeWGDi_321w",
  // 集群唯一UUID，集群创建时自动生成，集群内所有节点共用同一个uuid
  "version": {
    "number": "7.17.18",
    // Elasticsearch主版本号：7.17.18，7.x系列最后长期维护版本
    "build_flavor": "default",
    // 构建版本类型：default=官方标准版，无特殊定制
    "build_type": "docker",
    // 部署方式：当前是Docker镜像运行的ES
    "build_hash": "8682172c2130b9a411b1bd5ff37c9792367de6b0",
    // 源码提交哈希，定位该版本编译对应的代码提交点
    "build_date": "2024-02-02T12:04:59.691750271Z",
    // 该安装包编译构建的UTC时间
    "build_snapshot": false,
    // 是否快照测试版：false=正式稳定发行版，true=开发测试快照
    "lucene_version": "8.11.1",
    // ES底层依赖的Lucene搜索引擎内核版本，ES基于Lucene实现检索
    "minimum_wire_compatibility_version": "6.8.0",
    // 节点通信最低兼容版本：本节点能和6.8.0及以上版本ES节点互通组网
    "minimum_index_compatibility_version": "6.0.0-beta1"
    // 索引兼容最低版本：可以读取6.0.0-beta1及以上版本创建的索引数据
  },
  "tagline": "You Know, for Search"
  // ES官方标语：ES就是用来做搜索的，固定文案
}
```

## 2. 想要查看索引/数据，需要调用对应API
### ① 查看所有索引列表
```
http://172.24.65.40:9200/_cat/indices?v
```
### ② 查询某个索引下的全部数据（替换索引名test_index）
```
http://172.24.65.40:9200/test_index/_search
```
### ③ 可视化界面查看数据（推荐）
ES原生无Web可视化面板，需要配套工具：
1. **Kibana**（配套ES官方可视化，端口5601），可以图形化管理索引、查数据、建检索语句；
2. 第三方工具：elasticsearch-head、Dejavu、Postman/ApiFox 发请求调试。


# 接口地址说明【查看索引列表】
`http://172.24.65.40:9200/_cat/indices?v`
`/_cat/indices` 是ES查看**所有索引列表**的API，`?v` 代表显示表头，用来查看当前集群里全部索引的状态、数据量、存储大小。

## 逐字段解析表格
```
health status index               uuid                   pri rep docs.count docs.deleted store.size pri.store.size
green  open   .geoip_databases    bs2gBieNQBm7y05CZDKyyA  1   0        43            0      39.8mb         39.8mb
```

### 1. health 集群健康状态（索引分片健康度）
- `green`：绿色，**完全健康**。所有主分片+副本分片都正常分配，无丢失分片。
- `yellow`：黄色，主分片正常，副本分片缺失（单节点ES默认都是yellow，因为副本数rep=1但没第二台机器存副本）。
- `red`：红色，主分片丢失，索引数据损坏/不可查询。
你这里是 `green`，代表该索引分片全部正常。

### 2. status 索引状态
- `open`：索引正常打开，可读写、查询数据。
- `close`：索引关闭，无法读写，仅保留磁盘文件。

### 3. index 索引名称
`.geoip_databases`：ES内置的GeoIP地理位置库索引，**系统内置索引，不是你业务创建的表**，用来根据IP解析归属地。
前缀带 `.` 的都是ES系统自带索引。

### 4. uuid 索引唯一ID
每个索引创建时自动生成的唯一标识，无需关注。

### 5. pri（primary）主分片数量
值 `1`：该索引分配了**1个主分片**，数据拆分在1个主分片里。

### 6. rep（replica）副本分片数量
值 `0`：没有创建副本分片，仅存一份原始数据。
> 多节点集群建议 rep=1，主分片丢失可以用副本恢复。

### 7. docs.count 文档总数
`43`：这个索引里一共存了43条数据（类似数据库43行记录）。

### 8. docs.deleted 已删除文档数
`0`：没有删除过文档，ES删除不会立刻擦除，只会标记，合并段时才清理。

### 9. store.size 索引总占用磁盘大小
`39.8mb`：主分片+副本分片加起来总占用空间，你这里rep=0，所以和单主分片大小一致。

### 10. pri.store.size 仅主分片占用磁盘大小
`39.8mb`：只统计主分片的存储，副本不计入。

# 关键结论
1. 你当前ES集群**只有1个内置系统索引 `.geoip_databases`，没有任何业务索引**；
2. 集群状态健康（green），ES服务正常；
3. 没有自己创建的业务索引，Hyperf项目还没写入/创建搜索索引；

## 常用扩展操作
1. 创建业务索引：`PUT http://172.24.65.40:9200/你的索引名`
2. 查看该索引全部数据：`GET http://172.24.65.40:9200/.geoip_databases/_search`
3. 删除无用索引：`DELETE http://172.24.65.40:9200/索引名`



