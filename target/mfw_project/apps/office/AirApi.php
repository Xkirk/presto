<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2016/12/19
 * Time: 下午1:54
 */
namespace apps\office;

class MAirApi extends \Ko_Busi_Api
{
    public function bSetData($aData, $sDeviceId = '')
    {
        try
        {
            $this->airDataDao->iInsert(array(
                'device_id' => $sDeviceId,
                'exinfo' => \Ko_Tool_Enc_Serialize::SEncode($aData),
            ), array(
                'exinfo' => \Ko_Tool_Enc_Serialize::SEncode($aData),
            ));
            $this->_bCreateLog($aData, $sDeviceId);
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function aGetData($sDeviceId = '')
    {
        $aOne = $this->airDataDao->aGet($sDeviceId);
        if(!empty($aOne))
        {
            $aOne['data'] = \Ko_Tool_Enc_Serialize::ADecode($aOne['exinfo']);
            unset($aOne['exinfo']);
        }
        return $aOne;
    }

    private function _bCreateLog($aData, $sDeviceId = '')
    {
        try
        {
            $this->airLogDao->iInsert(array(
                'device_id' => $sDeviceId,
                'exinfo' => \Ko_Tool_Enc_Serialize::SEncode($aData),
            ));
            return true;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }
}