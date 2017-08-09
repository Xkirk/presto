<?php
/**
 * 计算年假脚本
 *
 * @author  Devin
 * @version 1.0
 * @date    2017-03-21
 * @note
 *  有用的字段 (脚本执行时)
    baseday  				年假基数
    curr_available  		当前可用
    last_remaind  			去年剩余

    有用的字段（正常程序执行时）
    curr_available  		当前可用
    used					已用
    last_remaind  			去年剩余
    this_year_available     去年剩余更新前的值(方便取消年假时回滚操作)


    年假计算规则：
    1.未满6个月的。入职第一个月算一个整月（也即从当月的1号开始算），不用向office_leave_annual 中插入数据
    2.满6个月，未满1年。年假基数为5，需要向office_leave_annual中插入数据。同时每次执行任务，都需要增加其年假数
    3.每满一年，年假基数加2天
    4.1月1日，更新去年的年假数可用数(last_remaind)
    5.4月1日，清空去年的年假数，同时减少当前可用的年假数(1月1日至3月31日期间转正的不需要减少可用的年假数)
 *
 */

namespace apps\office;

include_once("/mfw_project/www2011/htdocs/global.php");

class McalculateAnnualLeave {
    public static $iBase = 5;               //初始年假基数
    public static $iMin = 5;                //年假基数下限
    public static $iMax = 15;               //年假基数上限
    public static $iStep = 2;               //年假基数每年步进值
    public static $iMonth = 12;             //每年月分母


    public function mainProcess(){
        $sMonthDay = date('m-d', time());
        switch ($sMonthDay) {
            case '01-01' : {
                $this->operationAt0101();
                break;
            }
            case '04-01' : {
                $this->operationAt0401();
                break;
            }
            default : {
                $this->operationAtDefault();
            }
        }
    }

