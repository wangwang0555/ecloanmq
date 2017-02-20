<?php
/**
 * 贷款申请dao
 * @author chenwang
 */
class applyloanDao {
	private $conn;
	public function __construct() {
		if (! ($this->conn)) {
			$this->conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
			if ($this->conn) {
				mysql_select_db ( "ecloanodps" );
			}
			// $this->conn = mysql_connect ( 'localhost', 'root', '123456' );
			// if ($this->conn) {
			// mysql_select_db ( "ecloan" );
			// }
		}
	}
	/**
	 * 查询贷款申请
	 * @param unknown $data        	
	 */
	public function findAllLoan($data) {
		$is_update = 0;
		if (! empty ( $data )) {
			if (! ($this->conn)) {
				$this->conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
				if ($this->conn) {
					mysql_select_db ( "ecloan" );
				}
				echo "连接为空，重新连接\n";
			} else {
				echo "已记连接\n";
			}
			// 查询订单
			$sql0 = "select modify_date  from ec_ecorder_orders where sales_order='$data->tid'";
			$res = mysql_query ( $sql0, $this->conn );
			$resinfo = mysql_fetch_row ( $res );
			if (empty ( $resinfo )) {
				// 添加订单
				$sql = "insert into ec_ecorder_orders (sales_order,ec_ecorder_orders.`status`,seller_nick,buyer_nick,start_date,pay_date,shipping_date,end_date,modify_date,shipping_type,title,payment,post_fee,platform,remarks,is_consignment)VALUE('$data->tid','$data->status','$data->seller_nick','$data->buyer_nick','$data->created','$data->pay_time','$data->consign_time','$data->end_time','$data->modified','$data->shipping_type','$data->title','$data->payment','$data->post_fee','$data->trade_from','$data->buyer_message','$data->type')";
				$is_update = 1;
				// echo $sql;
			} else { // 修改订单
				if (strtotime ( $resinfo [0] ) <= strtotime ( $data->modified )) {
					$sql = "update ec_ecorder_orders set ec_ecorder_orders.`status`='$data->status',start_date='$data->created',pay_date='$data->pay_time',shipping_date='$data->consign_time',end_date='$data->end_time',modify_date='$data->modified',shipping_type='$data->shipping_type',title='$data->title',payment='$data->payment',post_fee='$data->post_fee',platform='$data->trade_from',remarks='$data->buyer_message',is_consignment='$data->type' where sales_order='$data->tid'";
					$is_update = 2;
					// echo $sql;
				}
			}
			mysql_query ( "set names utf8" );
			$res2 = mysql_query ( $sql, $this->conn );
			$res_msg = "";
			if ($is_update == 1)
				$res_msg = "添加";
			else
				$res_msg = "修改";
			if ($res2) {
				echo "主订单（" . $data->tid . "）：" . $res_msg . "成功\n";
			} else {
				echo "主订单（" . $data->tid . "）：" . $res_msg . "失败" . mysql_error () . "\n";
			}
		}
		return $is_update;
	}
	/**
	 * 添加子订单
	 */
	public function childorderAdd($data, $is_update) {
		mysql_query ( "set names utf8" );
		if (! empty ( $data )) {
			if (! ($this->conn)) {
				$this->conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
				if ($this->conn) {
					mysql_select_db ( "ecloan" );
				}
			}
			if ($is_update == 1) {
				// 添加订单
				$sql = "insert into ec_ecorder_childorders (order_id,sales_order,ec_ecorder_childorders.`status`,refund_status,goods_name,price,quantity,payment,sku_name,meal_name,goods_id,logistics_company,shipping_type)VALUE('$data->oid','$data->tid','$data->status','$data->refund_status','$data->title','$data->price','$data->num','$data->payment','$data->sku_properties_name','$data->item_meal_id','$data->num_iid','$data->logistics_company','$data->shipping_type' )";
				$res2 = mysql_query ( $sql, $this->conn );
				if ($res2) {
					echo "子订单（" . $data->oid . "）：添加成功\n";
				} else {
					echo "子订单（" . $data->oid . "）：添加失败" . mysql_error () . "\n";
				}
				// echo $sql;
			} else if ($is_update == 2) { // 修改订单
				$sql = "update ec_ecorder_childorders set ec_ecorder_childorders.`status`='$data->status',refund_status='$data->refund_status',goods_id='$data->num_iid',price='$data->price',quantity='$data->num',payment='$data->payment',logistics_company='$data->logistics_company',shipping_type='$data->shipping_type',sku_name='$data->sku_properties_name',meal_name='$data->item_meal_id'  where order_id='$data->oid'";
				$res2 = mysql_query ( $sql, $this->conn );
				if ($res2) {
					echo "子订单（" . $data->oid . "）：修改成功\n";
				} else {
					echo "子订单（" . $data->oid . "）：修改失败" . mysql_error () . "\n";
				}
				// echo $sql;
			}
		}
	}
	
