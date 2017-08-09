<?php
namespace apps\office;

class MDelivery_Api extends \Ko_Busi_Api
{

	/*
	 * 本接口，针对3个角色
	 * 普通员工：只能查看自己申请的快递
	 * 北门保安：可以查看所有人申请的快递
	 * 领导：可以查看本部门的申请记录
	 */
	public function aGetApplyList($query,$packagesListArea, $uid = 0, $departmentId = 0)
	{
		switch($packagesListArea)
		{
			case 'all' : $uid = 0; $departmentId = 0;break;
			case 'department' : $uid = 0;break;
			case 'self'	: break;
		};
		$applyApi = new MDelivery_ApplyApi();
		return $applyApi->aGetList($query, $uid, $departmentId);
	}

	public function bAuditApplyOrder($applyId, $auditResult, $rejectReason = '')
	{
		if(empty($applyId) || empty($auditResult))
			return false;	

		if($auditResult == 'pass')
				$tag = 2;
		else if($auditResult == 'forcepass')
				$tag = 4;
		else if($auditResult == 'reject')
				$tag = 3;

		$data = array(
			'audit_desc' => $rejectReason,
			'tag'		 => $tag,
		);
		$this->applyDao->iUpdate($applyId, $data);
		return true;
	}

	public function applyFailed($applyId)
	{
		$data = array('tag' => 5);//申请失败	
		return $this->applyDao->iUpdate($applyId, $data);
	}

	public function uploadBillExcel($file, $year, $month, $company)
	{
		require_once('/mfw_www/include/ko/vendor/phpExcel/PHPExcel.php');
		if(empty($file) || !is_file($file))
			return array('status' => 'error', 'msg' => '系统异常');

		$obj = \PHPExcel_IOFactory::load($file);
		$sheet = $obj->getSheet(0);
		//$flag = $sheet->getCell('A1')->getValue();
		
		if($company == '顺丰')
			return $this->_loadShunfengExcel($sheet, $year, $month);
		else
			return $this->_loadYuantongExcel($sheet, $year, $month);
	}

	public function aGetExcelMoreBillRecord($year, $month, $deliveryCompany)
	{
		$companyDao = $deliveryCompany == '顺丰' ? $this->excelShunfengDao : $this->excelYuantongDao;
		$monthStart = mktime(0,0,0,$month,1,$year);
		$monthStartStr = date('Y-m-d H:i:s', $monthStart);
		$monthEnd = mktime(0,0,0,$month+1,1,$year);
		$monthEndStr = date('Y-m-d H:i:s', $monthEnd);
		$nextMonthEnd = mktime(0,0,0,$month+2,1,$year);
		$nextMonthEndEnd = date('Y-m-d H:i:s', $nextMonthEnd);

	    $option = new \Ko_Tool_SQL();
		$option->oWhere('tag = (?)', 1);//多余的记录
		if( $deliveryCompany == '顺丰' )
		{
			$option->oAnd("delivery_time>= ?", $monthStartStr);
			$option->oAnd("delivery_time< ?", $monthEndStr);
		}
		else
		{
			$option->oAnd("ctime>= ?", $monthEndStr);
			$option->oAnd("ctime< ?", $nextMonthEndEnd);
		}

        return $companyDao->aGetList($option);
	}

	public function aGetPackageDiffRecord($year, $month, $deliveryCompany)
	{
		$tags = array(3,4);//diff more 
		$packageApi = new MDelivery_PackageApi();
		$recordList = $packageApi->aGetDiffRecord($year, $month, $deliveryCompany, $tags);

		if(empty($recordList))
			return array('short' => array(), 'diff' => array());

		$diffRecord = array();
		$shortRecord = array();
		$companyDao = $deliveryCompany == '顺丰' ? $this->excelShunfengDao : $this->excelYuantongDao;
		foreach($recordList as $record)
		{
			list($province, $city, $exp, $address) = $this->_extractAddress($record);

			$dbRecord = array(
					'delivery_id'		=> $record['delivery_id'],
					'sender'			=> $record['sender'],
					'receiver'			=> $record['receiver'],
					'receiver_province'	=> $province,
					'receiver_city'		=> $city,
					'receiver_exp'		=> $exp,
					'receiver_address'	=> $address,
					'pay_type'			=> $record['pay_type'] == 1 ? '到付' : '寄付',
					'pay_money'			=> $record['cost'],
					'receiver_company'	=> $record['receiver_company'],
			);

			if($record['tag'] == 3)//diff
			{
				$excelData = $companyDao->aGet($record['delivery_id']);
				$dbRecord['sender'] .= "({$excelData['sender']})";
				$dbRecord['receiver_city'] .= "({$excelData['receiver_address']})";
				$dbRecord['pay_type'] .= "({$excelData['pay_type']})";
				$dbRecord['receiver_company'] .= "({$excelData['receiver_company']})";

				$diffRecord[] = $dbRecord;
			}
			else
				$shortRecord[] = $dbRecord;
		}

		return array(
			'diff'  => $diffRecord,
			'short'	=> $shortRecord,	
		);
	}

