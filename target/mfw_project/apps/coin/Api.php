<?php

namespace apps\coin;

class MApi extends \Ko_Busi_Api
{
    public function vAddCoinRecord($aParams)
    {
        try
        {
            return $this->recordDao->iInsert($aParams);
        }
        catch(Exception $oEx)
        {
            return false;
        }
    }

    public function aGetRecordGroupByTypes($aTypes,$sStime = '',$sEtime = '')
    {
        if (empty($aTypes) && !$sStime && !$sEtime)
        {
            return array();
        }
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('1 = 1');
        $oOption->oSelect('sum(value) as num,type');

        if ($aTypes)
        {
            $oOption->oAnd("type IN (?)",$aTypes);
        }
        if ($sStime)
        {
            $oOption->oAnd('time >= ?',$sStime);
        }
        if ($sEtime)
        {
            $oOption->oAnd('time < ?',$sEtime);
        }
        $oOption->oGroupBy('group by type');
        $oOption->oForceInactive(true);
        $aInfos = $this->recordDao->aGetList($oOption);
        return \Ko_Tool_Utils::AObjs2map($aInfos,'type','num');
    }

    public function aGetRecordByUidType($iUid,$iType,$iLimit = 2000)
    {
        $iUid = intval($iUid);
        $iType = intval($iType);
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('type = ?',$iType);
        if ($iUid)
        {
            $oOption->oAnd('uid = ?',$iUid);
        }
        $oOption->oLimit($iLimit)->oOrderBy("order by a_id desc");
        return $this->recordDao->aGetList($oOption);
    }

    public function aGetUserByTimeAndType($iUid,$iType,$sSTime,$sETime)
    {
        if ($iUid) {
            $oOption = new \Ko_Tool_SQL();
            $oOption->oWhere('uid = ? and type = ? and time >= ? && time <= ?', $iUid, $iType, $sSTime, $sETime);
            return $this->recordDao->aGetList($oOption);
        }
        return array();
    }

    public function getRecordByUid($iUid, $iOffset, $iLimit)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('uid=?', $iUid);
        $option->oOrderBy('order by time desc');
        $option->oOffset($iOffset);
        $option->oLimit($iLimit);
        $option->oCalcFoundRows(true);
        $list = $this->recordDao->aGetList($option);
        $count = $option->iGetFoundRows();

        return array($count, $list);
    }

    public function setCloseLog($iChId){
        $key = 'channels_close_'.$iChId;
        $iLog = $this->redisDao->vHGet('coin',$key);
        if(!$iLog){
            $this->redisDao->vHSet('coin',$key,$iChId);
        }
    }

    public function getCloseLog(){
        return $this->redisDao->vHGetALL('coin');
    }

    public function delCloseLog($iChId){
        $key = 'channels_close_'.$iChId;
        return $this->redisDao->vHDel('coin',$key);
    }
}
