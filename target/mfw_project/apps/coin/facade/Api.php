<?php

namespace apps\coin;

class MFacade_Api
{
    const CTYPE_SEND = 1;
    const CTYPE_RECYCLE = 2;
    const CTYPE_ROLLOUT = 3;
    const CTYPE_ROLLIN = 4;

    public static $_aCTypes = array(
        self::CTYPE_SEND => '发放',
        self::CTYPE_RECYCLE => '回收',
        self::CTYPE_ROLLOUT => '转账转出',
        self::CTYPE_ROLLIN => '转账转入',
    );

    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 2;

    public static function bPerChangeCoin($iUid, $iChId, $iCoin)
    {
        $iCoin = intval($iCoin);
        if (!self::bCheckChannel($iChId, $iCoin)) {
            return false;
        }
        $aEx = \apps\user\ex\MFacade_Api::aGetEx($iUid);
        $iOldCoin = intval($aEx['coin']);
        if ($iCoin < 0 && $iOldCoin + $iCoin < 0) {
            return false;
        }
        return true;
    }

    public static function bChangeCoin($iUid, $iChId, $iCoin, $iReferId = 0)
    {
        if (!self::bPerChangeCoin($iUid, $iChId, $iCoin)) {
            return false;
        }
        $bRet = \apps\user\ex\MFacade_Api::bSetEx($iUid, array(), array(
            'coin' => $iCoin,
        ));
        if ($bRet) {
            $aUpdate = array('last_send_time' => date('Y-m-d H:i:s'));
            $oChannelApi = new \apps\coin\channel\MApi();
            $oChannelApi->bModifyChannel($iChId, $aUpdate);

            $oInstance = new MApi();
            $oInstance->vAddCoinRecord(array(
                'uid' => $iUid,
                'type' => $iChId,
                'value' => $iCoin,
                'refer_id' => $iReferId,
                'time' => date("Y-m-d H:i:s"),
            ));
        }
        return $bRet;
    }

    public static function aGetRecordGroupByTypes($aTypes,$sStime = '',$sEtime = '')
    {
        $oInstance = new MApi();
        return $oInstance->aGetRecordGroupByTypes($aTypes,$sStime,$sEtime);
    }

    public static function aGetRecordByUidType($iUid,$iType,$iLimit = 2000)
    {
        $oInstance = new MApi();
        return $oInstance->aGetRecordByUidType($iUid,$iType,$iLimit);
    }

    private static function bCheckChannel($iChId,$iCoin)
    {
        // 判断发放渠道是否存在

        $aChannel = \apps\coin\channel\MFacade_Api::aGetChannel($iChId);
        if (!$aChannel)
        {
            \apps\MFacade_Log_Api::webdlog('Coin:Error', $iChId.':'.$iCoin, 'NoExists');
            return false;
        }


        // 渠道是否已经关闭
        if ($aChannel['status'] == self::STATUS_CLOSE) {
            \apps\MFacade_Log_Api::webdlog('Coin:Error', $iChId . ':' . $iCoin, 'Closed');

            // log
            $oInstance = new MApi();
            $oInstance->setCloseLog($iChId);

            return false;
        }
        // 渠道发放是否超过总量上线
        if ($aChannel['total_coin']) {
            $iSendedHoney = $aChannel['sended_coin'] + $aChannel['return_coin'];
            if ($iSendedHoney + $iCoin > $aChannel['total_coin']) {
                \apps\MFacade_Log_Api::webdlog('Coin:Error', $iChId . ':' . $iCoin, 'Exceed');
                return false;
            }
        }
        // 渠道发放是否超过单次发放限制
        if ($aChannel['single_max_coin']) {
            if ($iCoin > $aChannel['single_max_coin']) {
                \apps\MFacade_Log_Api::webdlog('Coin:Error', $iChId . ':' . $iCoin, 'SingleExceed');
                return false;
            }
        }
        // 渠道发放是否到期
        if ($aChannel['expire_date'] != '0000-00-00' && $aChannel['c_type'] == self::CTYPE_SEND) {
            if (strtotime($aChannel['expire_date']) < time()) {
                \apps\MFacade_Log_Api::webdlog('Coin:Error', $iChId . ':' . $iCoin, 'Expire');
                return false;
            }
        }
        return true;
    }

//    /**
//     * 判断是否是金币回退行为
//     * @param $iType
//     * @param $iCoin
//     * @return bool
//     */
//    private static function bIsReturn($iCType,$iCoin)
//    {
//        if ($iCType == self::CTYPE_SEND && $iCoin < 0) {
//            return true;
//        }
//        else if ($iCType == self::CTYPE_RECYCLE && $iCoin > 0) {
//            return true;
//        }
//        return false;
//    }

    public static function getRecordByUid($uid, $offset, $limit)
    {
        $MApi = new MApi();

        return $MApi->getRecordByUid($uid, $offset, $limit);
    }

    public static function aGetUserByTimeAndType($iUid,$iType,$sSTime,$sETime)
    {
        $oMapi = new MApi();
        return $oMapi->aGetUserByTimeAndType($iUid,$iType,$sSTime,$sETime);
    }

    public static function getCloseLog(){
        $MApi = new MApi();

        return $MApi->getCloseLog();
    }

    public static function delCloseLog($iChId){
        $MApi = new MApi();

        return $MApi->delCloseLog($iChId);
    }
}