<?php
/**
 * 请假模块 -- 系统管理员权限管理
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-06
 */
namespace apps\office\center;


class MLeave_systemSettingApi extends \Ko_Busi_Api {
    public static $Cache_PRE = 'leave_cache_';
    public static $SYSTEM_SETTER = array(771392);
    public static $SYSTEM_HR = array(
        'all' => 771392,
        'read' => 10891824,

    );
    public static $_TEST_MASTER = array(355713, 59638549, 771392, 10009,17232838);
    public static $LEAVE_DEFAULT_SETTING = array(
        'email_notice' => 1,
        'cancel_able' => 0,
        'hr_confirm' => 1,
    );
    public static $_OPTION_SEPARATOR_SIGN = ":";

    public function aGetList() {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('department_id=0');
        return $this->systemSettingDao->aGetList($option);
    }

    public function bCheckSystemMaster($iMfwId) {
        if ($iMfwId < 1)
            return false;
        if (MLeave_api::$_is_test) {
            return in_array($iMfwId, self::$_TEST_MASTER);
        }
        return in_array($iMfwId, self::$SYSTEM_SETTER);


    }

    public function iUpdateByOptionKey($sKey, $aValue) {
        $aValue['mtime'] = date('Y-m-d H:i:s', time());
        $option = new \Ko_Tool_SQL();
        $option->oWhere('option_key = ? ', $sKey);
        try {
            $this->systemSettingDao->iUpdateByCond($option, $aValue);

        } catch (\Exception  $e) {
            exit($e->getMessage());
        }
    }

    /*
     * 获取请假配置参数
     *
     * @access  public
     * @author  Devin
     * @param   string  $sKey  配置的key值(email_notice,cancel_able,hr_confirm)
     * @return  string  返回从数据库中配置的值
     * @date    2017-03-14
     */
    public function vGetByKey($sKey) {
        $sValue = false;
        $option = new \Ko_Tool_SQL();
        $option->oSelect('option_value');
        $option->oWhere('option_key= ? ', $sKey);
        $aRet = $this->systemSettingDao->aGetList($option);
        if (is_array($aRet) && count($aRet)) {
            $sValue = $aRet[0]['option_value'];
        }
        return $sValue;

    }

    public function createDefaultData() {
        $aData = array(
//            array('option_key' => 'email_notice', 'option_value' => 1),
//            array('option_key' => 'cancel_able', 'option_value' => 1),
//            array('option_key' => 'hr_confirm', 'option_value' => 1),
            array('option_key' => 'leave_cycle', 'option_value' => "26:25"),
        );
        foreach ($aData as $aOne) {
            try {
                $this->systemSettingDao->iInsert($aOne);
            } catch (\Exception $e) {
                exit($e->getMessage());
            }
        }
    }

}