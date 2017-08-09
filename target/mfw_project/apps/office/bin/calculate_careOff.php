<?php
/**
 * 计算关爱假脚本
 *
 * @author  Devin
 * @version 1.0
 * @date    2017-06
 * @note
    关爱假计算规则：
    1.性别为女生
    2.每个月涨0.5天，每月1号开始计算
    3.关爱假不会清零
 *
 */

namespace apps\office;

include_once("/mfw_project/www2011/htdocs/global.php");

class McalculateCareOff {
    public static $iStep = 0.5;               //关爱假每月增加的步数

    /**
     * 主方法入口
     */
    public function mainProcess() {
        $aEmployeeList = \apps\MFacade_Office_Api::aGetEmployeeList();
        foreach ($aEmployeeList as $aEmployeeInfo) {
            if ($aEmployeeInfo['gender'] == 1) {
                continue;
            }
            $this->careOffOption($aEmployeeInfo['uid']);
        }
    }

    /**
     * 根据员工的uid来判断，是更新关爱假信息，还是插入关爱假信息
     *
     * @access  public
     * @author  Devin
     * @param   int   $iUid  员工uid
     * @date    2017-06
     */
    public function careOffOption($iUid) {
        $careOffDao  = new \apps\office\center\MLeave_careOffDao();
        $careOffInfo = $careOffDao->aGetCareOffInfoByUid($iUid);
        //插入操作
        if (empty($careOffInfo)) {
            $aData = array(
                'mfw_id'            => $iUid,
                'curr_available'    => self::$iStep,
                'used'              => 0,
            );
            $iResult = $careOffDao->iInsertCareOff($aData);
            $logApi = new \apps\office\common\MFacade_behaviorLog();
            $aParam = array(
                'kind'      => 'office_careoff',
                'infoid'    => $iResult,
                'remark'    => '正常月份添加关爱假',
            );
            $logApi->wirteLogInsertEnd($aParam);
        //更新操作
        } else {
            $aUpdate = array(
                'curr_available' => $careOffInfo['curr_available'] + self::$iStep,
            );
            $logApi = new \apps\office\common\MFacade_behaviorLog();
            $aParam = array(
                'kind'      => 'office_careoff',
                'infoid'    => $careOffInfo['id'],
                'remark'    => '正常月份更新关爱假',
            );
            $iLogId = $logApi->wirteLogUpdateStart($aParam);
            $careOffDao->bUpdateCareOffById($careOffInfo['id'], $aUpdate);
            $logApi->wirteLogUpdateEnd($iLogId, $aParam);
        }
    }
}
$api = new McalculateCareOff();
$api->mainProcess();