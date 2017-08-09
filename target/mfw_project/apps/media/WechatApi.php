<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2017/1/11
 * Time: 下午9:19
 */
namespace apps\media;

class MWechatApi extends \Ko_Busi_Api
{
    public function bUpdateActLog($sAppId, $sOpenId)
    {
        try
        {
            $this->wechatActLogDao->iInsert(array(
                'app_id' => $sAppId,
                'open_id' => $sOpenId,
                'updated_at' => date('Y-m-d H:i:s'),
            ), array(
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function bCreateOpLog($sAppId, $sOpenId, $iType)
    {
        try
        {
            $this->wechatOpLogDao->iInsert(array(
                'type_key' => $iType,
                'app_id' => $sAppId,
                'open_id' => $sOpenId,
            ));
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function bDeleteActLog($sAppId, $sOpenId)
    {
        try
        {
            $this->wechatActLogDao->iDelete(array(
                'app_id' => $sAppId,
                'open_id' => $sOpenId,
            ));
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function aGetActLog($sAppId, $sOpenId)
    {
        return $this->wechatActLogDao->aGet(array(
            'app_id' => $sAppId,
            'open_id' => $sOpenId,
        ));
    }
}