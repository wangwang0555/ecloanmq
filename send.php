<?php
/**
  	 * PHP amqp(RabbitMQ) 发送数据
  	 * @author  chenwang
 	 */
// 配置信息
$conn_args = array (
		'host' => '10.0.0.0',//10.139.96.244,120.55.176.81
		'port' => '7672',
		'vhost' => 'test',
		'login' => 'test',
		'password' => 'test' 
);
$e_name = 'test'; // 交换机名
$q_name = 'test'; // 队列名
$k_route = array(0=> 'test'); //路由key
//创建连接和channel
$conn = new AMQPConnection($conn_args);
if (!$conn->connect()){
die("Cannot connect to the broker!\n");
}
$channel = new AMQPChannel($conn);
//创建交换机
$ex = new AMQPExchange($channel);
$ex->setName($e_name);
$ex->setType(AMQP_EX_TYPE_DIRECT); //direct类型
$ex->setFlags(AMQP_DURABLE); //持久化
echo "Exchange Status:".$ex->declare()."\n";
echo "Send Message:".$ex->publish("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", "")."\n";
