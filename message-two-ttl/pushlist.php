<?php
#参考文档：https://www.cnblogs.com/Zhangcsc/p/11714932.html
#给队列信息设置过期时间
require_once __DIR__ . '/../vendor/autoload.php';

//引入 php-amqplib 类库并且 使用必要的类：
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


//创建一个 RabbitMQ 的连接：
$connection = new AMQPStreamConnection('192.168.88.130', 5672, 'guest', 'guest');
$channel = $connection->channel();


$channel->exchange_declare('order_list_exchange.dlx', 'direct', false, true);
$channel->exchange_declare('order_list_exchange.normal', 'fanout', false, true);
$args = new AMQPTable();
$args->set('x-dead-letter-exchange', 'order_list_exchange.dlx');
$args->set('x-dead-letter-routing-key', 'routingkey');
$channel->queue_declare('order_list_queue.normal', false, true, false, false, false, $args);
$channel->queue_declare('order_list_queue.dlx', false, true, false, false);

$channel->queue_bind('order_list_queue.normal', 'order_list_exchange.normal');
$channel->queue_bind('order_list_queue.dlx', 'order_list_exchange.dlx', 'routingkey');

$tls_arr=[
    10000,
    30000,
    10000,
    20000
];
$tls=$tls_arr[rand(0,3)];


//推送的信息内容
$data = array(
    'eval_type' => 1,
    'ttl'=>'过期时间'.$tls,
    'content' => '提交的内容',
    'add_time' => date('Y-m-d H:i:s')
);
echo $data = json_encode($data);
$message = new AMQPMessage($data,
    [
        'expiration' =>$tls
    ]
);
$channel->basic_publish($message, 'order_list_exchange.normal', 'rk');

$channel->close();
$connection->close();