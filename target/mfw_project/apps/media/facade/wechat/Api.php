<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2017/1/11
 * Time: 下午9:21
 */
namespace apps\media;

use apps\brand\cms\MFacade_Const;

class MFacade_Wechat_Api
{
    public static function bUpdateActLog($sAppId, $sOpenId)
    {
        $oApi = new MWechatApi();
        return $oApi->bUpdateActLog($sAppId, $sOpenId);
    }

    public static function bCreateOpLog($sAppId, $sOpenId, $iType)
    {
        $oApi = new MWechatApi();
        return $oApi->bCreateOpLog($sAppId, $sOpenId, $iType);
    }

    public static function bDeleteActLog($sAppId, $sOpenId)
    {
        $oApi = new MWechatApi();
        return $oApi->bDeleteActLog($sAppId, $sOpenId);
    }

    public static function bIsUser($sAppId, $sOpenId)
    {
        $oApi = new MWechatApi();
        $aOne = $oApi->aGetActLog($sAppId, $sOpenId);
        return !empty($aOne);
    }

    public static function bIsActiveUser($sAppId, $sOpenId)
    {
        $oApi = new MWechatApi();
        $aOne = $oApi->aGetActLog($sAppId, $sOpenId);
        if(!empty($aOne)
            && strtotime($aOne['updated_at']) + MFacade_Wechat_Const::ACT_EXPIRE_SECONDS > time())
        {
            return true;
        }
        return false;
    }
}