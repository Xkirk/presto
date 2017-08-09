<?php
/**
 * 请假模块 -- 审核权限管理
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-06
 */

namespace apps\office\center;


class MLeave_approveRuleApi extends \Ko_Busi_Api {
    public static $TEST_APPROVER = 355713;

    public static $POWER_TYPE = array(
        4 => '查看',
        6 => '审批',
    );


    public function aInsert($aRule) {
        try {
            $aRet = $this->approveRuleDao->aInsert($aRule);
            if ($aRet['insertid'] > 0) {
                $aRet = $aRet['data'];
            }
            return $aRet;
        } catch (\Exception $e) {
            return false;
        }

    }

    public function iUpdateById($iId, $aUpdate) {
        if (empty($aUpdate))
            return 0;
        return $this->approveRuleDao->iUpdate($iId, $aUpdate);
    }

    public function aGetListByDepartMentId($iDid) {
        if ($iDid < 1)
            return array();
        else {
            try {
                return $this->aGetList(array('department_id' => $iDid));

            } catch (\Exception $e) {
                return array();
            }
        }
    }

    public function aGetListByDepartMentIdAndPowerType($iDid, $iPowerType) {
        $aParam = array(
            'department_id' => $iDid,
            'power_type' => $iPowerType
        );
        return $this->aGetList($aParam);
    }

    public function aGetList($aParams) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id>0');
        if ($aParams['department_id'] > 0)
            $option->oAnd('department_id = ? ', $aParams['department_id']);
        if ($aParams['approver_id'] > 0)
            $option->oAnd('approver_id = ? ', $aParams['approver_id']);
        if (isset($aParams['power_type'])) {
            if (is_array($aParams['power_type']))
                $option->oAnd('power_type in(?)', $aParams['power_type']);
            else {
                $option->oAnd('power_type >= ? ', $aParams['power_type']);
            }
        }
        $option->oOrderBy('lowerlimit asc');
//        echo $option->vSQL('office_leave_approve_rule');
        return $this->approveRuleDao->aGetList($option);
    }

    /*
     * 检查该用户是否有审核的权限
     *
     * @access  public
     * @author  Devin
     * @param   string  $iUid  用户的uid
     * @return  bool  true - 具有审核权限
     * @date    2017-03-10
     */
    public function bCheckIsApprover($iUid) {
        if ($iUid < 1) {
            return false;
        }
        try {
            $option = new \Ko_Tool_SQL();
            $option->oWhere('approver_id = ? ', $iUid);
            $aRet = $this->approveRuleDao->aGetList($option);
            if (count($aRet) > 0)
                return true;
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        return false;
    }

    public function bDeleteById($iId) {
        if ($iId < 1)
            return false;
        return $this->approveRuleDao->iDelete($iId);
    }


    public function aGetById($iId) {
        if ($iId > 0) {
            return $this->approveRuleDao->aGet($iId);
        }
        return array();
    }

    /*
     * 根据审核列表返回审核人的邮件数组
     *
     * @access  public
     * @author  Devin
     * @param   array  $aList  审核人列表(二维数组)
     * @return  array  审核人邮箱
     * @date    2017-03-20
     */
    public function aGetEmailByList($aList) {
        $aResult = array();
        foreach ($aList as $item) {
            $aUserInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($item['approver_id']);
            $aResult[] = $aUserInfo['email'];
        }
        return $aResult;
    }

}