# component-creater

## 功能
* 该组件主要是在amqp组件的基础上做扩展，可以对rabbitmq队列进行自动重试，还可以手动重试；
### 下载安装包
```
composer require eric-strive/amqp-retry
```
### 同步配置
```bash
php bin/hyperf.php vendor:publish eric-strive/amqp-retry
```
* 根剧自己的要求修改配置
```bash
retry_times 自动重试次数 
retry_times_interval 每次重试间隔时间基数 间隔时间=times*retry_times_interval
比如retry_times_interval设置的100 第二次重试时间隔的时间就是2*100 200秒
```
### 新建数据表
```bash
php bin/hyperf.php migrate
```
### 重试脚本执行
```bash
php bin/hyperf.php amqp:retry exchange routing_key
exchange 交换器 可不填 不填就会重试所有状态为error和terminated
routing_key 路由键名称
```
* 注意
```bash
config/autoload/amqp.php 配置文件中将 close_on_destruct 改为 false ;执行重试脚本时会报错
```
