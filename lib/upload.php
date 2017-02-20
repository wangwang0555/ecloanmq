<?php
/**
 * 将贷款记录及店铺信息、订单处理成txt文件
 * @author chenwang
 */
class upload {
	/**
	 * 处理数据贷款记录及订单，保存为txt文件
	 */
	public static function uploadData($apply_id, $sellerNicks) {
		if (empty ( $apply_id ) && empty ( $sellerNicks )) {
			return false;
		}
		$ddate = date ( "Ymd" );
		$fgstr = "\x1E";
		$conn = mysql_connect ( 'rdsg2l9bwdl2u7m9d9vz.mysql.rds.aliyuncs.com', 'loanadmin', 'loanadmin2015' );
		// $conn = mysql_connect ( 'localhost', 'root', '' );
		if ($conn) {
			mysql_select_db ( "ecloanodps" );
		}
		mysql_query ( "set names utf8" );
		// 拉取当天的订单数据，从贷款申请处理队列中获取还未传到总行的贷款申请记录
		
		// 主订单
		$fp = fopen ( "data/order/ORDERS_" . $ddate . ".txt", "w" );
		$fp2 = fopen ( "data/order/CHILDORDERS_" . $ddate . ".txt", "w" );
		
		// 业务逻辑分表
		$tablelist = array (
				"2015061" => "2015070",
				"2015070" => "2015071",
				"2015071" => "2015080",
				"2015080" => "2015081",
				"2015081" => "2015090",
				"2015090" => "2015091",
				"2015091" => "2015100",
				"2015100" => "2015101",
				"2015101" => "2015101",
				"2015101" => "2015110",
				"2015110" => "2015111",
				"2015111" => "2015120",
				"2015120" => "2015121" 
		);
		foreach ( $sellerNicks as $sellernick ) {
			$current_yearmonth = "2015070";
			$current_endtime = "2015-07-01 00:00:00"; // 初始化
			// 循环所有分表查询订单
			foreach ( $tablelist as $tablename ) {
				$current_yearmonth=$tablename;
				echo $current_yearmonth;
				$current_endtime = "2015-07-01 00:00:00";
				$sqljl = "select current_total,current_yearmonth,current_endtime from ec_ecorder_pinganjl where seller_nick='" . $sellernick ["seller_nick"] . "' and current_yearmonth='$tablename' ORDER BY createtime desc  limit 0,1 ";
				$resjl = mysql_query ( $sqljl ); // 查询上传的记录
				$current_total = 0;
				if (! empty ( $resjl )){
					$valjl = mysql_fetch_row ( $resjl );
					if (! empty ( $valjl )) {
						$current_total = $valjl[0];
						//$current_yearmonth = $valjl[1];
						$current_endtime = $valjl[2];
					}
				}
				// 查询订单数量
				$sqlcount = "select count(*) as order_num from ec_ecorder_orders" . $current_yearmonth . " where seller_nick='" . $sellernick ["seller_nick"] . "' and modify_date>'$current_endtime'";
				$rescount = mysql_query ( $sqlcount, $conn );
				$ordercount = mysql_fetch_row ( $rescount );
				if ($ordercount[0]>0) { // 订单不为空
					echo $sellernick ["seller_nick"] . "," . $tablename . "月,查询订单数量" . $ordercount [0] . "\n";
					$orderpage = ceil ( $ordercount [0] / 1000 );
				} else { // 订单为空
					echo $sellernick ["seller_nick"] . "，月份" . $current_yearmonth . "订单已为空！\n";
					$orderpage=0;
// 					if ($current_yearmonth == "2015121")break;
					continue;
				}
				$ctotal = 0;
				$cendtime = "";
				for($i = 0; $i < $orderpage; $i ++) {
					$dsize = $i * 1000;
					$sql = "select * from ec_ecorder_orders" . $current_yearmonth . " where seller_nick='" . $sellernick ["seller_nick"] . "' and modify_date>'$current_endtime' ORDER BY modify_date   LIMIT " . $dsize . ",1000";
					$res = mysql_query ( $sql, $conn );
					if (! empty ( $res )) {
						$orders = "";
						while ( $val = mysql_fetch_array ( $res ) ) {
							$buyer_nick = "";
							$orders = $val ["sales_order"] . $fgstr . $val ["status"] . $fgstr . $val ["seller_nick"] . $fgstr . $buyer_nick . $fgstr . $val ["start_date"] . $fgstr . $val ["end_date"] . $fgstr . $val ["modify_date"] . $fgstr . $val ["pay_date"] . $fgstr . $val ["shipping_date"] . $fgstr . $val ["shipping_type"] . $fgstr . $val ["title"] . $fgstr . number_format ( $val ["payment"], 2 ) . $fgstr . number_format ( $val ["post_fee"], 2 ) . $fgstr . $sellernick ["platform"] . "\r\n";
							fwrite ( $fp, $orders );
							echo $sellernick ["seller_nick"] . "，月份" . $current_yearmonth . "主订单添加\n";
							// 子订单
							$sql2 = "select * from ec_ecorder_childorders" . $current_yearmonth . " where sales_order='" . $val ["sales_order"] . "'";
							$res2 = mysql_query ( $sql2, $conn );
							if (! empty ( $res2 )) {
								while ( $val2 = mysql_fetch_array ( $res2 ) ) {
									$childorder = $val2 ["order_id"] . $fgstr . $val2 ["sales_order"] . $fgstr . $val2 ["status"] . $fgstr . $val2 ["refund_status"] . $fgstr . $val2 ["goods_name"] . $fgstr . number_format ( $val2 ["price"], 2 ) . $fgstr . $val2 ["quantity"] . $fgstr . number_format ( $val2 ["payment"], 2 ) . $fgstr . $val2 ["sku_name"] . $fgstr . $val2 ["meal_name"] . $fgstr . $val2 ["goods_id"] . $fgstr . $val ["is_consignment"] . $fgstr . $val ["remarks"] . "\r\n";
									fwrite ( $fp2, $childorder );
									echo $sellernick ["seller_nick"] . "，月份" . $current_yearmonth . "子订单添加\n";
								}
							}
							$cendtime = $val ["modify_date"];
							++ $ctotal;
						}
						// 线程休眠1s
						echo "线程休眠1S\n";
						sleep ( 1 );
					}
				}
				echo $current_yearmonth . "月订单上传完\n";
				// 分表数据上传 完，记录表中
				$addsqljl = "insert into ec_ecorder_pinganjl (current_total,current_page,current_yearmonth,current_endtime,seller_nick,createtime)VALUE('$ctotal','$orderpage','$current_yearmonth','$cendtime','" . $sellernick ["seller_nick"] . "','" . time () . "')";
				mysql_query ( "set names utf8" );
				mysql_query ( $addsqljl, $conn );
				
			}
		}
	}
}