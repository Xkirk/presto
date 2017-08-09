<?php
/*
 * 请假模块-公用类
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */

namespace apps\office\center;
require_once(COMMON_3RD_PATH . 'mailer/PHPMailer.php');


class MLeave_commApi extends \Ko_Busi_Api {

    private static $sLogDir = '/home/caodi/leave_system_log/';  //请假系统存放日志目录

    public function aGetCycleByMonth($sMonth) {
        $settingApi = new MLeave_systemSettingApi();

        $sLeaveCycle = $settingApi->vGetByKey('leave_cycle');
        list($offsetday, $endday) = explode(MLeave_systemSettingApi::$_OPTION_SEPARATOR_SIGN, $sLeaveCycle);
        list($iYear, $iMonth) = explode('-', $sMonth);
        $sStartDate = implode('-', array($iYear, $iMonth - 1, $offsetday));
        $sEndDate = implode('-', array($iYear, $iMonth, $offsetday));
        return array('sdate' => $sStartDate, 'edate' => $sEndDate);
    }

    public function fGetLeaveTimeLength($aLeave, $aLeaveCycle) {
        if ($aLeave['edate'] <= $aLeaveCycle['edate']) {
            $fLength = $this->getLeaveTime($aLeave['stime'], $aLeave['etime']);
        } else {
            $etime = $aLeaveCycle['edate'] . " 23:23:59";
            $fLength = $this->getLeaveTime($aLeave['stime'], $etime);
        }
        return $fLength;
    }

    /*
     * 根据起始的时间戳，计算请假的时长
     *
     * @access  public
     * @author  Devin
     * @param   string  $iStartDate  开始时间  2017/03/07 16:00
     * @param   string  $iEndDate  结束时间  2017/03/13 10:30
     * @return  mixed  false OR float
     * @date    2017-03-07
     */
    public function getLeaveTime($iStartDate, $iEndDate) {
        $iStartTime = strtotime($iStartDate);
        $iEndTime = strtotime($iEndDate);
        if ($iEndTime <= $iStartTime) {
            return false;
        }
        $iDays = 0;
        $item = $iStartTime + 24 * 3600;
        //计算中间的天数
        while ($item <= $iEndTime - 24*3600) {
            if (!$this->checkLeaveDay($item)) {
                $iDays++;
            }
            $item = $item + 24 * 3600;
        }
        //如果是同一天，需要单独处理
        if (date('Y/m/d', $iStartTime) == date('Y/m/d', $iEndTime)) {
            $iHourTotal = !$this->checkLeaveDay($iStartTime) ?
                date("H", $iEndTime) - date("H", $iStartTime) : 0;
        } else {
            $iHourHalf1 = !$this->checkLeaveDay($iStartTime) ? 19 - date("H", $iStartTime) : 0;
            $iHourHalf2 = !$this->checkLeaveDay($iEndTime) ? date("H", $iEndTime) - 10 : 0;
            $iHourTotal = $iHourHalf1 + $iHourHalf2;
        }
        $iDays = $iDays + floor($iHourTotal / 9);
        $iHour = $iHourTotal % 9;
        if ($iHour > 4) {
            $iDays++;
        } else if ($iHour > 0 and $iHour <= 4) {
            $iDays = $iDays + 0.5;
        }
        return $iDays;
    }

    /*
     * 检查日期
     *
     * @access  public
     * @author  Devin
     * @param   int  $iTime  时间戳
     * @return  bool  true（不需要计算在假期中）
     * @date    2017-03-07
     */
    public function checkLeaveDay($iTime) {
        //判断是否在节假日中
        $iYear = date('Y', $iTime);
        $date = date('m/d', $iTime);
        $week = date('w', $iTime);

        $conf = MLeave_conf::$holiday_conf[$iYear];
        if (in_array($date, $conf['holiday'])) {
            return true;
        }
        //判断是否在周末中，同时不在周末加班的日期中
        if (in_array($week, array(0, 6)) && !in_array($date, $conf['weekday_need_work'])) {
            return true;
        }
        return false;
    }

    /*
     * 发送邮件
     *
     * @access  public
     * @author  Devin
     * @param   string  $title  邮件主题
     * @param   string  $body   邮件内容
     * @param   array   $recipients  收件人
     */
    public static function _vSendMail($title, $body, $recipients) {
        $mail = new \PHPMailer();

        $title = \Ko_Tool_Str::SConvert2GB18030($title);
        $body = \Ko_Tool_Str::SConvert2GB18030($body);
        $mail->Host = 'smtp.mafengwo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hr@mafengwo.com';
        $mail->Password = 'Mfw123';
        $mail->From = 'hr@mafengwo.com';
        $mail->FromName = \Ko_Tool_Str::SConvert2GB18030('蚂蜂窝请假系统');
        $mail->CharSet = 'GB2312';
        $mail->Body = $body;
        $mail->Subject = "=?GB2312?B?" . base64_encode($title) . "?=";
        $mail->Timeout = 30;
        $mail->IsSMTP();
        $mail->IsHTML(true);

        foreach ($recipients as $address) {
            $mail->AddAddress($address);
        }
        $mail->Send();
    }

    /*
     * 判断某一个值是否在二维数组中
     *
     * @access  public
     * @author  Devin
     * @param   string  $needle  value
     * @param   array  $haystack 二维数组
     * @param   string  $key  关系数组时才会有此值
     * @return  bool  true-存在数组中
     * @date    2017-03-16
     */
    public static function inDoubleArr($needle, $haystack, $key = '') {
        if (!is_array($haystack)) {
            return false;
        }
        foreach ($haystack as $item) {
            //数字数组时的判断
            if (empty($key)) {
                if (in_array($needle, $item)) {
                    return true;
                }
            //关系数组
            } else {
                if ($item[$key] == $needle) {
                    return true;
                }
            }
        }
        return false;
    }

