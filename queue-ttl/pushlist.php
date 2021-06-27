<?php
#参考文档：https://www.cnblogs.com/Zhangcsc/p/11714932.html
#给队列设置过期时间
require_once __DIR__ . '/../vendor/autoload.php';

//引入 php-amqplib 类库并且 使用必要的类：
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


//创建一个 RabbitMQ 的连接：
$connection = new AMQPStreamConnection('192.168.88.130', 5672, 'guest', 'guest');
$channel = $connection->channel();


$channel->exchange_declare('exchange.dlx', 'direct', false, true);
$channel->exchange_declare('exchange.normal', 'fanout', false, true);
$args = new AMQPTable();
// 消息过期方式：设置 queue.normal 队列中的消息10s之后过期
$args->set('x-message-ttl', 10000);
// 设置队列最大长度方式： x-max-length
//$args->set('x-max-length', 1);
$args->set('x-dead-letter-exchange', 'exchange.dlx');
$args->set('x-dead-letter-routing-key', 'routingkey');
$channel->queue_declare('queue.normal', false, true, false, false, false, $args);
$channel->queue_declare('queue.dlx', false, true, false, false);

$channel->queue_bind('queue.normal', 'exchange.normal');
$channel->queue_bind('queue.dlx', 'exchange.dlx', 'routingkey');
$message = new AMQPMessage('Hello DLX Message-'.date('Y-m-d H;i:s'));
$channel->basic_publish($message, 'exchange.normal', 'rk');

$channel->close();
$connection->close();