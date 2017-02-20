<?php
/**
 * PHP amqp(RabbitMQ) 接收数据
 * 
 * @author chenwang
 */
set_time_limit ( 0 );
// 配置信息
$conn_args = array (
		'host' => '10.01.01.01',
		'port' => '7672',
		'vhost' => 'test',
		'login' => 'test',
		'password' => 'test' 
);
$e_name = 'test'; // 交换机名
$q_name = 'test'; // 队列名
$k_route = 'test'; // 路由key
                     
// 创建连接和channel
$conn = new AMQPConnection ( $conn_args );
if (! $conn->connect ()) {
	die ( "Cannot connect to the broker!\n" );
}
$channel = new AMQPChannel ( $conn );

// 创建交换机
$ex = new AMQPExchange ( $channel );
$ex->setName ( $e_name );
$ex->setType ( AMQP_EX_TYPE_DIRECT ); // direct类型
$ex->setFlags ( AMQP_DURABLE ); // 持久化
echo "Exchange Status:" . $ex->declare () . "\n";

// 创建队列
$q = new AMQPQueue ( $channel );
$q->setName ( $q_name );
$q->setFlags ( AMQP_DURABLE ); // 持久化
                               
// 绑定交换机与队列，并指定路由键
echo 'Queue Bind: ' . $q->bind ( $e_name, $k_route ) . "\n";

// 阻塞模式接收消息
echo "Message:\n";
while ( true ) {
	$q->consume ( 'processMessage', AMQP_AUTOACK ); // 自动ACK应答
}
$conn->disconnect ();

/**
 * 消费回调函数
 * 处理消息
 */
function processMessage($envelope, $queue) { // 处理订单
	$msg = $envelope->getBody ();
	// 将压缩数据解压缩
	if (! empty ( $msg )) {
		// 日志记录,十天一个文件记录
		$staTime = microtime ( true );
		$array = json_decode ( denData ( $msg ) );
		if (! empty ( $array )) { // 数据解析正常
			//业务逻辑代码-根据自身项目需求修改start
			$orderDao = new orderDao ();
			foreach ( $array->trades as $val ) { // 主订单
				// 添加子订单信息
				$childval = $val->orders;
				$check = $orderDao->orderMothAdd ( $val );
				foreach ( $childval as $child ) {
					$child->tid = $val->tid;
						$orderDao->childorderMothAdd ($child, $check );
					}
			}
			//end
		}
		// 程序执行结束时间
		$xcTime = microtime ( true ) - $staTime;
		echo "程序执行时间：" . floor ( $xcTime ) . "";
	}
}
/**
 * 处理压缩数据
 */
function denData($data) {
	if (! empty ( $data )) {
		return gzinflate ( substr ( base64_decode ( $data ), 10, - 8 ) );
	}
}