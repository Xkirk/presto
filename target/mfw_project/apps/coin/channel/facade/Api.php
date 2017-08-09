<?php

namespace apps\coin\channel;

class MFacade_Api
{
    private static $_oInstance = null;

    const D_USER_ADMIN_FLAG = 1;
    const D_USER_INFO_FLAG = 2;
    const D_COIN_PICK_FLAG = 3;
    const D_NOTE_FLAG = 4;
    const D_QA_FLAG = 5;
    const D_COMMENT_FLAG = 6;
    const D_POSTAL_FLAG = 7;
    const D_WENGWENG_FLAG = 8;
    const D_COIN_MOVE_FLAG = 9;
    const D_DAKA_FLAG = 10;
    const D_OTHER_ACTIVITY_FLAG = 11;
    const D_UGC_ACTIVITY_FLAG = 12;
    const D_OLD_DATA_FLAG = 13;
    const D_TO_HONEY_FLAG = 14;
    const D_DAY_TASK_FLAG = 15;

    public static $aDepartment = array(
        self::D_USER_ADMIN_FLAG => '注册/登录/后台',
        self::D_USER_INFO_FLAG => '个人信息',
        self::D_COIN_PICK_FLAG => '捡金币',
        self::D_NOTE_FLAG => '游记',
        self::D_QA_FLAG => '问答',
        self::D_COMMENT_FLAG => '点评',
        self::D_POSTAL_FLAG => '明信片券',
        self::D_WENGWENG_FLAG => '嗡嗡',
        self::D_COIN_MOVE_FLAG => '金币转账',
        self::D_DAKA_FLAG => '打卡',
        self::D_OTHER_ACTIVITY_FLAG => '其它活动',
        self::D_UGC_ACTIVITY_FLAG => '社区活动',
        self::D_OLD_DATA_FLAG => '老数据',
        self::D_TO_HONEY_FLAG => '兑换蜂蜜',
        self::D_DAY_TASK_FLAG => '每日任务',
    );

    public static function iAddChannel($sTitle,$iDid,$iCType,$iStatus,$iTotalCoin,$iSingleMaxCoin,$sExpireDate,
                                       $sContent,$sReturnDesc = '', $iChId = null)
    {
        $oInstance = self::_oGetObj();
        return $oInstance->iAddChannel($sTitle,$iDid,$iCType,$iStatus,$iTotalCoin,
            $iSingleMaxCoin,$sExpireDate,$sContent,$sReturnDesc,$iChId);
    }

    public static function bCheckTitle($sTitle)
    {
        $oInstance = self::_oGetObj();
        return $oInstance->bCheckChannelTitle($sTitle);
    }

    public static function aGetChannel($iChId)
    {
        $oInstance = self::_oGetObj();
        return $oInstance->aGetChannel($iChId);
    }

    public static function bModifyChannel($iChId,$sTitle,$iDid,$iCType,$iStatus,$iTotalCoin,
                                          $iSingleMaxCoin,$sExpireDate,$sContent,$sReturnDesc,$sSendDesc)
    {
        $oInstance = self::_oGetObj();

        $sTitle = trim($sTitle);
        $iDid = intval($iDid);
        if (strlen($sTitle) == 0 || $iDid == 0 || !self::$aDepartment[$iDid])
        {
            return false;
        }

        $aChannel = self::aGetChannel($iChId);
        if ($aChannel['title'] != $sTitle && $oInstance->bCheckChannelTitle($sTitle))
        {
            return false;
        }

        $aUpdateField = array(
            'did' => $iDid,
            'title' => $sTitle,
            'content' => $sContent,
            'return_desc' => $sReturnDesc,
            'send_desc' => $sSendDesc,
            'c_type' => $iCType,
            'status' => $iStatus,
            'total_coin' => $iTotalCoin,
            'single_max_coin' => $iSingleMaxCoin,
            'expire_date' => $sExpireDate,
            'mtime' => date('Y-m-d H:i:s'),
            'modify_admin_uid' => intval($_SESSION['admin']['id'])
        );
        $oInstance->bModifyChannel($iChId,$aUpdateField);
        return true;
    }

    public static function aGetChannels($iDid = 0 ,$iCType = 0,$iStatus = 0)
    {
        $oInstance = self::_oGetObj();
        return $oInstance->aGetChannels($iDid,$iCType,$iStatus);
    }

    public static function aGetAll()
    {
        return self::$aDepartment;
    }

    private static function _oGetObj()
    {
        if (is_null(self::$_oInstance))
        {
            self::$_oInstance = new MApi();
        }
        return self::$_oInstance;
    }
}
