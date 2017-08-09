<?php
/**
 * 请假模块 -- 核心Api
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-06
 */
namespace apps\office\center;

class MLeave_api extends \Ko_Busi_Api {

    public static $_LEAVE_APPROVE_QUEUE_REDIS = '_leave_appprove_queue_';
    public static $WORK_TIME_AREA = array(

        'am' => '10:00-12:00',
        'pm' => '13:30-19:00',
    );
    public static $_is_test = true;
    public static $_PAGEINATION_DEFAULT = array(
        'page_num' => 10,
        'page_deltas' => 3,
    );

    public function aGetLeaveList($aParams) {
        if (empty($aParams))
            return array();

        $aUid = array();
        $aDid = array();
        if (isset($aParams['did'])) {
            if (is_array($aParams['did']))
                $aDid = $aParams['did'];
            else if ($aParams['did'])
                $aDid = array($aParams['did']);
        }

        if (isset($aParams['word']) && $aParams['word'] != '') {
            $aEmployee = \apps\MFacade_Office_Api::aGetEmployeeByWord($aParams['word'], $aDid);
            if (count($aEmployee) > 0) {
                $aUid = \Ko_Tool_Utils::AObjs2ids($aEmployee, 'uid');
            }
            $aParams['mfw_id'] = $aUid;
        }
        $api = new MLeave_infoApi();
        $aRet = $api->aGetList($aParams);
        return $aRet;
    }

    /**
     * 添加请假信息，如果是年假类型，需要更新年假的信息同时更新年假的记录信息
     *
     * @access  public
     * @author  Devin
     * @param   array  $aInfo  请假的相关信息
     * @return  array  插入数据库后返回的信息
     * @date    2017-03-13
     */
    public function addLeaveInfo($aInfo) {
        $api = new MLeave_infoApi();
        $aLeaveRet = $api->aInsert($aInfo);
        if (!is_array($aLeaveRet) || count($aLeaveRet) == 0) 
			return $aLeaveRet;

		if ($aInfo['type_id'] == 1)								//如果是年假类型 则更新可休年假信息
			$aLeaveRet = $this->_updateAnnualLeaveInfo($aInfo, $aLeaveRet);
		else if ($aInfo['type_id'] == 5) 						//如果是关爱假类型 则更新关爱假的信息
			$aLeaveRet = $this->_updateCareOffInfo($aInfo, $aLeaveRet);

		return $aLeaveRet;
    }

    /**
     * 取消请假信息，如果是年假类型，需要更新年假的信息同时更新年假的记录信息
     *
     * @access  public
     * @author  Devin
     * @param   int  $iLeaveId  请假id
     * @return  int  非0代表取消成功
     * @date    2017-03-13
     */
    public function bCancelLeave($iLeaveId) {

        $api = new MLeave_infoApi();
        $aLeaveInfo = $api->aGetById($iLeaveId);
        if (!is_array($aLeaveInfo)) 
			return 0;

		//请假状态的变化(会更新用户的年假信息表中的this_year_available字段，代表更新前的去年年假数)
		$isn = $api->bCancelById($iLeaveId);
		if (!$isn) 
			return 0;

		//审批状态改变
		$approveApi = new MLeave_approveInfoApi();
		$approveApi->bCancelApproveByLeaveId($iLeaveId);

		if ($aLeaveInfo['type_id'] == 1) 										//如果是年假类型 则更新可休年假信息
			$this->_updateAnnualLeaveInfoWhenCancel($aLeaveInfo);
		else if ($aLeaveInfo['type_id'] == 5) 									//如果是关爱假类型 则更新关爱假的信息
			$this->_updateCareOffInfoWhenCancel($aLeaveInfo);

        return $isn;
    }

