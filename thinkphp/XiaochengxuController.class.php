<?php

/*
* author 林晓鸿
* 返回标准
*status = 1:成功 ，2：失败
*data = 数组形式 array("000"=>111);
*/
namespace Api\Controller;

use Common\Controller\AppframeController;
 use Portal\Service\ApiService;
 
class XiaochengxuController extends AppframeController{
	 
	 
	
	public function _initialize() {
		parent::_initialize();
	}
	  
  /*
    *微信支付 接口
    */
  public function pay_wechat_api(){
    $data = I("post.");
 	$params = array(
            'appid'      => C('THINK_SDK_WEIXIN.APP_KEY'),
            'secret'     => C('THINK_SDK_WEIXIN.APP_SECRET'),
       		'mch_id'      => C('THINK_SDK_WEIXIN.zhifu_weixin_mch_id'),
            'key'     => C('THINK_SDK_WEIXIN.zhifu_weixin_key'),
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
			 );
     Vendor("php.WeixinPay");
	 
$appid=$params['appid']; 
$openid=$data['openid'];
$mch_id=$params['mch_id']; 
$key=$params['key']; 
     
     $order_model = D("order");
     $info = $order_model->where(array("id"=>$data['order_id']))->find();
    if($info){
      $out_trade_no = $info['out_trade_no']; //订单号 
      $total_fee = $info['total_fee'];   //金额
      $body = '消费';//$info['appointment_date'];  
      $notify_url = 'http://xxxxxxx.com/index.php/Api/xiaochengxu/notify_url_api'; //异步通知
      $order_ip  = get_client_ip(0,true); //IP地址
      $weixinpay = new \WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$notify_url,$order_ip); 
      $return=$weixinpay->pay(); 

          //echo json_encode($return); 
               $dataaa['status']=1;
                $dataaa['data'] = $return;
          $dataaa['dataa'] = array("out_trade_no"=>$out_trade_no,"total_fee"=>$total_fee,"notify_url"=>$notify_url,"order"=>$order_ip);
                $this->ajaxReturn($dataaa); 
    }else{
    			 $dataaa['status']=2;
                $dataaa['data'] = '订单错误,请重新操作';
                $this->ajaxReturn($dataaa); 
    }
 
     
  }
  
 /*微信支付的 异步通知*/
  
  public function notify_url_api(){
      $xml=file_get_contents('php://input', 'r');
    //转成php数组 禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $data= json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
    file_put_contents('./notify.text', $data);
    // ↑↑↑上面的file_put_contents是用来简单查看异步发过来的数据 测试完可以删除；↑↑↑
   
    	 // 获取xml
        $xml=file_get_contents('php://input', 'r'); 
        // 转成php数组
         $attr=$this->toArray($xml);
     
    $total_fee = $attr['total_fee'];  
    $open_id = $attr['openid'];  
    $out_trade_no = $attr['out_trade_no'];  
    $time = $attr['time_end']; 
    $transaction_id = $attr['transaction_id'];
     
   //判断支付状态
        if ($attr['return_code']=='SUCCESS' && $attr['result_code']=='SUCCESS') {
            $result=$attr;
        }else{
            $result=false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $order_model = D("order");
            $where['order_status'] =0; //0待付款 1已付款 2可使用 3已完成 4需要退款(申请售后) 5已退款 
            $where['out_trade_no'] =$out_trade_no; //订单号
            $info = $order_model->where($where)->find();
              if($info){
                $save['zhifu_time'] = $time;//保存支付成功的时间
                $save['zhifu_openid'] = $open_id;//保存支付的openid
                $save['zhifu_type'] =0;//保存支付的类型
                $save['total_fee'] =$total_fee;//保存支付成功的金额
                $save['transaction_id'] =$transaction_id;//保存支付商户号对应的ID号
                $save['order_status'] =1;//保存支付状态  0待付款 1已付款  
                 
                $order_model->where($where)->save($save);
              }else{
                  echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';//支付成功过了
              }
          
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } 
        echo $str;
		
      
  } 
  //将xml格式转换成数组  
  public function xmlToArray($xml) {  

        //禁止引用外部xml实体   
        libxml_disable_entity_loader(true);  

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);  

        $val = json_decode(json_encode($xmlstring), true);  

        return $val;  
    } 
  /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml){   
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $result;
    }
   
  
public function vget($url){
    $info=curl_init();
    curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($info,CURLOPT_HEADER,0);
    curl_setopt($info,CURLOPT_NOBODY,0);
    curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($info,CURLOPT_URL,$url);
    $output= curl_exec($info);
    curl_close($info);
    return $output;
}
  
