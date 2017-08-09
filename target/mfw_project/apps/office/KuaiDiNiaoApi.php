<?php
namespace apps\office;

//define('EBusinessID', '1289960');
//define('AppKey', '262bd2f4-3e7b-4458-a0e4-013a5cf8a33d');
define('EBusinessID', '1298240');
define('AppKey', '35677321-54eb-421a-94ab-d8def335f3be');
define('ReqURL', 'https://api.kdniao.cc/api/Eorderservice');
defined('ReqURL') or define('ReqURL', 'https://testapi.kdniao.cc:8081/api/Eorderservice');


class MKuaiDiNiaoApi extends \Ko_Busi_Api
{
	private $_electricBillParams = array();

	CONST COMPANY_YUANTONG = 'YTO';
	CONST COMPANY_SHUNFENG = 'SF';

	CONST PAYTYPE_NOW    = 1;//现付
	CONST PAYTYPE_ARRIVE = 2;//到付
	CONST PAYTYPE_MONTH  = 3;//月结
	CONST PAYTYPE_THIRD  = 4;//第三方支付

	public function aGetElectricBill()
	{
		return $this->_submitEOrder();	
	}

	public function setReceiverName($name)
	{
		$this->_electricBillParams['Receiver']['Name'] = $name;
		return $this;
	}

	public function setReceiverMobile($mobile)
	{
		$this->_electricBillParams['Receiver']['Mobile'] = $mobile;
		return $this;
	}

	public function setReceiverProvinceName($province)
	{
		$this->_electricBillParams['Receiver']['ProvinceName'] = $province;
		return $this;
	}

	public function setReceiverCityName($city)
	{
		$this->_electricBillParams['Receiver']['CityName'] = $city;
		return $this;
	}

	public function setReceiverExpAreaName($exp)
	{
		$this->_electricBillParams['Receiver']['ExpAreaName'] = $exp;
		return $this;
	}

	public function setReceiverAddress($address)
	{
		$this->_electricBillParams['Receiver']['Address'] = $address;
		return $this;
	}

	public function setSenderName($name)
	{
		$this->_electricBillParams['Sender']['Name'] = $name;
		return $this;
	}

	public function setSenderMobile($mobile)
	{
		$this->_electricBillParams['Sender']['Mobile'] = $mobile;
		return $this;
	}

	public function setSenderProvinceName($province)
	{
		$this->_electricBillParams['Sender']['ProvinceName'] = $province;
		return $this;
	}

	public function setSenderCityName($city)
	{
		$this->_electricBillParams['Sender']['CityName'] = $city;
		return $this;
	}

	public function setSenderExpAreaName($exp)
	{
		$this->_electricBillParams['Sender']['ExpAreaName'] = $exp;
		return $this;
	}

	public function setSenderAddress($address)
	{
		$this->_electricBillParams['Sender']['Address'] = $address;
		return $this;
	}

	public function setBillProperty($deliveryCompany, $orderNum, $payType)
	{
		$this->_electricBillParams['Commodity'] = array(array('GoodsName' => '其他'));
		$this->_electricBillParams['IsReturnPrintTemplate'] = 1;//输出电子面单的html
		$this->_electricBillParams["ExpType"] = 1;				//快递类型 1-标准类型

		if($deliveryCompany == self::COMPANY_SHUNFENG)
		{
			$this->_electricBillParams["TemplateSize"] = 210;		//模板编号
			$this->_electricBillParams["MonthCode"] = '0107292049';		//月结账号
		}

		$this->_electricBillParams["ShipperCode"] = $deliveryCompany;		//快递公司
		$this->_electricBillParams["OrderCode"] = $orderNum;		//订单号，自有
		$this->_electricBillParams["PayType"] = $payType;			//付款类型
		return $this;
	}

	public function setYuantongNamePwd($customerName, $custormerPwd)
	{
		$this->_electricBillParams["CustomerName"] = $customerName;		
		$this->_electricBillParams["CustomerPwd"] = $custormerPwd;		
		$this->_electricBillParams["MonthCode"] = $custormerPwd;		
		return $this;
	}

	/**
	 * Json方式 调用电子面单接口
	 */
	private function _submitEOrder(){
		$requestData = json_encode($this->_electricBillParams);
		file_put_contents('/tmp/office.log', PHP_EOL.'申请订单，请求第三方  订单参数: '. $requestData, FILE_APPEND);
		$datas = array(
			'EBusinessID' => EBusinessID,
			'RequestType' => '1007',
			'RequestData' => urlencode($requestData) ,
			'DataType' => '2',
		);
		$datas['DataSign'] = $this->_encrypt($requestData, AppKey);
		$response = $this->_sendPost(ReqURL, $datas);

		file_put_contents('/tmp/office.log', PHP_EOL.'申请订单，请求第三方 返回数据 '. var_export($response, true), FILE_APPEND);
		$result = json_decode($response, true);

		return $result;
	}


	/**
	 * 电商Sign签名生成
	 * @param data 内容
	 * @param appkey Appkey
	 * @return DataSign签名
	 */
	private function _encrypt($data, $appkey) {
		return urlencode(base64_encode(md5($data.$appkey)));
	}

	/**
	 *  post提交数据
	 * @param  string $url 请求Url
	 * @param  array $datas 提交的数据
	 * @return url响应返回的html
	 */
	private function _sendPost($url, $datas){ 
		$temps = array();
		foreach ($datas as $key => $value) {
			$temps[] = sprintf('%s=%s', $key, $value);
		}
		$post_data = implode('&', $temps);
		$url_info = parse_url($url);
		if(empty($url_info['port']))
		{
			$url_info['port']=80;
		}
		$httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
		$httpheader.= "Host:" . $url_info['host'] . "\r\n";
		$httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
		$httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
		$httpheader.= "Connection:close\r\n\r\n";
		$httpheader.= $post_data;
		$fd = fsockopen($url_info['host'], $url_info['port']);
		fwrite($fd, $httpheader);
		$gets = "";
		$headerFlag = true;
		while (!feof($fd)) {
			if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
				break;
			}
		}
		while (!feof($fd)) {
			$gets.= fread($fd, 128);
		}
		fclose($fd);

		return $gets;
	}

	private function _curl($url,  $info){

		$header = 'Content-Type:application/x-www-form-urlencoded';
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $info);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //这个是重点,规避ssl的证书检查。
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 跳过host验证

		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode($response,true);
	}
}
