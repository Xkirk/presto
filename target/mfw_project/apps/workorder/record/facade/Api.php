<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/10/16
 * Time: 16:50
 */

namespace apps\workorder\record;

class MFacade_Api
{
    const LOG_TYPE_CREATE = 1; // 新建工单
    const LOG_TYPE_CREATEANDCLOSE = 2; // 新建工单并直接关单
    const LOG_TYPE_SAVE = 3; // 修改工单
    const LOG_TYPE_CLOSE = 4; // 关闭工单
    const LOG_TYPE_TRANSFER = 5; // 转接处理人
    const LOG_TYPE_REOPEN = 6; // 重开


    const RECORD_TYPE_UPDATE_INFO = 1;
    const RECORD_TYPE_UPDATE_DEAL = 2;
    const RECORD_TYPE_CREATE = 3;
    const RECORD_TYPE_UPDATE_STATUS = 4;
    const RECORD_TYPE_ADD_NOTE = 101;


    public static function bAddLog($data, $type, $oldWorkOrder)
    {
        \apps\workorder\record\MLogApi::bAddLog($data, $type, $oldWorkOrder);
    }

    public static function aGetLogListById($iWorkOrderId)
    {
        return \apps\workorder\record\MLogApi::aGetLogListById($iWorkOrderId);
    }




    public static function aGetRecordByOption($option)
    {
        return \apps\workorder\record\MRecordApi::oGetRecord()->aGetListByOption($option);
    }

    public static function aGetNoteFormatData($type, $note, $uid)
    {
        return \apps\workorder\record\MRecordApi::oGetRecord()->aGetNoteFormatData($type, $note, $uid);
    }

    public static function bAddRecord($record)
    {
        return \apps\workorder\record\MRecordApi::oGetRecord()->recordDao->iInsert($record);
    }

    public static function bUpdateRecord($id, $data)
    {
        return \apps\workorder\record\MRecordApi::bUpdateRecord($id, $data);
    }

    public function sGetChanges($workorderId, $startDate, $endDate)
    {
        return \apps\workorder\record\MRecordApi::oGetRecord()->sGetChanges($workorderId, $startDate, $endDate);
    }

}