	public function passAbNormalOrder($deliveryId, $type, $deliveryCompany)
	{
		if($type == 'more') //excel多余订单信息
		{
			if($deliveryCompany == '顺丰')
				$dao = $this->excelShunfengDao;
			else
				$dao = $this->excelYuantongDao;
			return $dao->iUpdate($deliveryId, array('tag' => 5));
		}

		$packageApi = new MDelivery_PackageApi();
		$packageApi->passAbNormalOrder($deliveryId, $deliveryCompany);
	}

	private function _loadShunfengExcel($sheet, $year, $month)
	{
		$packageApi = new MDelivery_PackageApi();
		$row = $sheet->getHighestRow();
		$date = $this->_extractShunfengExcelBillDate($sheet);
		if($date == false)
			return array('status' => 'error', 'msg' => '格式有误，无法识别日期');;

		$successNum = 0;
		$lastSuccessDeliveryId = 0;
		for($j = 13; $j <= $row; $j++) {

			$deliveryId = $sheet->getCell('C' . $j)->getValue();
			if(empty($deliveryId))
				continue;
			$packageInfo = array(
				'delivery_id'		=> $deliveryId, 
				'delivery_time'		=> $year .'-'.$sheet->getCell('B' . $j)->getValue(), 
				'sender'			=> $sheet->getCell('L' . $j)->getValue(), 
				'receiver_address'	=> $sheet->getCell('D' . $j)->getValue(), 
				'receiver_company'	=> $sheet->getCell('E' . $j)->getValue(), 
				'cost'				=> floatval($sheet->getCell('I' . $j)->getValue()), 
				'extra_cost'		=> floatval($sheet->getCell('M' . $j)->getValue()), 
				'pay_money'			=> floatval($sheet->getCell('K' . $j)->getValue()), 
				'weight'			=> floatval($sheet->getCell('F' . $j)->getValue()), 
				'pay_type'			=> $sheet->getCell('H' . $j)->getValue(), 
				'product_type'		=> $sheet->getCell('G' . $j)->getValue(), 
				'discount'			=> floatval($sheet->getCell('J' . $j)->getValue()), 
			);

			$packageInfoFromDb = $packageApi->aGetByDeliveryId($deliveryId);
			$packageInfoFromDb = $packageInfoFromDb[0];
			$diffResult = $this->_diffShunfengBill($packageInfo, $packageInfoFromDb);
			$packageInfoUpdate2Db = array();

			switch($diffResult)
			{
				case 'extraRecord' : $packageInfo['tag'] = 1; break;
				case 'match'	   : $packageInfo['tag'] = 2; $packageInfoUpdate2Db['tag'] = 2; break;
				case 'dataDiff'    : $packageInfo['tag'] = 3; $packageInfoUpdate2Db['tag'] = 3; break;
			}

			$this->_updateExcelData($this->excelShunfengDao, $packageInfo);
			if(!empty($packageInfoUpdate2Db))
				$this->packageDao->iUpdate($packageInfoFromDb['apply_id'], $packageInfoUpdate2Db);
		}

		$packageApi->updateShortBillTag('顺丰', $year, $month);
		return array('status' => 'done', 'msg' => '上传完成');;
	}

	private function _extractShunfengExcelBillDate($sheet)
	{
		$dateStr = $sheet->getCell('G5');	
		preg_match('/\d+/', $dateStr, $matches);
		if(empty($matches))
			return false;

		$year = substr($matches[0], 0, 4);
		$month= substr($matches[0], 5);

		return array('year' => $year, 'month' => $month);
	}

