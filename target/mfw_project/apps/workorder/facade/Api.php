<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/9/16
 * Time: 16:53
 */

namespace apps\workorder;

//todo Add comments.
class MFacade_Api
{
    public static $category_type = array(
        '15' => '咨询',
        '18' => '投诉',
        '19' => '转接',
        '16' => '变更操作',
        '17' => '取消操作',
        '52' => '重发确认单操作',
    );

    public static $_aUidTypes = array(
        0 => '未知',
        1 => '普通用户',
        2 => '供应商',
        3 => '手机号',
    );

    public static $_aStatusConf = array(
        1 => '新建',
        2 => '处理中',
        3 => '完成',
        4 => '关闭',
    );

    public static $_aStatusStyleConf = array(
        1 => 'danger',
        2 => 'primary',
        3 => 'success',
        4 => 'warning',
    );

    public static $_aDataTypeConf = array(
        '0' => '无',
        '1' => '产品',
        '2' => '订单',
        '3' => '优惠券',
    );

    public static function getIdByUserName($userName)
    {
        $list = \apps\im\MFacade_busiApi::aGetAllActive();
        $uid = 0;
        array_walk($list, function ($v) use ($userName, &$uid) {
            if (!$uid && $v['b_name'] == $userName) {
                $uid = $v['b_uid'];
            }
        });

        return $uid;
    }

    public static function getUserNameById($id)
    {
        if ($name = self::aGetEmpleyeeById($id, 'name')) {
            return $name;
        } else {
            return self::aGetUserById($id, 'name');
        }
    }

    public static function aGetEmpleyeeById($uid, $key = '')
    {
        $createUser = \apps\im\MFacade_busiApi::aGetActiveById($uid);

        if (!empty($createUser)) {
            foreach ($createUser as $k => $v) {
                if (strpos($k, 'b_') === 0) {
                    $createUser[substr($k, 2)] = $v;
                }
            }

            return $key ? $createUser[$key] : $createUser;
        }

        return false;
    }

    public static function aGetUserById($uid, $key = '')
    {
        $user = \apps\user\info\MFacade_Api::aGetById($uid);
        if (!empty($user)) {
            \Ko_Tool_Str::VConvert2UTF8($user);
            return $key ? $user[$key] : $user;
        }

        return false;
    }

    public static function setUserName(&$workOrder)
    {
        $workOrder['create_name'] = self::aGetEmpleyeeById($workOrder['create_uid'], 'name');
        if (!$workOrder['create_name']) {
            $workOrder['create_name'] = self::aGetUserById($workOrder['create_uid'], 'name');
        }

        $workOrder['deal_name'] = self::aGetEmpleyeeById($workOrder['deal_uid'], 'name');
        if (!$workOrder['deal_name']) {
            $workOrder['deal_name'] = self::aGetUserById($workOrder['deal_uid'], 'name');
        }
    }

    public static function iAddWorkOrder($data)
    {
        return \apps\workorder\MApi::iAddWorkOrder($data);
    }

    public static function aGetWorkOrderByid($iWorkOrderId)
    {
        return \apps\workorder\MApi::aGetWorkOrderByid($iWorkOrderId);
    }

    public static function bUpdateWorkOrder($id, $data)
    {
        return \apps\workorder\MApi::bUpdateWorkOrder($id, $data);
    }

    public static function bDelWorkOrder($id)
    {
        return \apps\workorder\MApi::bDelWorkOrder($id);
    }

    public static function bUpdateWorkOrderV2($id, $data)
    {
        return \apps\workorder\MApi::bUpdateWorkOrderV2($id, $data);
    }

    public static function bAddAttachment($iWorkOrderId, $uid, $data)
    {
        return \apps\workorder\MApi::bAddAttachment($iWorkOrderId, $uid, $data);
    }

    public static function aGetAttachmentListById($iWorkOrderId)
    {
        return \apps\workorder\MApi::aGetAttachmentListById($iWorkOrderId);
    }

    public static function bDelAttachment($iAttachmentId)
    {
        return \apps\workorder\MApi::bDelAttachment($iAttachmentId);
    }

    public static function bAddRecord($iWorkorderId, $type, $data)
    {
        return \apps\workorder\MApi::bAddRecord($iWorkorderId, $type, $data);
    }

    public static function bUpdateRecord($iRecordId, $data)
    {
        return \apps\workorder\MApi::bUpdateRecord($iRecordId, $data);
    }

    public static function aGetWorkOrderList($option)
    {
        return \apps\workorder\MApi::aGetWorkOrderList($option);
    }

    public static function aGetWorkOrderAllList($option)
    {
        return \apps\workorder\MApi::aGetWorkOrderAllList($option);
    }

    public static function aGetWorkOrderListByData($sDataInfo, $iDataType = 2)
    {
        return \apps\workorder\MApi::aGetWorkOrderListByData($sDataInfo, $iDataType);
    }

    public static function aGetAttachments($iWorkOrderId)
    {
        return \apps\workorder\MApi::aGetAttachments($iWorkOrderId);
    }

    public static function aGetRecord($iWorkOrderId, $type = 0)
    {
        return \apps\workorder\MApi::aGetRecord($iWorkOrderId, $type);
    }

    public static function bAddNote($iWorkOrderId, $note)
    {
        return \apps\workorder\MApi::bAddNote($iWorkOrderId, $note);
    }

    public static function bUpdateNote($id, $note)
    {
        return \apps\workorder\MApi::bUpdateNote($id, $note);
    }

    public static function aGetComplaintList($startDate='', $endDate='')
    {
        $oApi = new MInfoApi();
        return $oApi->aGetComplaintList($startDate, $endDate);
    }

    public static function aGetCallData($iQueNum, $startDate='', $endDate='')
    {
        $oApi = new MInfoApi();
        return $oApi->aGetCallData($iQueNum, $startDate, $endDate);
    }
}