/* 
  *title：余额支付
  *data = 》array("order_id"=>产品ID,"user_id"=>"用户id")
  * 直接扣除用户金额
  */
	private function _credit($data){
  	  $user_model =D("users");
     $order_model =D("order");
      $user_id =$data['user_id'];
      $order_id =$data['order_id'];
    
       $a1=$user_model->where(array("id"=>$user_id))->find();  
      $a2=$order_model->where(array("id"=>$order_id))->find();
         //  echo 1;exit;
      if($a2){
          if($a2['order_status']==1){ 
             $dataaa['status']=2;
		 	 $dataaa['data'] = '该订单已经支付了！';
		     $this->ajaxReturn($dataaa); 
          }
      	  if($a1){
           	//产品金额的总额 直接扣掉用户的金额 并且做记录
            $sum_product_price = $a2['total_fee'];
            $user_coin = $a1['coin'];
            if($user_coin>=$sum_product_price){
              
                $status =$user_model->where(array("id"=>$user_id,"user_type"=>2))->setDec('coin',$sum_product_price);
              if($status){
            	   $data_record = array(
					'out_trade_no'=> $a2['out_trade_no'],//订单号
					'total_fee'=> $a2['total_fee'],	//金额 1 = 1分钱   100 = 1元
					'user_id'=>$user_id,
					'zhifu_type'=>2,	//类型 1：充值；2：消费  
					'balance_sheng'=>$user_coin,// 之前的余额
					'date'=>date('Y-m-d H:i:s'), 
                    'time'=>time(), 
				 ); 
				 $info = M('balance_record')->add($data_record);
                
                 $order_data = array(
					'zhifu_type'=> 1,//余额支付
                    'zhifu_time'=>time(), 
                   'order_status'=>1,//已付款
				 ); 
                $order_status = $order_model->where(array("id"=>$a2['id']))->save($order_data);
                if($order_status){
                    $dataaa['status']=1;
		 	 		$dataaa['data'] = '余额支付成功！';
		    		 $this->ajaxReturn($dataaa); 
                }else{
                     $dataaa['status']=2;
		 	 		 $dataaa['data'] = '支付失败，但您的金额已被扣除，订单的状态未被更新，请与管理员取得联系！';
		    		 $this->ajaxReturn($dataaa); 
                }
              }
            }else{
				 $dataaa['status']=2;
				$dataaa['data'] = '您余额不足！';
				$this->ajaxReturn($dataaa); 
			}
            
          }else{
             $dataaa['status']=2;
		 	 $dataaa['data'] = '用户信息不存在，请重新操作';
		     $this->ajaxReturn($dataaa); 
          }
        
      }else{
     	  $dataaa['status']=2;
		  $dataaa['data'] = '订单不存在，请重新操作';
		  $this->ajaxReturn($dataaa); 
      }
  }
   
   
  
  public function login_openid_api(){
   $data =I('post.');
 
    $params = array(
            'appid'      => C('THINK_SDK_WEIXIN.APP_KEY'),
            'secret'     => C('THINK_SDK_WEIXIN.APP_SECRET'),
       		'mch_id'      => C('THINK_SDK_WEIXIN.mch_id'),
            'secret'     => C('THINK_SDK_WEIXIN.APP_SECRET'),
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
			 );
    
  $url='https://api.weixin.qq.com/sns/jscode2session?appid='.$params['appid'].'&secret='.$params['secret'].'&js_code='. $data['code'] . '&grant_type=authorization_code';
    $info = $this->curl_request($url,'','','');
   		   $dataaa['status']=1;
		  $dataaa['data'] =$info;
		  $this->ajaxReturn($dataaa); 
  }
  
  //参数1：访问的URL，参数2：post数据(不填则为GET)，参数3：提交的$cookies,参数4：是否返回$cookies
private function curl_request($url,$post='',$cookie='', $returnCookie=0){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
        if($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        if($returnCookie){
            list($header, $body) = explode("\r\n\r\n", $data, 2);
            preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
            $info['cookie']  = substr($matches[1][0], 1);
            $info['content'] = $body;
            return $info;
        }else{
            return $data;
        }
}
  
   
  public function genTree($items,$id='term_id',$pid='parent',$son = 'children'){
		$tree = array(); //格式化的树
		$tree = array(); //格式化的树
		$tmpMap = array();  //临时扁平数据
		
		foreach ($items as $item) {
			$tmpMap[$item[$id]] = $item;
		}
		
		foreach ($items as $item) {
			if (isset($tmpMap[$item[$pid]])) {
				$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
			} else {
				$tree[] = &$tmpMap[$item[$id]];
			}
		}
		return $tree;
	} 
  
}