	private function _loadYuantongExcel($sheet, $year, $month)
	{
		$packageApi = new MDelivery_PackageApi();
		$row = $sheet->getHighestRow();
		for($j = 3; $j <= $row; $j++) {

			$deliveryId = $sheet->getCell('F' . $j)->getValue();
			if(empty($deliveryId))
				continue;
			$packageInfo = array(
				'delivery_id'		=> $deliveryId, 
				'pay_money'			=> floatval($sheet->getCell('E' . $j)->getValue()), 
				'weight'			=> floatval($sheet->getCell('D' . $j)->getValue()), 
				'sender'			=> $sheet->getCell('A' . $j)->getValue(), 
				'receiver_address'	=> $sheet->getCell('B' . $j)->getValue(), 
				'receiver'			=> $sheet->getCell('C' . $j)->getValue(), 
			);

			$packageInfoFromDb = $packageApi->aGetByDeliveryId($deliveryId);
			$packageInfoFromDb = $packageInfoFromDb[0];
			$diffResult = $this->_diffYuantongBill($packageInfo, $packageInfoFromDb);
			$packageInfoUpdate2Db = array();

			switch($diffResult)
			{
				case 'extraRecord' : $packageInfo['tag'] = 1; break;
				case 'match'	   : $packageInfo['tag'] = 2; $packageInfoUpdate2Db['tag'] = 2; break;
				case 'dataDiff'    : $packageInfo['tag'] = 3; $packageInfoUpdate2Db['tag'] = 3; break;
			}

			$this->_updateExcelData($this->excelYuantongDao, $packageInfo);
			if(!empty($packageInfoUpdate2Db))
				$this->packageDao->iUpdate($packageInfoFromDb['apply_id'], $packageInfoUpdate2Db);
		}

		$packageApi->updateShortBillTag('圆通', $year, $month);
		return array('status' => 'done', 'msg' => '上传完成');
	}

	private function _updateExcelData($companyDao, $packageInfo)
	{
	    $option = new \Ko_Tool_SQL();
		$option->oWhere('delivery_id=?', $packageInfo['delivery_id']);

        $result = $companyDao->aGetList($option);
		if(empty($result))
			$companyDao->iInsert($packageInfo);
		else
			$companyDao->iUpdate($result[0]['id'], $packageInfo);
	}

	private function _diffYuantongBill($dataFromExcel, $dataFromDb)
	{
		if(empty($dataFromDb))
			return "extraRecord";//多余数据

		$excelSender = $dataFromExcel['sender'];
		$dbSender = $dataFromDb['sender'];

		$excelReceiverAddress = $dataFromExcel['receiver_address'];
		$address = json_decode($dataFromdb['receiver_address'], true);
		if(is_array($address))
			$dbReceiverAddress = $addres['province'];//这里还要处理
		else
			$dbReceiverAddress = $dataFromdb['receiver_address'];


		if( $excelReceiverAddress != $dbReceiverAddress || $excelSender != $dbSender)
			return "dataDiff";//数据存在不一致

		return "match";//数据一致
	}


	private function _diffShunfengBill($dataFromExcel, $dataFromDb)
	{
		if(empty($dataFromDb))
			return "extraRecord";//多余数据

		$excelDeliveryTime = explode('-', $dataFromExcel['delivery_time']);
		$dbDeliveryTime = explode('-', $dataFromDb['ctime']);

		$excelSender = $dataFromExcel['sender'];
		$dbSender = $dataFromDb['sender'];

		$excelReceiverAddress = $dataFromExcel['receiver_address'];
		$address = json_decode($dataFromdb['receiver_address'], true);
		if(is_array($address))
			$dbReceiverAddress = $addres['city'];
		else
			$dbReceiverAddress = $dataFromdb['receiver_address'];

		$excelPayType = $dataFromExcel['pay_type'] == '寄付' ? 2 : 1;
		$dbPayType = $dataFromDb['pay_type'];

		if( $excelReceiverAddress != $dbReceiverAddress ||
			$excelPayType != $dbPayType ||
			$excelSender != $dbSender ||
			$excelDeliveryTime[0] != $dbDeliveryTime[1] //只保证月份一致
		)
			return "dataDiff";//数据存在不一致

		return "match";//数据一致
	}

	public function sGetUserRole($uid, $departmentInfo)
	{
		if($this->_isSectorLeader($uid, $departmentInfo))
		{
		  	if($this->_isAdministrator($uid))
				return 'administratorLeader';	
			else
				return 'leader';	
		}

		if($this->_isSecurity($uid))
			return 'security';	

		if($this->_isAdministrator($uid))
			return 'administrator';	

		return 'ordinary';
	}

	public function sGetElecBillHtml($id)
	{
		$result = $this->packageDao->aGet($id);
		if(empty($result))
			return "";
		return $result['elec_order'];
	}

