<?php
namespace apps\office;

class MDelivery_PackageApi extends \Ko_Busi_Api
{
	public function aGetByDeliveryId($deliveryId)
	{
	    $option = new \Ko_Tool_SQL();
        $option->oAnd('delivery_id=?', $deliveryId);

        $result = $this->packageDao->aGetList($option);
		return $result;
	}

	public function updateShortBillTag($deliveryCompany, $year, $month)
	{
		$now = time();
		$monthStart = mktime(0,0,0,$month-1,1,$year);
		$monthStartStr = date('Y-m-d H:i:s', $monthStart);
		$monthEnd = mktime(0,0,0,$month,0,$year);
		$monthEndStr = date('Y-m-d H:i:s', $monthEnd);

		$deliveryCompany = $deliveryCompany == '顺丰' ? 1 : 2;
	    $option = new \Ko_Tool_SQL();
        $option->oAnd("ctime >= ?", $monthStartStr);
        $option->oAnd("ctime < ?", $monthEndStr);
        $option->oAnd('tag = ?', 1);
        $option->oAnd('delivery_company = ?', $deliveryCompany);

		$aUpdate = array('tag' => 4);// 账单缺失

        return $this->packageDao->iUpdateByCond($option, $aUpdate);
	}

	public function passAbNormalOrder($deliveryId, $deliveryCompany)
	{
		$deliveryCompany = $deliveryCompany == '顺丰' ? 1 : 2;
	    $option = new \Ko_Tool_SQL();
        $option->oAnd("delivery_id = ?", $deliveryId);
        $option->oAnd('delivery_company = ?', $deliveryCompany);

		$aUpdate = array('tag' => 5);// 强制通过

        return $this->packageDao->iUpdateByCond($option, $aUpdate);
	}

	public function aGetDiffRecord($year, $month, $deliveryCompany, $tagList)
	{
		$deliveryCompany = $deliveryCompany == '顺丰' ? 1 : 2;
		$monthStart = mktime(0,0,0,$month,1,$year);
		$monthStartStr = date('Y-m-d H:i:s', $monthStart);
		$monthEnd = mktime(0,0,0,$month+1,1,$year);
		$monthEndStr = date('Y-m-d H:i:s', $monthEnd);

	    $option = new \Ko_Tool_SQL();
		$option->oWhere('tag in (?)', $tagList);
	    $option->oAnd("ctime >= ?", $monthStartStr);
        $option->oAnd("ctime < ?", $monthEndStr);
        $option->oAnd('delivery_company = ?', $deliveryCompany);

        return $this->packageDao->aGetList($option);
	}
}
