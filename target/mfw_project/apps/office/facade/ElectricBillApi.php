<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2016/12/19
 * Time: 下午4:03
 */
namespace apps\office;

class MFacade_ElectricBillApi extends \Ko_Busi_Api
{
	private $_kuaidiniaoApi = null;
	private $_yuantongCustomerName = 'K100523850';
	private $_yuantongCustomerPwd = 'UKUSwIMQ';

	public function __construct()
	{
		$this->_kuaidiniaoApi = new MKuaiDiNiaoApi();
	}

    public function aGetElectricBill($userInfo, $receiver, $params)
    {
		$this->_setKuaiDiNiaoDeliverySender($userInfo);
		$this->_setKuaiDiNiaoDeliveryReceiver($receiver);
		$this->_setKuaiDiNiaoDeliveryParams($params);

		$aData = $this->_kuaidiniaoApi->aGetElectricBill();
        return $aData;
    }

	private function _setKuaiDiNiaoDeliverySender($userInfo)
	{
		$province = '北京市';
		$city = '北京市';
		$exp = '朝阳区';
		$address = '酒仙桥北路恒通国际创新园C9蚂蜂窝';

		$this->_kuaidiniaoApi->setSenderName($userInfo['name'])
							 ->setSenderMobile($userInfo['mobile']) 
							 ->setSenderProvinceName($province)
							 ->setSenderCityName($city)
							 ->setSenderExpAreaName($exp)
							 ->setSenderAddress($address);
	}

	private function _setKuaiDiNiaoDeliveryReceiver($receiver)
	{
		$this->_kuaidiniaoApi->setReceiverName($receiver['receiver'])
							 ->setReceiverMobile($receiver['receiver_phone'])
							 ->setReceiverProvinceName($receiver['receiver_province'])
							 ->setReceiverCityName($receiver['receiver_city'])
							 ->setReceiverExpAreaName($receiver['receiver_exp'])
							 ->setReceiverAddress($receiver['receiver_address']);
	}

	private function _setKuaiDiNiaoDeliveryParams($params)
	{
		$this->_kuaidiniaoApi->setBillProperty($params['company'], $params['orderNum'], $params['payType']);

		if($params['company'] == MKuaiDiNiaoApi::COMPANY_YUANTONG)
			$this->_kuaidiniaoApi->setYuantongNamePwd($this->_yuantongCustomerName, $this->_yuantongCustomerPwd);
	}
}