	/**
	 * 添加主订单按月分表
	 *
	 * @param unknown $data        	
	 */
	public function orderMothAdd($data) {
		$is_update = array (
				"is_update" => 0 
		);
		
		if (! empty ( $data )) {
			if (!($this->conn)){
				$this->conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
				if ($this->conn) {
					mysql_select_db ( "ecloanodps" );
				}
				echo "连接为空，重新连接\n";
			} else {
				echo "已记连接\n";
			}
			$tableName = "";
			$tableName2 = "";
			// 将开始时间转换成时间戳
			$state_time = strtotime ( $data->created );
			if ($state_time >= strtotime ( "2015-06-01 00:00:00" ) && $state_time <= strtotime ( "2015-06-15 23:59:59" )) { // 201506订单
				$tableName = "ec_ecorder_orders2015060";
				$tableName2 = "ec_ecorder_childorders2015060";
			} else if ($state_time >= strtotime ( "2015-06-16 00:00:00" ) && $state_time <= strtotime ( "2015-06-30 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015061";
				$tableName2 = "ec_ecorder_childorders2015061";
			} else if ($state_time >= strtotime ( "2015-07-01 00:00:00" ) && $state_time <= strtotime ( "2015-07-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015070";
				$tableName2 = "ec_ecorder_childorders2015070";
			} else if ($state_time >= strtotime ( "2015-07-16 00:00:00" ) && $state_time <= strtotime ( "2015-07-31 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015071";
				$tableName2 = "ec_ecorder_childorders2015071";
			} else if ($state_time >= strtotime ( "2015-08-01 00:00:00" ) && $state_time <= strtotime ( "2015-08-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015080";
				$tableName2 = "ec_ecorder_childorders2015080";
			} else if ($state_time >= strtotime ( "2015-08-16 00:00:00" ) && $state_time <= strtotime ( "2015-08-31 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015081";
				$tableName2 = "ec_ecorder_childorders2015081";
			} else if ($state_time >= strtotime ( "2015-09-01 00:00:00" ) && $state_time <= strtotime ( "2015-09-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015090";
				$tableName2 = "ec_ecorder_childorders2015090";
			} else if ($state_time >= strtotime ( "2015-09-16 00:00:00" ) && $state_time <= strtotime ( "2015-09-30 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015091";
				$tableName2 = "ec_ecorder_childorders2015091";
			} else if ($state_time >= strtotime ( "2015-10-01 00:00:00" ) && $state_time <= strtotime ( "2015-10-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015100";
				$tableName2 = "ec_ecorder_childorders2015100";
			} else if ($state_time >= strtotime ( "2015-10-16 00:00:00" ) && $state_time <= strtotime ( "2015-10-31 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015101";
				$tableName2 = "ec_ecorder_childorders2015101";
			} else if ($state_time >= strtotime ( "2015-11-01 00:00:00" ) && $state_time <= strtotime ( "2015-11-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015110";
				$tableName2 = "ec_ecorder_childorders2015110";
			} else if ($state_time >= strtotime ( "2015-11-16 00:00:00" ) && $state_time <= strtotime ( "2015-11-30 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015111";
				$tableName2 = "ec_ecorder_childorders2015111";
			} else if ($state_time >= strtotime ( "2015-12-01 00:00:00" ) && $state_time <= strtotime ( "2015-12-15 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015120";
				$tableName2 = "ec_ecorder_childorders2015120";
			} else if ($state_time >= strtotime ( "2015-12-16 00:00:00" ) && $state_time <= strtotime ( "2015-12-31 23:59:59" )) {
				$tableName = "ec_ecorder_orders2015121";
				$tableName2 = "ec_ecorder_childorders2015121";
			}
			$is_update ["tablename"] = $tableName2;
			// 查询订单
			$sql0 = "select modify_date  from " .$tableName . " where sales_order='$data->tid'";
			$res = mysql_query ( $sql0, $this->conn );
			$resinfo = mysql_fetch_row ( $res );
			$sql="";
			//转义
			$title = mysql_escape_string($data->title );
			$buyer_message = mysql_escape_string($data->buyer_message );
			$buyer_nick=mysql_escape_string($data->buyer_nick);
			$seller_nick=mysql_escape_string($data->seller_nick);
			if (empty ( $resinfo )) {
				// 添加订单
				$sql = "insert into " . $tableName . " (sales_order,`status`,seller_nick,buyer_nick,start_date,pay_date,shipping_date,end_date,modify_date,shipping_type,title,payment,post_fee,platform,remarks,is_consignment)VALUE('$data->tid','$data->status','$seller_nick','$buyer_nick','$data->created','$data->pay_time','$data->consign_time','$data->end_time','$data->modified','$data->shipping_type','$title','$data->payment','$data->post_fee','$data->trade_from','$buyer_message','$data->type')";
				$is_update ["is_update"] = 1;
			} else {// 修改订单
				if (strtotime ( $resinfo [0] ) <= strtotime ( $data->modified )) {
					$sql = "update " . $tableName . " set `status`='$data->status',start_date='$data->created',pay_date='$data->pay_time',shipping_date='$data->consign_time',end_date='$data->end_time',modify_date='$data->modified',shipping_type='$data->shipping_type',title='$title',payment='$data->payment',post_fee='$data->post_fee',platform='$data->trade_from',remarks='$buyer_message',is_consignment='$data->type' where sales_order='$data->tid'";
					$is_update ["is_update"] = 2;
				}
			}
			mysql_query ( "set names utf8" );
			$res2 = mysql_query ( $sql, $this->conn );
			$res_msg = "";
			if ($is_update ["is_update"] == 1)
				$res_msg = "添加";
			else
				$res_msg = "修改";
			if ($res2) {
				echo $data->created."主订单（" . $data->tid . "）：" . $res_msg . "成功\n";
			} else {
				//失败，添加日志
				$msglog=fopen("data/log/errorlog.txt", "a+");
				fwrite($msglog, date("Y-m-d H:i:s")."主订单（" . $data->tid . "）：" . $res_msg . "失败sql：".$sql."系统报错". mysql_error () . "\n");
				echo "主订单（" . $data->tid . "）：" . $res_msg . "失败sql：".$sql."系统报错". mysql_error () . "\n";
			}
		}
		
		return $is_update;
	}
	/**
	 * 添加子订单按月添加
	 */
	public function childorderMothAdd($data, $is_update) {
		mysql_query("set names utf8");
		if (! empty ( $data )) {
			if (! ($this->conn)) {
				$this->conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
				if ($this->conn) {
					mysql_select_db ( "ecloanodps" );
				}
			}
			$sku_properties_name = mysql_escape_string($data->sku_properties_name);
			$title = mysql_escape_string($data->title);
			if ($is_update ["is_update"] == 1) {
				// 添加订单
				$sql = "insert into " . $is_update ["tablename"] . " (order_id,sales_order,`status`,refund_status,goods_name,price,quantity,payment,sku_name,meal_name,goods_id,logistics_company,shipping_type)VALUE('$data->oid','$data->tid','$data->status','$data->refund_status','$title','$data->price','$data->num','$data->payment','$sku_properties_name','$data->item_meal_id','$data->num_iid','$data->logistics_company','$data->shipping_type' )";
				$res2 = mysql_query ( $sql, $this->conn );
				if ($res2) {
					echo "子订单（" . $data->oid . "）：添加成功\n";
				} else {
					$msglog=fopen("data/log/errorlog.txt", "a+");
					fwrite($msglog, date("Y-m-d H:i:s")."子订单（" . $data->oid . "）：添加失败" ."sql".$sql."系统报错". mysql_error () . "\n");
					echo "子订单（" . $data->oid . "）：修改失败" ."sql".$sql."系统报错". mysql_error () . "\n";
				}
				// echo $sql;
			} else if ($is_update ["is_update"] == 2) { // 修改订单
				$sql = "update " . $is_update ["tablename"] . " set `status`='$data->status',refund_status='$data->refund_status',goods_id='$data->num_iid',price='$data->price',quantity='$data->num',payment='$data->payment',logistics_company='$data->logistics_company',shipping_type='$data->shipping_type',sku_name='$sku_properties_name',meal_name='$data->item_meal_id'  where order_id='$data->oid'";
				$res2 = mysql_query ( $sql, $this->conn );
				if ($res2) {
					echo "子订单（" . $data->oid . "）：修改成功\n";
				} else {
					//失败，添加日志
					$msglog=fopen("data/log/errorlog.txt", "a+");
					fwrite($msglog, date("Y-m-d H:i:s")."子订单（" . $data->oid . "）：修改失败sql".$sql."系统报错". mysql_error () . "\n");
					echo "子订单（" . $data->oid . "）：修改失败sql".$sql."系统报错". mysql_error () . "\n";
					
				}
				// echo $sql;
			}
		}
	}
	/**
	 * 查询主订单
	 */
	public function findAll($page) {
		mysql_query ( "set names utf8" );
		$page = empty ( $page ) ? 1 : $page;
		$sql = "select * from ec_ecorder_orders LIMIT 0,10";
		$res = mysql_query ( $sql, $this->conn );
		$orders = "";
		$fp = fopen ( "data/order1.txt", "w" );
		while ( $val = mysql_fetch_array ( $res ) ) {
			$orders = $val [0] . "," . $val [1] . "," . $val [2] . "," . $val [3] . "," . $val [4] . "," . $val [5] . "," . $val [6] . "," . $val [7] . "," . $val [8] . "," . $val [9] . "," . $val [10] . "," . $val [11] . "," . $val [12] . "," . $val [13] . "," . $val [14] . "," . $val [15] . "\r\n";
			fwrite ( $fp, $orders );
		}
		$rr = system ( "D:\odps\odps-dship-0.15.1\dship upload test.txt test1" );
		print_r ( $rr );
	}
}