<?php

require_once __DIR__ . '/../vendor/autoload.php';

//引入 php-amqplib 类库并且 使用必要的类：
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


//创建一个 RabbitMQ 的连接：
$connection = new AMQPStreamConnection('192.168.88.130', 5672, 'guest', 'guest');
$channel = $connection->channel();


$exchange = 'order_eval'; //交换机名
$exchange_type = 'direct'; //交换机类型
$durable = true; //标识是否持久化
$routing_key = '';//路由关键字
//声明一个队列给我们发送消息使用；然后我们就可以将消息发送到队列中;第四个参数标识是否持久化【durable】
$channel->exchange_declare($exchange, $exchange_type, false, $durable, false);
//设为confirm模式
$channel->confirm_select();

//推送的信息内容
$data = array(
    'eval_type' => 1,
    'identifying' => 77,
    'nickname' => 'nickname',
    'rider_id' => 21,
    'dispatch_no' => '20180427105905',
    'score' => '3.5',
    'tag_ids' => '9,10',
    'is_anonymou' => 0,
    'content' => '提交的内容',
    'add_time' => date('Y-m-d H:i:s')
);
echo $data = json_encode($data);
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, $exchange, $routing_key);
//echo " send message :".$data." \n";

//消息发送状态回调(成功回调)
$channel->set_ack_handler(function (AMQPMessage $message) {
    echo "success:" . $message->body;
});
//失败回调
$channel->set_nack_handler(function (AMQPMessage $message) {
    echo "fail:" . $message->body;
});
$channel->wait_for_pending_acks();
$channel->close();
$connection->close();