    /**
     * 01-01 需要更新的数据
     * baseday  				年假基数    1位小数
     * curr_available  		    当前可用    2位小数
     * last_remaind  			去年剩余    1位小数
     *
     * @access  public
     * @author  Devin
     * @note  插入操作肯定是员工转正时第一次的操作，所以计算年假天数要从入职时开始
     */
    public function operationAt0101() {
        $aEmployeeList = \apps\MFacade_Office_Api::aGetEmployeeList();
        $annualLeaveApi = new \apps\office\center\MLeave_annualLeaveApi();
        $sNowDate = date('Y-m-d', time());
        foreach ($aEmployeeList as $aUserInfo) {
            if ($aUserInfo['entry_date'] == '0000-00-00') {
                continue;
            }
            $sEntryDate = date('Y-m-01', strtotime($aUserInfo['entry_date']));
            $iMonthSum = $this->iGetMonthNum($sEntryDate, $sNowDate);
            $iYearNum = intval($iMonthSum / self::$iMonth);
            $iMonthNum = intval($iMonthSum % self::$iMonth);
            if ($iYearNum < 1 && $iMonthNum < 6) {
                continue;  //员工未转正，不做处理
            }
            $iBaseDay = $this->iGetBaseDayByYearNum($iYearNum);
            $aAnnualLeaveInfo = $annualLeaveApi->aGetInfoByUid($aUserInfo['uid']);
            if (empty($aAnnualLeaveInfo)) {
                //插入操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay, $iMonthNum);
                $aData = array(
                    'mfw_id'            => $aUserInfo['uid'],
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $fIncDay,
                    'last_remaind'      => \apps\office\center\MLeave_commApi::fGet2BitsBy1Bit($fIncDay),
                );
                $iResult = $annualLeaveApi->iInsert($aData);
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $iResult,
                    'remark'    => '1月1日添加年假',
                );
                $logApi->wirteLogInsertEnd($aParam);
            } else {
                //更新操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay);
                $aData = array(
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $aAnnualLeaveInfo['curr_available'] + $fIncDay,
                    'last_remaind'      => \apps\office\center\MLeave_commApi::fGet2BitsBy1Bit(
                        $aAnnualLeaveInfo['curr_available'] + $fIncDay)
                );
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $aAnnualLeaveInfo['id'],
                    'remark'    => '1月1日更新年假',
                );
                $iLogId = $logApi->wirteLogUpdateStart($aParam);
                $annualLeaveApi->iUpdate($aAnnualLeaveInfo['id'], $aData);
                $logApi->wirteLogUpdateEnd($iLogId, $aParam);
            }
        }
    }
    /**
     * 04-01 需要更新的数据
     * baseday  				年假基数    1位小数
     * curr_available  		    当前可用    2位小数
     * last_remaind  			去年剩余    1位小数  置为0
     *
     * @access  public
     * @author  Devin
     * @note  插入操作肯定是员工转正时第一次的操作，所以计算年假天数要从入职时开始。
     *        在1月1日至3月31日期间转正的员工。上年的年假不清空。也就是curr_available不会减去last_remaind的值
     */
    public function operationAt0401() {
        $aEmployeeList = \apps\MFacade_Office_Api::aGetEmployeeList();
        $annualLeaveApi = new \apps\office\center\MLeave_annualLeaveApi();
        $sNowDate = date('Y-m-d', time());
        foreach ($aEmployeeList as $aUserInfo) {
            if ($aUserInfo['entry_date'] == '0000-00-00') {
                continue;
            }
            $sEntryDate = date('Y-m-01', strtotime($aUserInfo['entry_date']));
            $iMonthSum = $this->iGetMonthNum($sEntryDate, $sNowDate);
            $iYearNum = intval($iMonthSum / self::$iMonth);
            $iMonthNum = intval($iMonthSum % self::$iMonth);
            if ($iYearNum < 1 && $iMonthNum < 6) {
                continue;  //员工未转正，不做处理
            }
            $iBaseDay = $this->iGetBaseDayByYearNum($iYearNum);
            $aAnnualLeaveInfo = $annualLeaveApi->aGetInfoByUid($aUserInfo['uid']);
            if (empty($aAnnualLeaveInfo)) {
                //插入操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay, $iMonthNum);
                $aData = array(
                    'mfw_id'            => $aUserInfo['uid'],
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $fIncDay,
                );
                $iResult = $annualLeaveApi->iInsert($aData);
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $iResult,
                    'remark'    => '4月1日添加年假',
                );
                $logApi->wirteLogInsertEnd($aParam);
            } else {
                //更新操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay);
                $bResult = $this->bGetTimeJudgementByDate($sEntryDate);  //判断是否在1月1日至4月1日之间转正的
                $fCurrentAvailable = $bResult ? $aAnnualLeaveInfo['curr_available'] + $fIncDay :
                    $aAnnualLeaveInfo['curr_available'] + $fIncDay - $aAnnualLeaveInfo['last_remaind'];
                $aData = array(
                    'baseday'               => $iBaseDay,
                    'curr_available'        => $fCurrentAvailable,
                    'last_remaind'          => 0,
                    'this_year_available'   => 0,
                );
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $aAnnualLeaveInfo['id'],
                    'remark'    => '4月1日更新年假',
                );
                $iLogId = $logApi->wirteLogUpdateStart($aParam);
                $annualLeaveApi->iUpdate($aAnnualLeaveInfo['id'], $aData);
                $logApi->wirteLogUpdateEnd($iLogId, $aParam);
            }
        }
    }

    /**
     * 正常月份的操作 需要更新的数据
     * baseday  				年假基数    1位小数
     * curr_available  		    当前可用    2位小数
     *
     * @access  public
     * @author  Devin
     * @note  插入操作肯定是员工转正时第一次的操作，所以计算年假天数要从入职时开始
     */
    public function operationAtDefault() {
        $aEmployeeList = \apps\MFacade_Office_Api::aGetEmployeeList();
        $annualLeaveApi = new \apps\office\center\MLeave_annualLeaveApi();
        $sNowDate = date('Y-m-d', time());
        foreach ($aEmployeeList as $aUserInfo) {
            if ($aUserInfo['entry_date'] == '0000-00-00') {
                continue;
            }
            $sEntryDate = date('Y-m-01', strtotime($aUserInfo['entry_date']));
            $iMonthSum = $this->iGetMonthNum($sEntryDate, $sNowDate);
            $iYearNum = intval($iMonthSum / self::$iMonth);
            $iMonthNum = intval($iMonthSum % self::$iMonth);
            if ($iYearNum < 1 && $iMonthNum < 6) {
                continue;  //员工未转正，不做处理
            }
            $iBaseDay = $this->iGetBaseDayByYearNum($iYearNum);
            $aAnnualLeaveInfo = $annualLeaveApi->aGetInfoByUid($aUserInfo['uid']);
            if (empty($aAnnualLeaveInfo)) {
                //插入操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay, $iMonthNum);
                $aData = array(
                    'mfw_id'            => $aUserInfo['uid'],
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $fIncDay,
                );
                $iResult = $annualLeaveApi->iInsert($aData);
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $iResult,
                    'remark'    => '正常月份添加年假',
                );
                $logApi->wirteLogInsertEnd($aParam);
            } else {
                //更新操作
                $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay);
                $aData = array(
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $aAnnualLeaveInfo['curr_available'] + $fIncDay,
                );
                $logApi = new \apps\office\common\MFacade_behaviorLog();
                $aParam = array(
                    'kind'      => 'office_leave_annual',
                    'infoid'    => $aAnnualLeaveInfo['id'],
                    'remark'    => '正常月份更新年假',
                );
                $iLogId = $logApi->wirteLogUpdateStart($aParam);
                $annualLeaveApi->iUpdate($aAnnualLeaveInfo['id'], $aData);
                $logApi->wirteLogUpdateEnd($iLogId, $aParam);
            }
        }
    }

    /**
     * 初始化office_leave_annual表中
     * curr_available(当前可用) 2位小数
     * baseday(年假基数)        1位小数
     *
     * @access  public
     * @author  Devin
     * @date    2017-03-21
     * @note    初始化操作时，计算年假的天数全部从入职的时候开始的
     */
    public function initData() {
        $aEmployeeList = \apps\MFacade_Office_Api::aGetEmployeeList();
        $annualLeaveApi = new \apps\office\center\MLeave_annualLeaveApi();
        $sNowDate = date('Y-m-d', time());
        foreach ($aEmployeeList as $aUserInfo) {
            if ($aUserInfo['entry_date'] == '0000-00-00') {
                continue;
            }
            $sEntryDate = date('Y-m-01', strtotime($aUserInfo['entry_date']));
            $iMonthSum = $this->iGetMonthNum($sEntryDate, $sNowDate);
            $iYearNum = intval($iMonthSum / self::$iMonth);
            $iMonthNum = intval($iMonthSum % self::$iMonth);
            if ($iYearNum < 1 && $iMonthNum < 6) {
                continue;  //员工未转正，不做处理
            }
            $iBaseDay = $this->iGetBaseDayByYearNum($iYearNum);
            $aAnnualLeaveInfo = $annualLeaveApi->aGetInfoByUid($aUserInfo['uid']);
            $fIncDay = $this->fGetIncDaysByMonthNum($iBaseDay, $iMonthNum);
            if (empty($aAnnualLeaveInfo)) {
                //插入操作
                $aData = array(
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $fIncDay,
                    'mfw_id'            => $aUserInfo['uid']
                );
                $iResult = $annualLeaveApi->iInsert($aData);
            } else {
                //更新操作
                $aData = array(
                    'baseday'           => $iBaseDay,
                    'curr_available'    => $fIncDay,
                );
                $iResult = $annualLeaveApi->iUpdate($aAnnualLeaveInfo['id'], $aData);
            }
            if ($iResult) {
                echo $aUserInfo['name_py'] . '年假信息更新成功!' . "\n";
            } else {
                echo $aUserInfo['name_py'] . '年假信息更新失败!' . "\n";
            }
        }
    }

    /**
     * 根据员工入职日期判断转正的时间是否在1月1日至4月1日之间
     *
     * @access  public
     * @author  Devin
     * @param   string   $sEntryDate  入职日期 2016-06-01
     * @return  bool
     */
    public function bGetTimeJudgementByDate($sEntryDate) {
        $iTurnRightTimestamp = strtotime($sEntryDate) + 6 * 30 * 24 * 3600;
        $iStartTimestamp = strtotime(date('Y-01-01', time()));
        $iEndTimestamp = strtotime(date('Y-04-01', time()));
        if ($iStartTimestamp <= $iTurnRightTimestamp && $iTurnRightTimestamp <= $iEndTimestamp) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据年限获取员工的年假基数
     *
     * @access  public
     * @author  Devin
     * @param   int  $iYearNum  年限
     * @return  int  返回年假的基数
     * @date    2017-03-21
     */
    public function iGetBaseDayByYearNum($iYearNum) {
        if ($iYearNum < 1) {
            $iRet = self::$iBase;
            return $iRet;
        }
        $iRet = self::$iBase + ($iYearNum -1) * self::$iStep;
        $iRet = $iRet > self::$iMax ? self::$iMax : $iRet;
        return $iRet;
    }

    /**
     * 根据月份数和年假基数获取每个月增加的年假数
     *
     * @access  public
     * @author  Devin
     * @param   int   $iBaseDay  年假基数
     * @param   int   $iMonthNum  月份数（默认为1，每月1日为员工增加年假数）
     * @return  float  返回增加的年假数的两位小数
     */
    public function fGetIncDaysByMonthNum($iBaseDay, $iMonthNum = 1) {
        $iPerMonthday = round($iBaseDay / self::$iMonth, 2);
        return $iPerMonthday * $iMonthNum;
    }

    /**
     * 判断两个日期直接相差的月份
     *
     * @access  public
     * @author  Devin
     * @param   string  $sStartDate  开始日期 2017-02-01
     * @param   string  $sEndDate   结束日期  2017-03-01
     * @param   string  $sTag  日期的分隔符
     * @return  int  返回相差的月份
     * @date    2017-03-21
     */
    public function iGetMonthNum($sStartDate, $sEndDate, $sTag = '-'){
        $aDate1 = explode($sTag,$sStartDate);
        $aDate2 = explode($sTag,$sEndDate);
        return abs($aDate1[0] - $aDate2[0]) * 12 + abs($aDate1[1] - $aDate2[1]);
    }
}

$api = new McalculateAnnualLeave();
// 初始化office_leave_annual表中的this_year_available(当年可用)字段
if ($argv[1] == 'init') {
    $api->initData();exit;
}
$api->mainProcess();