   /**
    * 审核者驳回请假信息，如果是年假类型，需要更新年假的信息同时更新年假的记录信息
    *
    * @access  public
    * @author  Devin
    * @param   int  $iLeaveId  请假id
    * @return  bool  true-成功
    * @date    2017-03-13
    */
    public function bRefuseLeave($iLeaveId) {
        $bRet = true;
        $leaveApi = new MLeave_infoApi();
        $aLeaveInfo = $leaveApi->aGetById($iLeaveId);
        $annualApi = new MLeave_annualLeaveApi();
        $aAnnualInfo = $annualApi->aGetInfoByUid($aLeaveInfo['mfw_id']);

        //请假状态的变化(会更新用户的年假信息表中的this_year_available字段，代表更新前的去年年假数)
        $isn = $leaveApi->bChangeStatus($iLeaveId, 3);
        if ($isn) {
            //如果是年假类型 则更新可休年假信息
            if ($aLeaveInfo['type_id'] == 1) {
                $used = floatval($aAnnualInfo['used']) - floatval($aLeaveInfo['time_length']);
                $curr_available = floatval($aAnnualInfo['curr_available']) + floatval($aLeaveInfo['time_length']);
                $aUpdate = array(
                    'used' => $used,
                    'curr_available' => $curr_available,
                    'last_remaind' =>
                        \apps\office\center\MLeave_commApi::fGet2BitsBy1Bit($aAnnualInfo['this_year_available']),
                );
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $aAnnualInfo['id'],
                    'remark'    => '驳回年假申请',
                );
                $iLogId = $logApi->wirteLogUpdateStart($aParam);
                $isn = $annualApi->iUpdate($aAnnualInfo['id'], $aUpdate);
                if ($isn) {
                    //记录年假增减记录
                    $aLeaveInfo['status'] = 3;//log记录申请状态为取消
                    $aLeaveInfo['reason'] = '审批人驳回';
                    $aLog = array(
                        'mfw_id' => $aLeaveInfo['mfw_id'],
                        'type' => 1,
                        'days' => floatval($aLeaveInfo['time_length']),
                        'leave_id' => $aLeaveInfo['id'],
                        'remark' => serialize($aLeaveInfo)
                    );
                    $annualLogApi = new MLeave_annualLeaveLog();
                    $annualLogApi->iInsert($aLog);
                    $logApi->wirteLogUpdateEnd($iLogId, $aParam);
                }
            //如果是关爱假类型 则更新关爱假的信息
            } else if ($aLeaveInfo['type_id'] == 5) {
                $careOffDao = new \apps\office\center\MLeave_careOffDao();
                $careOffInfo = $careOffDao->aGetCareOffInfoByUid($aLeaveInfo['mfw_id']);
                $aUpdate = array(
                    'used' => floatval($careOffInfo['used']) - floatval($aLeaveInfo['time_length']),
                    'curr_available' => floatval($careOffInfo['curr_available']) + floatval($aLeaveInfo['time_length'])
                );
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_careoff',
                    'infoid'    => $careOffInfo['id'],
                    'remark'    => '驳回关爱假申请',
                );
                $iLogId = $logApi->wirteLogUpdateStart($aParam);
                $careOffDao->bUpdateCareOffById($careOffInfo['id'], $aUpdate);
                $logApi->wirteLogUpdateEnd($iLogId, $aParam);
            }
        } else {
            $bRet = false;
        }
        return $bRet;
    }

    public function aGetListByStatus($iStatus = 0) {
        $api = new MLeave_infoApi();
        return $api->aGetList(array('status' => $iStatus));
    }

    public function aGetLeaveInfo($iLeaveId) {
        $api = new MLeave_infoApi();
        return $api->aGetById($iLeaveId);

    }

    /**
     * 为请假信息创建审核列表
     *
     * -先验证请假时长是否符合
     * -有审核权限的人才能激活请假列表
     * @access  public
     * @author  Devin
     * @param   array  $sData  请假的相关信息
     * @return  array  返回被激活的审核人列表
     * @date    2017-03-13
     * @note    先激活本部门的审核人员信息，本部门没有的话，激活其父部门的信息
     */
    public function vCreateApproveInfo($aData) {
        $ruleApi = new MLeave_approveRuleApi();
        $approveApi = new MLeave_approveInfoApi();
        $bExist = false;
        if (($fDate = $aData['time_length']) > 0) {
            $aUser = \apps\MFacade_Office_Api::aGetEmployeeByUid($aData['mfw_id']);
            //找出部门所在的审核人列表
            $aRuleList = $ruleApi->aGetListByDepartMentId($aUser['did']);
            foreach ($aRuleList as $key => $aRule) {
                if ($aRule['lowerlimit'] <= $fDate && $aRule['toplimit'] >= $fDate) {
                    $aParam = array(
                        'leave_id' => $aData['id'],
                        'approver_id' => $aRule['approver_id'],
                        'is_active' => 0
                    );
                    if ($aRule['power_type'] > 4) {  //具有审批以上的权限才能激活请假列表 4-查看 6-审批 7-所有权限
                        $bExist = true;
                        $aParam['is_active'] = 1;
                    } else {
                        unset($aRuleList[$key]);
                    }
                    $approveApi->iInsert($aParam);
                } else {
                    unset($aRuleList[$key]);
                }
            }
            if ($bExist) return $aRuleList;
            //本部门没有，去父部门找
            $aDepartmentInfo = \apps\MFacade_Office_Api::aGetInfoById($aUser['did']);
            $aParentRuleList = $ruleApi->aGetListByDepartMentId($aDepartmentInfo['parent_id']);
            foreach ($aParentRuleList as $key => $aRule) {
                if ($aRule['lowerlimit'] <= $fDate && $aRule['toplimit'] >= $fDate) {
                    $aParam = array(
                        'leave_id' => $aData['id'],
                        'approver_id' => $aRule['approver_id'],
                        'is_active' => 0
                    );
                    if ($aRule['power_type'] > 4) {  //具有审批以上的权限才能激活请假列表 4-查看 6-审批 7-所有权限
                        $bExist = true;
                        $aParam['is_active'] = 1;
                    } else {
                        unset($aParentRuleList[$key]);
                    }
                    $approveApi->iInsert($aParam);
                } else {
                    unset($aParentRuleList[$key]);
                }
            }
            if ($bExist) return $aParentRuleList;
        }
        return array();
    }

    /**
     * 根据员工的uid获取部门的审核人的信息(power_type >=6)
     *
     * @access  public
     * @author  Devin
     * @param   int  $iMfwId  员工的uid
     * @param   int  $iPowerType  审核权限类型
     * @param   int  $leave_time  请假时长
     * @return  array  返回审核人的信息，没有的话，返回数组
     * @date    2017-03-16
     * @note    策略：优先选择本部门的审核人，如果没有，再去其父部门寻找
     */
    public function getApproverInfo($iMfwId = 0, $iPowerType = 6, $leave_time = 0) {
        $bExist = false;
        //找出本部门的审核人信息
        $aUser = \apps\MFacade_Office_Api::aGetEmployeeByUid($iMfwId);
        $aApprover = $this->getDepartMentApprover($aUser['did'], $iPowerType);
        foreach ($aApprover as $key => $value) {
            if ($value['lowerlimit'] <= $leave_time && $value['toplimit'] >= $leave_time) {
                $bExist = true;
            } else {
                unset($aApprover[$key]);
            }
        }
        if ($bExist) {
            return $aApprover;
        }
        //本部门没有，找出父部门的审核人信息
        $aDepartmentInfo = \apps\MFacade_Office_Api::aGetInfoById($aUser['did']);
        $iParentId = $aDepartmentInfo['parent_id'];
        $aApproverParent = $this->getDepartMentApprover($iParentId, $iPowerType);
        foreach ($aApproverParent as $key => $value) {
            if ($value['lowerlimit'] <= $leave_time && $value['toplimit'] >= $leave_time) {
                $bExist = true;
            } else {
                unset($aApproverParent[$key]);
            }
        }
        if ($bExist) {
            return $aApproverParent;
        } else {
            return array();
        }
    }

    /**
     * HR通过请假 (只需要改变请求列表的状态就可以了)
     *
     * @access  public
     * @author  Devin
     * @param   int  $iLeaveId  请假列表id
     * @return  bool
     * @date    2017-03-16
     */
    public function bLeaveFinish($iLeaveId) {
        $api = new MLeave_infoApi();
        $isn = $api->bChangeStatus($iLeaveId, 2);
        if ($isn) {
            \apps\office\center\MLeave_commApi::sendMessage($iLeaveId, 5);
            return true;

        }
        return false;
    }

    /**
     * 部门审核人操作请假列表
     *
     * @access  public
     * @author  Devin
     * @param   int  $iId  审核列表ID
     * @param   array  $aUpdate  更新操作需要的数据
     * @return  bool  true-操作成功
     * @date    2017-03-14
     */
    public function iUpdateApproveTaskStatus($iId, $aUpdate) {
        $bRet = true;
        $approveApi = new MLeave_approveInfoApi();
        $settingApi = new MLeave_systemSettingApi();
        $leaveApi = new MLeave_infoApi();
        $aApproveInfo = $approveApi->aGetById($iId);
        //更新审核列表状态
        $isn = $approveApi->iUpdateApproveTaskStatus($iId, $aUpdate);
        if ($isn) {
            //2-审核者驳回操作
            if ($aUpdate['approve_status'] == 2) {
                $this->bRefuseLeave($aApproveInfo['leave_id']);
                \apps\office\center\MLeave_commApi::sendMessage($aApproveInfo['leave_id'], 4);
            //1-审核者批准操作
            } else {
                //需要hr审核的，将状态标记为5
                $iHr = $settingApi->vGetByKey('hr_confirm');
                if (intval($iHr) == 1) {
                    $leaveApi->bChangeStatus($aApproveInfo['leave_id'], 5);
                    \apps\office\center\MLeave_commApi::sendMessage($aApproveInfo['leave_id'], 2);
                } else {
                    $leaveApi->bChangeStatus($aApproveInfo['leave_id'], 2);
                    \apps\office\center\MLeave_commApi::sendMessage($aApproveInfo['leave_id'], 3);
                }
            }
        } else {
            $bRet = false;
        }
        return $bRet;
    }

    /**
     * 部门规则管理-获取部门规则
     * @param $iDepartMentId
     * @return array
     */
    public function getDepartMentApprover($iDepartMentId, $iPowerType = 6) {
        $api = new MLeave_approveRuleApi();
        return $api->aGetListByDepartMentIdAndPowerType($iDepartMentId, $iPowerType);
    }

    /**
     * 部门规则管理-添加部门规则
     * @param $aInfo
     * @return array|bool
     */
    public function addDepartMentApprover($aInfo) {
        $api = new MLeave_approveRuleApi();
        return $api->aInsert($aInfo);
    }


    /**
     * 获取员工的年假列表
     *
     * @access  public
     * @author  Devin
     * @param   array  $aParams
     * @return  array
     * @date    2017-03-21
     */
    public function aGetAnnualList($aParams) {
        if (empty($aParams))
            return array();

        $aUid = array();
        if ($aParams['did'])
            $iDid = $aParams['did'];
        else
            $iDid = 0;

        $depatmentTreeApi = new MLeave_departmenttreeApi();
        $aDepartmentId = $depatmentTreeApi->aGetChildByDid(intval($iDid));
        $aEmployee = \apps\MFacade_Office_Api::aGetEmployeeByWord($aParams['word'], $aDepartmentId);
        if (count($aEmployee) > 0) {
            $aUid = \Ko_Tool_Utils::AObjs2ids($aEmployee, 'uid');
        } else {
            if ($iDid !== 0 and $aParams['word'] !== '') {
                return array();
            }
        }
        $aParams['mfw_id'] = $aUid;
        $api = new MLeave_annualLeaveApi();
        $aRet = $api->aGetList($aParams);
        return $aRet;
    }

    /**
     * 根据关键字获取员工的信息
     *
     * @access  public
     * @author  Devin
     * @param   string  $sWord  关键字
     * @return  array  员工的信息
     * @date    2017-03-15
     */
    public function aGetEmployeeByWord($sWord) {
        $aEmployee = \apps\MFacade_Office_Api::aGetEmployeeByWord($sWord, array());
        return $aEmployee;
    }

    /**
     * 格式化审核列表
     *
     * @access  public
     * @author  Devin
     * @param   array  $aData  审核列表的源数据
     * @return  array  已审批(状态为HR确认)和待审批的列表
     * @date    2017-03-13
     */
    public function formatApproveList($aData) {
        $leaveApi = new MLeave_infoApi();
        $departmentApi = new MLeave_departmentApi();
        $approved = array();
        $notApproved = array();
        foreach ($aData as $key => $value) {
            $item = array();
            $leaveInfo = $leaveApi->aGetById($value['leave_id']);
            $userInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($leaveInfo['mfw_id']);
            $departmentInfo = $departmentApi->aGetInfoById($leaveInfo['did']);
            $item['user_name'] = $userInfo['name'];
            $item['department_name'] = $departmentInfo['name'];
            $item['sdate'] = date('Y-m-d', strtotime($leaveInfo['sdate']));
            $item['edate'] = date('Y-m-d', strtotime($leaveInfo['edate']));
            $item['type_desc'] = MLeave_infoApi::$LEAVE_TYPE_CONTENT[$leaveInfo['type_id']];
            $item['status_desc'] = MLeave_infoApi::$LEAVE_STATUS_CONTENT[$leaveInfo['status']];
            $item['time_length'] = $leaveInfo['time_length'];
            $item['content'] = $leaveInfo['content'];
            $item['id'] = $value['id'];  //审核列表主键ID
            //判断是否有操作的权限 1-有权限 0-无权限
            $item['op_status'] = $leaveInfo['status'] == 1 && $value['is_active'] == 1 ? 1 :0 ;
            if ($leaveInfo['status'] == 1) {
                $notApproved[] = $item;
            } elseif ($leaveInfo['status'] == 5 || $leaveInfo['status'] == 2) {
                $approved[] = $item;
            }
        }
        return array($approved, $notApproved);
    }

    /**
     * 判断是否具有请假后台权限
     *
     * @access  public
     * @author  Devin
     * @param   int  $iUid  员工id
     * @return  bool
     * @date    2017-03-20
     */
    public function bHasPermission($iUid) {
        //管理员组
        $aAdmin = MLeave_conf::$permission_conf['admin'];
        //HR组
        $aHr = MLeave_conf::$permission_conf['hr'];
        if (in_array($iUid, $aAdmin)) {
            return true;
        }
        if (in_array($iUid, $aHr)) {
            return true;
        }
        return false;
    }

    /**
     * 根据uid获取年假信息以及年假申请信息
     *
     * @access  public
     * @author  Devin
     * @param   int  $iUid  员工uid
     * @return  array
     * @date    2017-03-27
     */
    public function aGetAnnualInfoByUid($iUid) {
        $infoApi = new MLeave_infoApi();
        $annualApi = new MLeave_annualLeaveApi();
        if ($iUid < 1) {
            return array();
        }
        //获取请假信息(天数和条数)
        list($totalList, $totalDays) = $infoApi->aGetLeaveInfoByUid($iUid);
        $aAnnualInfo = $annualApi->aGetInfoByUid($iUid);
        $aResult = array(
            'last_remaind'      => $aAnnualInfo['last_remaind'],
            'curr_available'    => MLeave_commApi::fGet2BitsBy1Bit($aAnnualInfo['curr_available']),
            'total_days'        => $totalDays,
            'total_list'        => $totalList,
        );
        return $aResult;
    }
	//年假类型 则更新可休年假信息
	private function _updateAnnualLeaveInfo($aInfo, $aLeaveRet)
	{
		$annualApi = new MLeave_annualLeaveApi();
		$aAnnualInfo = $annualApi->aGetInfoByUid($aInfo['mfw_id']);

		// 年假类型时，年假信息没有找到
		if (empty($aAnnualInfo) == true) {
			return array();
		}
		$aUpdate = array(
			'used' => floatval($aAnnualInfo['used']) + floatval($aInfo['time_length']),
			'curr_available' => floatval($aAnnualInfo['curr_available']) - floatval($aInfo['time_length'])
		);
		//优先选择上年有结余的年假，先扣除去年结余的年假
		if ($aAnnualInfo['last_remaind'] > 0) {
			$aUpdate = array(
				'used' => floatval($aAnnualInfo['used']) + floatval($aInfo['time_length']),
				'curr_available' =>
				floatval($aAnnualInfo['curr_available']) - floatval($aInfo['time_length']),
				'this_year_available' => $aAnnualInfo['last_remaind'],  //更新前的去年年假数
				'last_remaind' => $aAnnualInfo['last_remaind'] - $aInfo['time_length'] >= 0 ?
				$aAnnualInfo['last_remaind'] - $aInfo['time_length'] : 0,
			);
		}
		$logApi = new \apps\office\common\MFacade_behaviorLog();
		$aParam = array(
			'kind'      => 'office_leave_annual',
			'infoid'    => $aAnnualInfo['id'],
			'remark'    => '申请年假',
		);
		$iLogId = $logApi->wirteLogUpdateStart($aParam);
		$isn = $annualApi->iUpdate($aAnnualInfo['id'], $aUpdate);

		if ($isn) {
			//记录年假增减记录
			$aLog = array(
				'mfw_id' => $aInfo['mfw_id'],
				'type' => 0,
				'days' => floatval($aInfo['time_length']),
				'leave_id' => $aLeaveRet['id'],
				'remark' => serialize($aLeaveRet)
			);
			$annualLogApi = new MLeave_annualLeaveLog();
			$annualLogApi->iInsert($aLog);
			$logApi->wirteLogUpdateEnd($iLogId, $aParam);
		}

		return $aLeaveRet;
	}

	//年假类型 则更新可休年假信息
	private function _updateAnnualLeaveInfoWhenCancel($aLeaveInfo)
	{
		$annualApi = new MLeave_annualLeaveApi();
		$aAnnualInfo = $annualApi->aGetInfoByUid($aLeaveInfo['mfw_id']);

		$used = floatval($aAnnualInfo['used']) - floatval($aLeaveInfo['time_length']);
		$curr_available = floatval($aAnnualInfo['curr_available']) + floatval($aLeaveInfo['time_length']);
		$aUpdate = array(
			'used' => $used,
			'curr_available' => $curr_available,
			'last_remaind' =>
			\apps\office\center\MLeave_commApi::fGet2BitsBy1Bit($aAnnualInfo['this_year_available']),
		);
		$logApi = new \apps\office\common\MFacade_behaviorLog();
		$aParam = array(
			'kind'      => 'office_leave_annual',
			'infoid'    => $aAnnualInfo['id'],
			'remark'    => '取消年假申请',
		);
		$iLogId = $logApi->wirteLogUpdateStart($aParam);
		$isn = $annualApi->iUpdate($aAnnualInfo['id'], $aUpdate);
		if ($isn) {
			//记录年假增减记录
			$aLeaveInfo['status'] = 4;
			$aLeaveInfo['reason'] = '员工取消';
			$aLog = array(
				'mfw_id' => $aLeaveInfo['mfw_id'],
				'type' => 1,
				'days' => floatval($aLeaveInfo['time_length']),
				'leave_id' => $aLeaveInfo['id'],
				'remark' => serialize($aLeaveInfo)
			);
			$annualLogApi = new MLeave_annualLeaveLog();
			$annualLogApi->iInsert($aLog);
			$logApi->wirteLogUpdateEnd($iLogId, $aParam);
		}
	}

	//关爱假类型 则更新关爱假的信息
	private function _updateCareOffInfo($aInfo, $aLeaveRet)
	{
		$careOffDao = new \apps\office\center\MLeave_careOffDao();
		$careOffInfo = $careOffDao->aGetCareOffInfoByUid($aInfo['mfw_id']);
		if (empty($careOffInfo)) {
			return array();
		}
		$aUpdate = array(
			'used' => floatval($careOffInfo['used']) + floatval($aInfo['time_length']),
			'curr_available' => floatval($careOffInfo['curr_available']) - floatval($aInfo['time_length'])
		);
		$logApi = new \apps\office\common\MFacade_behaviorLog();
		$aParam = array(
			'kind'      => 'office_careoff',
			'infoid'    => $careOffInfo['id'],
			'remark'    => '申请关爱假',
		);
		$iLogId = $logApi->wirteLogUpdateStart($aParam);
		$bRet = $careOffDao->bUpdateCareOffById($careOffInfo['id'], $aUpdate);
		if ($bRet === false) {
			return array();
		}
		$logApi->wirteLogUpdateEnd($iLogId, $aParam);

		return $aLeaveRet;
	}

	//关爱假类型 则更新关爱假的信息
	private function _updateCareOffInfoWhenCancel($aLeaveInfo)
	{
		$careOffDao = new \apps\office\center\MLeave_careOffDao();
		$careOffInfo = $careOffDao->aGetCareOffInfoByUid($aLeaveInfo['mfw_id']);
		$aUpdate = array(
			'used' => floatval($careOffInfo['used']) - floatval($aLeaveInfo['time_length']),
			'curr_available' => floatval($careOffInfo['curr_available']) +
			floatval($aLeaveInfo['time_length'])
		);
		$logApi = new \apps\office\common\MFacade_behaviorLog();
		$aParam = array(
			'kind'      => 'office_careoff',
			'infoid'    => $careOffInfo['id'],
			'remark'    => '取消关爱假申请',
		);
		$iLogId = $logApi->wirteLogUpdateStart($aParam);
		$careOffDao->bUpdateCareOffById($careOffInfo['id'], $aUpdate);
		$logApi->wirteLogUpdateEnd($iLogId, $aParam);
	}
}
