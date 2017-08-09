<?php
/*
 * 个人中心入口 -- office_leave_info表的模型类
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */
namespace apps\office\center;


class MLeave_infoApi extends \Ko_Busi_Api {
    /*
     * 状态变化说明：
     * 1 => '进行中',  提交后的状态
     * 2 => '已通过',  HR确认后为已通过
     * 3 => '已驳回',  部门审核者才能进行驳回操作
     * 4 => '已撤销',  状态为(1, 5)都能进行撤销操作
     * 5 => 'HR确认',  部门人员审核后成为HR确认
     */
    public static $LEAVE_STATUS_CONTENT = array(
        1 => '进行中',
        2 => '已通过',
        3 => '已驳回',
        4 => '已撤销',
        5 => 'HR确认',
    );
    public static $LEAVE_TYPE_CONTENT = array(
        1  => '年假',
        2  => '事假',
        3  => '病假',
        4  => '婚假',
        5  => '关爱假',
        6  => '产假',
        7  => '流产假',
        8  => '倒休',
        9  => '丧假',
        10 => '陪产假',
        11 => '产检假',
        12 => '出差',
        20 => '其他',
    );
    public static $NEED_ATTACHMENT_TYPE = "3,4";

    public function aGetList($aParams) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id>0');

        if (isset($aParams['mfw_id'])) {
            if (is_array($aParams['mfw_id']) && !empty($aParams['mfw_id']))
                $option->oAnd("mfw_id in(?)", $aParams['mfw_id']);
            else
                $option->oAnd('mfw_id = ? ', $aParams['mfw_id']);
        }
        if (isset($aParams['did']))
            $option->oAnd('did in (?) ', $aParams['did']);
        if (isset($aParams['status']))
            $option->oAnd('status = ? ', $aParams['status']);
        if (isset($aParams['type_id']))
            $option->oAnd('type_id = ? ', $aParams['type_id']);
        if (isset($aParams['sdate']))
            $option->oAnd('sdate>= ? ', $aParams['sdate']);
        if (isset($aParams['edate']))
            $option->oAnd('edate<= ? ', $aParams['edate']);
        if (isset($aParams['groupby'])) {
            $option->oGroupBy($aParams['groupby']);
        }
        $option->oOrderBy('id DESC');
//        echo $option->vSQL('office_leave_info');
        return $this->leaveInfoDao->aGetList($option);
    }

    public function aInsert($aInfo) {
        if (!isset($aInfo['sdate'])) {
            $aInfo['sdate'] = date('Y-m-d', strtotime($aInfo['stime']));
        }
        if (!isset($aInfo['edate'])) {
            $aInfo['edate'] = date('Y-m-d', strtotime($aInfo['etime']));
        }
        try {
            $aRet = $this->leaveInfoDao->aInsert($aInfo);
            if ($aRet['insertid'] > 0) {
                $aRet = $aRet['data'];
            }
            return $aRet;
        } catch (\Exception  $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 检查用户请假的日期段是否重复
     *
     * @access  public
     * @author  Devin
     * @param   string  $iUid  用户的uid
     * @param   string  $sStime  请假的起始日期
     * @param   string  $sEtime  请假的终止日期
     * @return  bool  true - 日期段内有重复
     * @date    2017-03-13
     */
    public function bCheckTimeIsClash($iUid, $sStime, $sEtime) {
        //(1)统一格式化时间
        $sStime = date('Y-m-d H:i:s', strtotime($sStime));
        $sEtime = date('Y-m-d H:i:s', strtotime($sEtime));
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('mfw_id = ? ', $iUid);
        //(2)请假状态只能在 1-进行中, 2-已通过, 5-HR确认 状态下
        $oOption->oAnd('status in (1, 2, 5)');
        $oOption->oAnd('(stime <= ? and etime >= ?) or (stime >= ? and etime <= ?) or (stime <= ? and etime >= ?)'
            , $sStime, $sStime, $sStime, $sEtime, $sEtime, $sEtime);
        $aRet = $this->leaveInfoDao->aGetList($oOption);
        return empty($aRet) ? false : true;
    }

    public function aGetById($iId) {
        return $this->leaveInfoDao->aGet($iId);
    }

    public function bCancelById($iId) {
        return $this->bChangeStatus($iId, 4);

    }

    /*
     * 更新请假列表中的状态
     *
     * @access  public
     * @author  Devin
     * @param   int  $iId  请假列表id
     * @param   int  $iStatus  更新后的状态
     * @return  bool  true-跟新成功
     * @date    2017-03-14
     * @note  状态描述：1-进行中 2-已通过 3-已驳回 4-已撤销 5-HR确认
     */
    public function bChangeStatus($iId, $iStatus) {
        if ($iId < 1) {
            return false;
        }
        try {
            $aLeaveInfo = $this->aGetById($iId);
            // 2, 3, 4最终状态下 恢复更新前的去年年假数
            if (in_array($iStatus, array(2, 3, 4)) && $aLeaveInfo['type_id'] == 1) {
                $annualApi = new MLeave_annualLeaveApi();
                $aAnnualInfo = $annualApi->aGetInfoByUid($aLeaveInfo['mfw_id']);
                $aUpdate = array(
                    'this_year_available' => 0.00,
                );
                $annualApi->iUpdate($aAnnualInfo['id'], $aUpdate);
            }
            $isn = $this->leaveInfoDao->iUpdate($iId, array('status' => $iStatus));
            if ($isn)
                return true;
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        return false;

    }

    /*
     * 格式化用户的请假列表
     *
     * @access  public
     * @author  Devin
     * @param   array  $list  传递过来的php二维数组
     * @return  array  格式化完后的二维数组
     * @date  2017-03-10
     */
    public function formatInfoList($list) {
        $approveApi = new MLeave_approveInfoApi();
        foreach ($list as $key => $value) {
            $list[$key]['sdate'] = date('Y-m-d', strtotime($value['sdate']));
            $list[$key]['edate'] = date('Y-m-d', strtotime($value['edate']));
            $list[$key]['type_desc'] = self::$LEAVE_TYPE_CONTENT[$value['type_id']];
            $list[$key]['status_desc'] = self::$LEAVE_STATUS_CONTENT[$value['status']];
            //判断是否可以进行撤销操作 状态在(1, 5)中 结果为1时表示可以进行撤销操作
            $list[$key]['cancel_status'] = in_array($value['status'], array(0, 1, 5)) ? 1 : 0;
            //判断审核人信息，状态(3, 5)才会有审核意见
            if (in_array($value['status'], array(2, 3, 5))) {
                $list[$key]['approve_desc'] = $approveApi->sGetMessageByIleaveId($value['id']);
            }
        }
        return $list;
    }

    /*
     * 判断是否能够操作请假信息
     *
     * @access  public
     * @author  Devin
     * @param   int  $iLeaveId  请假信息ID
     * @param   int  $type  操作类型(正确的前置状态)  1-申请者撤销(1,5) 2-部门审核或者驳回(1) 3-HR审核
     * @return  bool  true-可以操作
     * @date    2017-03-13
     */
    public function canOperation($iLeaveId, $type) {
        $bRet = false;
        if (empty($iLeaveId)) {
            return $bRet;
        }
        $aLeaveInfo = $this->aGetById($iLeaveId);
        switch ($type) {
            case 1:
                if (in_array($aLeaveInfo['status'], array(1, 5))) {
                    $bRet = true;
                }
                break;
            case 2:
                if (in_array($aLeaveInfo['status'], array(1))) {
                    $bRet = true;
                }
                break;
            case 3:
                if (in_array($aLeaveInfo['status'], array(5))) {
                    $bRet = true;
                }
                break;
            default:
                $bRet = false;
        }
        return $bRet;
    }

    /*
     * 返回用户有效的请假天数和条数
     *
     * @access  public
     * @author  Devin
     * @param   int  $iUid  员工uid
     * @return  array  返回有效的请假天数和条数
     * @date    2017-03-20
     */
    public function aGetLeaveInfoByUid($iUid) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('mfw_id = ?', $iUid);
        $option->oAnd('status in (?)', array(1, 5));
        $infoList = $this->leaveInfoDao->aGetList($option);
        $aResult = array(count($infoList), 0);
        foreach ($infoList as $item) {
            $aResult[1] += $item['time_length'];
        }
        return $aResult;
    }
}