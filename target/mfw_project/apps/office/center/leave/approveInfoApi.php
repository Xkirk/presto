<?php
/**
 * 个人中心入口 -- 请假模块
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */

namespace apps\office\center;

/**
 * @property \Ko_Dao_Config $approveInfoDao
 */
class MLeave_approveInfoApi extends \Ko_Busi_Api {
    public static $TASK_ACTIVE = array(
        0 => '未激活',
        1 => '激活',
        2 => '已完成',
        3 => '申请人取消',
    );

    public static $_b_is_test = true;
    public static $STATUS_CONTENT = array(
        0 => '暂无',
        1 => '通过',
        2 => '驳回',
    );

    public function aInsert($aInfo) {
        return $this->approveInfoDao->aInsert($aInfo);
    }

    public function iInsert($aInfo) {
        return $this->approveInfoDao->iInsert($aInfo);
    }

    public function aGetApproveProssByLeaveId($iLid, $iStatus = -1) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('leave_id = ? ', $iLid);
        if ($iStatus != -1) {
            $option->oAnd('approve_status = ?', $iStatus);
        }
        return $this->approveInfoDao->aGetList($option);
    }

    public function aGetProssByLeaveIdAndApproverId($iLid, $iUid) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('leave_id = ? ', $iLid);
        $option->oAnd('approver_id = ? ', $iUid);
        return $this->approveInfoDao->aGetList($option);
    }

    public function getApproveTaskListByUid($iUid, $iIsActive = -1) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('approver_id = ? ', $iUid);
        $option->oAnd('is_active<>3');
        if ($iIsActive > -1) {
            $option->oAnd('is_active = ? ', $iIsActive);
        }
        $option->oOrderBy('id DESC');
        return $this->approveInfoDao->aGetList($option);
    }

    public function aGetApproveTaskCount($iUid) {
        $option = new \Ko_Tool_SQL();
        $option->oSelect('is_active,count(id) as count');
        $option->oWhere('approver_id = ? ', $iUid);
        //过滤员工撤销记录
        $option->oAnd('is_active<>3');
        $option->oGroupBy('is_active');

        $aRet = $this->approveInfoDao->aGetList($option);
        return $aRet;
    }

    public function iUpdateApproveTaskStatus($iId, $aUpdate) {
        return $this->approveInfoDao->iUpdate($iId, $aUpdate);
    }


    public function iUpdateActiveByApproverIdAndLid($iLid, $iAid, $iactive) {
        $option = new \Ko_Tool_SQL();

        $option->oWhere('approver_id = ? ', $iAid);
        $option->oAnd('leave_id = ? ', $iLid);
        return $this->approveInfoDao->iUpdateByCond($option, array('is_active' => $iactive));
    }

    public function iUpdateActiveByLeaveId($iLid, $iActive) {
        $option = new \Ko_Tool_SQL();

        $option->oWhere('leave_id = ? ', $iLid);
        return $this->approveInfoDao->iUpdateByCond($option, array('is_active' => $iActive));
    }

    /*
     * 申请者取消申请，将任务状态置为3-申请人取消
     *
     * @access  public
     * @author  Devin
     * @param   string  $lId  请假记录ID
     * @return  int  返回更新后的主键ID
     * @date    2017-03-13
     */
    public function bCancelApproveByLeaveId($lId) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('leave_id = ? ', $lId);
        return $this->approveInfoDao->iUpdateByCond($option, array('is_active' => 3));
    }

    /*
     * 获取审核列表信息
     *
     * @access  public
     * @author  Devin
     * @param   int  $iId  唯一键id
     * @return  array
     * @date    2017-03-14
     */
    public function aGetById($iId) {
        if ($iId > 0) {
            return $this->approveInfoDao->aGet($iId);
        }
        return array();
    }

    /*
     * 根据leaveId获取审核人的结论
     *
     * @access  public
     * @author  Devin
     * @param   int  $iLeaveId  请假列表ID
     * @return  string
     * @date    2017-03-22
     */
    public function sGetMessageByIleaveId($iLeaveId) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('leave_id = ?', $iLeaveId);
        $option->oAnd('is_active = 2');  //0-未激活 1-激活 2-已完成 3-申请人取消
        $aResult = $this->approveInfoDao->aGetList($option);
        $aApproveInfo = $aResult[0];
        $aApproveUserInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($aApproveInfo['approver_id']);
        $sMsg = '部门领导'.$aApproveUserInfo['name'] .
            self::$STATUS_CONTENT[$aApproveInfo['approve_status']] . '你的请假请求';
        return $sMsg;
    }
}