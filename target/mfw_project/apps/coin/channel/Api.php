<?php

namespace apps\coin\channel;

class MApi extends \Ko_Busi_Api
{
    public function iAddChannel($sTitle,$iDid,$iCType,$iStatus,$iTotalCoin,$iSingleMaxCoin,$sExpireDate = ''
        ,$sContent,$sReturnDesc,$iChId = null)
    {
        try
        {
            $sTitle = trim($sTitle);
            $iDid = intval($iDid);
            if (strlen($sTitle) == 0 || $iDid == 0 || !\apps\coin\channel\MFacade_Api::$aDepartment[$iDid])
            {
                return 0;
            }
            if ($this->bCheckChannelTitle($sTitle))
            {
                return 0;
            }
            if ($iChId === null)
            {
                $iChId = $this->iGenNewChannelId();
            }
            $iStatus = $iStatus ? 1 : 0;
            $sExpireDate = $sExpireDate ? date('Y-m-d',strtotime($sExpireDate)) : '0000-00-00';
            $aInsert = array(
                'ch_id' => $iChId,
                'did' => $iDid,
                'title' => $sTitle,
                'content' => $sContent,
                'c_type' => $iCType,
                'status' => $iStatus,
                'total_coin' => $iTotalCoin,
                'single_max_coin' => $iSingleMaxCoin,
                'expire_date' => $sExpireDate,
                'return_desc' => $sReturnDesc,
                'ctime' => date('Y-m-d H:i:s'),
                'create_admin_uid' => intval($_SESSION['admin']['id'])
            );
            $this->channelDao->aInsert($aInsert,$aInsert);
//            \apps\MFacade_Localcache_Api::vSetByQueue('mfw','coin_channels');
            return $iChId;
        }
        catch(\Exception $oEx)
        {
            return 0;
        }
    }

    public function bModifyChannel($iChId,$aUpdate,$aChange = array())
    {
        $bRet = $this->channelDao->iUpdate($iChId,$aUpdate,$aChange);
//        \apps\MFacade_Localcache_Api::vSetByQueue('mfw','coin_channels');
        return $bRet;
    }

    public function aGetChannel($iChId)
    {
        return $this->channelDao->aGet($iChId);
    }

    public function aGetAll()
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('1 = 1');
        return $this->channelDao->aGetList($oOption);
    }

    public function aGetChannels($iDid = 0,$iCType = 0,$iStatus = 0)
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('1 = 1');
        if ($iDid)
        {
            $oOption->oAnd('did = ?',$iDid);
        }
        if ($iCType)
        {
            $oOption->oAnd('c_type = ?',$iCType);
        }
        if ($iStatus)
        {
            $oOption->oAnd('status = ?',$iStatus);
        }
        return $this->channelDao->aGetList($oOption);
    }

    public function bCheckChannelTitle($sTitle)
    {
        $sTitle = trim($sTitle);
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('title = ?',$sTitle);
        $aInfos = $this->channelDao->aGetList($oOption);
        return $aInfos ? true : false;
    }

    public function iGenNewChannelId()
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oSelect('ch_id')->oOrderBy('order by ch_id desc');
        $aInfos = $this->channelDao->aGetList($oOption);
        $iMaxChId = intval($aInfos[0]['ch_id']);
        return $iMaxChId + 1;
    }
}
