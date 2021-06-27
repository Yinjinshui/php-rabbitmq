<?php
/*
延迟交换机
安装延迟交换机：https://blog.csdn.net/qq_36025814/article/details/106647467
地址：https://www.cnblogs.com/-mrl/p/11114116.html
地址：https://blog.csdn.net/qq_36025814/article/details/106681291
*/
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

try {
    $connection = new AMQPStreamConnection('192.168.88.130', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $exchange = 'order_delayed_exchange'; //交换机名
    $queue_name = 'order_delayed_queue'; //消息队列载体
    $exchange_type = 'x-delayed-message'; //交换机类型-延迟交换机
    $routing_key = 'order_delayed_key'; //路由关键字
    $durable = true; //是否持久化 true false


//第四个参数标识是否持久化【durable】
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
//第三个参数标识是否持久化【durable】
    $channel->queue_declare($queue_name, false, $durable, false, false);
    $channel->queue_bind($queue_name, $exchange, $routing_key);

    echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";

    $res = array();
    $callback = function ($msg) {
        #获取推送的信息
        echo ' [x] ', $msg->body, "\n";
        $result = $msg->body;

        //file_put_contents('aa.log',$res,FILE_APPEND);
        //信息标签
        echo $msg->delivery_info['delivery_tag'] . "\n";
        echo '接收时间：' . date("Y-m-d H:i:s", time()) . PHP_EOL;
        echo '接收内容：' . $result . PHP_EOL;

        //消息应答机制
        //$result = mt_rand(0, 1); #模拟信息处理逻辑， 逻辑成功-true  逻辑处理失败-false
        if (!empty($result)) {
            #表示逻辑处理完成，并处理成功，使用ack机制应答信息（ack应答成功消息将从队列消失）
            echo 'success' . PHP_EOL;
            $msg->ack();
        } else {
            #表示逻辑处理失败，需要把信息重回队列，重新消费，使用nack机制
            echo 'fail' . PHP_EOL;
            echo "将消息打回,重回队列：";
            $msg->nack(true);
        }

        // $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']); //手动发送ACK应答
        //exit();
    };

//在默认情况下，消息确认机制是关闭的。现在是时候开启消息确认机制，将basic_consumer的第四个参数设置为false(true表示不开启消息确认)，并且工作进程处理完消息后发送确认消息。
    $channel->basic_consume($queue_name, '', false, false, false, false, $callback);

    while (count($channel->callbacks)) {
        $channel->wait();
    }
    $channel->close();
    $connection->close();
    return $res;
}catch (Exception $exception){
    print_r($exception->getMessage());
}