    /*
     * 发送邮件通知
     *
     * @access  public
     * @author  Devin
     * @param   int   $ileaveId  请假列表id
     * @param   int  $type  类型 1-分配审核 2-部门审核 3-部门审核后完成 4-部门驳回 5-HR通过后完成
     * @param   array  $recipients  收件人
     * @date    2017-03-20
     */
    public static function sendMessage($ileaveId, $type, $recipients = array()) {
        $settingApi = new MLeave_systemSettingApi();
        $infoApi = new MLeave_infoApi();
        if ($settingApi->vGetByKey('email_notice') == 0 && $type != 1) {
            return;
        }
        $iOpId = \apps\user\MFacade_Api::iLoginUid();
        $aLeaveInfo = $infoApi->aGetById($ileaveId);
        $aLeaveInfo['type_desc'] = MLeave_infoApi::$LEAVE_TYPE_CONTENT[$aLeaveInfo['type_id']];
        $aUserInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($aLeaveInfo['mfw_id']);
        $aOpInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($iOpId);
        switch ($type) {
            case 1:
                $title = "申请请假";
                $body = $aUserInfo['name'].'申请从'.$aLeaveInfo['stime'].'到'.$aLeaveInfo['etime'].
                    '共计'.$aLeaveInfo['time_length'].'天的'.$aLeaveInfo['type_desc'];
                break;
            case 2:
                $title = "部门审核通过，待HR确认";
                $body = '你申请的假期('.$aLeaveInfo['stime'].'到'.$aLeaveInfo['etime']. '共计'.
                    $aLeaveInfo['time_length']
                    .'天的'.$aLeaveInfo['type_desc'].')已经被'.$aOpInfo['name'].'批准通过了，等待HR确认';
                break;
            case 3:
                $title = "部门审核通过，请假申请完成";
                $body = '你申请的假期('.$aLeaveInfo['stime'].'到'.$aLeaveInfo['etime']. '共计'.
                    $aLeaveInfo['time_length']
                    .'天的'.$aLeaveInfo['type_desc'].')已经被'.$aOpInfo['name'].'批准通过了，已完成';
                break;
            case 4:
                $title = "部门审核未通过，请假申请被驳回";
                $body = '你申请的假期('.$aLeaveInfo['stime'].'到'.$aLeaveInfo['etime']. '共计'.
                    $aLeaveInfo['time_length']
                    .'天的'.$aLeaveInfo['type_desc'].')已经被'.$aOpInfo['name'].'驳回了!';
                break;
            case 5:
                $title = "HR已确认，请假申请完成";
                $body = '你申请的假期('.$aLeaveInfo['stime'].'到'.$aLeaveInfo['etime']. '共计'.
                    $aLeaveInfo['time_length']
                    .'天的'.$aLeaveInfo['type_desc'].')已经被'.$aOpInfo['name'].'确认了!';
                break;
            default :
                return;

        }
        $recipients = empty($recipients) ? array($aUserInfo['email']) : $recipients;
        self::_vSendMail($title, $body, $recipients);
    }

    /*
     * 两位小数转化为一位小数的年假计算规则
     *
     * @access  public
     * @author  Devin
     * @param   float  $f2Day  两位小数的年假天数
     * @return  float  一位小数的年假天数
     * @date    2017-03-21
     */
    public static function fGet2BitsBy1Bit($f2Day) {
        $f1Day = round($f2Day, 1);
        $iDay = $f1Day * 10;
        $iDayQuotient = intval($iDay / 5);  //商
        $iDayRemainder = $iDay % 5;  //余数
        if ($iDayRemainder > 2.5) {
            $iDayResult = $iDayQuotient * 5 + 5;
        } else {
            $iDayResult = $iDayQuotient * 5;
        }
        return round($iDayResult / 10, 1);

    }

    /*
     * 记录系统日志
     *
     * @access  public
     * @author  Devin
     * @param   string $log_content 日志内容
     * @param   string $log_name 日志文件名前缀，可不输入
     * @return  null
     * @date    2017-03-22
     * @note 日志文件名 = $log_name + ".年-月-日" + .log 例: abc.2012-03-19.log
     */
    public static function DLOG($log_content='', $log_name='leave')
    {
        if(empty($log_content)) {
            return;
        }

        $log_dir = self::$sLogDir;
        $log_file = $log_dir.$log_name.".".date('Y-m-d').".log";

        $time = sprintf("%8s.%03d",date('H:i:s'),floor(microtime()*1000));

        $ip = sprintf("%15s",$_SERVER["REMOTE_ADDR"]);
        $path_arr = explode("/", $_SERVER['PATH_INFO']);
        $content_prefix = "[ ".$time." ".$ip." ".$path_arr[0]." ] ";
        $fp = fopen($log_file, 'a+');
        fwrite($fp, $content_prefix.$log_content." [".getmypid()."]\n");
        fclose($fp);
        return;
    }

    public static function xlog($msg, $tpye) {
        $pid = getmypid();
        $sLineBreak = "</br>";
        if (isset($_SERVER['SHELL']))
            $sLineBreak = "\n";

        echo date('Y-m-d H:i:s') . "|" . $tpye . ",pid=" . $pid . "|" . $msg . "{$sLineBreak}";
    }
}