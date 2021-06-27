<?php
/*
延迟交换机
地址：https://www.cnblogs.com/-mrl/p/11114116.html
地址：https://blog.csdn.net/qq_36025814/article/details/106681291
*/
require_once __DIR__ . '/../vendor/autoload.php';

//引入 php-amqplib 类库并且 使用必要的类：
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;


//创建一个 RabbitMQ 的连接：
$connection = new AMQPStreamConnection('192.168.88.130', 5672, 'guest', 'guest');
$channel = $connection->channel();


$exchange='order_delayed_exchange'; //交换机名
$exchange_type='x-delayed-message'; //交换机类型-延迟交换机
$durable = true; //标识是否持久化
$routing_key = 'order_delayed_key';//路由关键字
//声明一个队列给我们发送消息使用；然后我们就可以将消息发送到队列中;第四个参数标识是否持久化【durable】
$channel->exchange_declare(
    $exchange,
    //exchange类型为x-delayed-message
    $exchange_type,
    false,
    $durable,
    false,
    false,
    false,
    //此处是重点，$argument必须使用new AMQPTable()生成
    new AMQPTable([
        "x-delayed-type" => 'direct'
    ])
);
//设为confirm模式
$channel->confirm_select();

$tls_arr=[
    10000,
    30000,
    10000,
    20000
];
$tls=$tls_arr[rand(0,3)];
#$tls=20000;【可以自行测试，先推送过期时间是6s的数据，再推送2s过期的数据】
//推送的信息内容
$data = array(
    'eval_type' => 1,
    'ttl'=>'过期时间'.$tls,
    'content' => '提交的内容',
    'add_time' => date('Y-m-d H:i:s')
);

echo $data = json_encode($data);
$msg = new AMQPMessage(
    $data,
    [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        //此处是重点，设置延时时间，单位是毫秒 1s=1000ms,实例延迟20s
        'application_headers' => new AMQPTable([
            'x-delay' => $tls,
        ])
    ]
);
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