	public function aGetPackageInfo($id)
	{
		$packageInfo = $this->packageDao->aGet($id);

		unset($packageInfo['id']);
		unset($packageInfo['apply_id']);
		unset($packageInfo['elec_order']);
		$address = json_decode($packageInfo['receiver_address'], true);
		if(is_array($address))
		{
			$packageInfo['receiver_province'] = $address['province']; 
			$packageInfo['receiver_city']	  = $address['city'];
			$packageInfo['receiver_exp']	  = $address['exp'];	
			$packageInfo['receiver_address']  = $address['address'];
		}

		return $packageInfo;
	}

	/*
	 * 添加新的快递申请
	 * 添加记录到apply表
	 */
	public function iApplyNewDelivery($uid, $departmentId, $deliveryCompany, $applyDesc)
	{
		$applyInfo = array(
			'uid'                           => $uid,
			'department_id'         => $departmentId,
			'delivery_id'           => 0,
			'delivery_company'      => $deliveryCompany,
			'apply_desc'            => $applyDesc,
			'audit_desc'            => '',
			'tag'                           => 1,
		);

		return $this->applyDao->iInsert($applyInfo);
	}

	/*
	 * 添加新的快递申请的包裹信息  
	 * 添加到包裹package表
	 */
	public function iInsertNewPackage($packageInfo)
	{
		$packageInfo['receiver_address'] = json_encode(
									array(
											'province'	=> $packageInfo['receiver_province'], 
											'city'		=> $packageInfo['receiver_city'], 
											'exp'		=> $packageInfo['receiver_exp'], 
											'address'	=> $packageInfo['receiver_address'], 
										));
		unset($packageInfo['receiver_province']);
		unset($packageInfo['receiver_city']);
		unset($packageInfo['receiver_exp']);
		$packageInfo['delivery_id'] = 0;
		$packageInfo['cost'] = 0;
		$packageInfo['elec_order'] = '';

		$result = $this->packageDao->iInsert($packageInfo);
	}

	/*
	 * 更新申请表、包裹表，第三方公司返回的订单编号,电子订单html
	 */
	public function supplyDeliveryInfoByApplyId($applyId, $elecBillOrderId, $elecBillHtml)
	{
		$this->applyDao->iUpdate($applyId, array('delivery_id' => $elecBillOrderId));
		$this->packageDao->iUpdate($applyId, array('delivery_id' => $elecBillOrderId, 'elec_order' => $elecBillHtml));
	}

	public function userRightCheck($uid, $packagesListArea, $departmentInfo)
	{
		switch($packagesListArea)
		{
			case 'all' : 
				if(!$this->_isAdministrator($uid)
					&& !$this->_isSecurity($uid))
					return false;
				break;
			case 'department' : 
				if(!$this->_isSectorLeader($uid, $departmentInfo))
					return false;
				break;
		//	case 'self'	: 
		};

		return true;
	}


	//判断是不是保安
	private function _isSecurity($uid)
	{
		$securityList = array(58044903);
		return in_array($uid, $securityList);
	}

	//判断是不是部门领导
	private function _isSectorLeader($uid, $departmentInfo)
	{
		$leader = $this->_getDepartmentLeader($uid, $departmentInfo);
		return $leader == $uid;
	}

	//判断是不是行政
	private function _isAdministrator($uid)
	{
		$administratorList = array(63344676);//美晨
		return in_array($uid, $administratorList);
	}

	private function _getDepartmentLeader($uid, $departmentInfo)
	{
		$leader = $uid;
		$parentId = $departmentInfo['parent_id'];
		$officeApi = new \apps\MFacade_Office_Api();
		$departmentApi = new \apps\MFacade_Office_Department();

		while(true)
		{
			if($departmentInfo['leader_id'] != $uid)
			{
				$leaderUserInfo = $officeApi->aGetEmployee($departmentInfo['leader_id']);
				if(empty($leaderUserInfo['name']))
					$leaderUserInfo['name'] = '?';
				$leader = $leaderUserInfo['id'];
			}

			$departmentInfo = $officeApi->aGetInfoById($parentId);
			if(empty($departmentInfo))
				break;
			$parentId = $departmentInfo['parent_id'];
			if($parentId == $departmentInfo['id'])//避免进入死循环，这种异常情况应该报警
				break;
		}

		return $leader;
	}

	private function _extractAddress($record)
	{
		$address = json_decode($record['receiver_address'], true);
		if(is_array($address)){
			$province = $address['province'];
			$city	  = $address['city'];
			$exp      = $address['exp'];	
			$address  = $address['address'];	
		}
		else
		{
			$province = '';
			$city 	  = $record['receiver_address'];
			$exp      = '';	
			$address  = $record['receiver_address'];
		}

		return array($province, $city, $exp, $address);
	}
}
