<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 5/9/16
 * Time: 17:03
 */

namespace apps\workorder;

require_once(COMMON_3RD_PATH . 'mailer/PHPMailer.php');

/**
 * @property \Ko_Dao_Config infoDao
 */
class MApi extends \Ko_Busi_Api
{
    private static $_oInfo;

    private static function oGetInfo()
    {
        if (!is_object(self::$_oInfo)) {
            self::$_oInfo = new self();
        }

        return self::$_oInfo;
    }


    /**
     * 添加工单
     * @param array $data
     * ex:$data = array(title,deal_uid,note,create_uid)
     * return id 工单id
     * @return int|void
     */
    public static function iAddWorkOrder($data)
    {
        if (isset($data['uid_type']) && !isset(MFacade_Api::$_aUidTypes[$data['uid_type']])) {
            $data['uid_type'] = 0;
        }
        if (isset($data['uid'])) {
            $data['uid'] = intval($data['uid']);
        }
        $data['ctime'] = date('Y-m-d H:i:s', time());
        if (!isset($data['status']) || !isset(MFacade_Api::$_aStatusConf[$data['status']])) {
            $data['status'] = 1;
        }

        $attr = array(
            'category' => MFacade_Api::$category_type[$data['category_id']],
            'deal_uid' => $data['deal_uid'],
            'from_type' => $data['from_type'],
        );
        \apps\MFacade_Log_Api::serverEvent(\apps\MFacade_Log_Api::SAPP_SALES, 'workorder_create', $attr);

        $id = self::oGetInfo()->infoDao->iInsert($data);

        if ($data['deal_uid']) {
            $sCreateName = MFacade_Api::getUserNameById($data['create_uid']);
            $aNoticeData = array(
                'title' => $sCreateName . '创建了一个工单',
                'detail' => $sCreateName . '创建的工单处理人是您:' . MFacade_Api::getUserNameById($data['deal_uid']),
                'ctime' => $data['ctime']
            );
            self::_vNotice($id, $aNoticeData);
        }

        return $id;
    }

    /**
     * 获取工单详情
     * @param $iWorkOrderId
     * @return array
     */
    public static function aGetWorkOrderByid($iWorkOrderId)
    {
        $workOrder = self::oGetInfo()->infoDao->aGet($iWorkOrderId);
        $workOrder['status_name'] = MFacade_Api::$_aStatusConf[$workOrder['status']];
        MFacade_Api::setUserName($workOrder);
        return $workOrder;
    }


    /**
     * 修改工单信息（给IM用）
     * @param $id
     * @param $data
     * @return int|void
     */
    public static function bUpdateWorkOrder($id, $data)
    {
        $aOldData = self::aGetWorkOrderByid($id);

        if (isset($data['status'])) {
            if ($data['status'] == 2 && $aOldData['status'] == 1) {
                if (!intval($aOldData['start_time'])) $data['start_time'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] > 2 && $aOldData['status'] == 2) {
                $data['end_time'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] == 4) {
                $data['close_time'] = date('Y-m-d H:i:s');
            }
        }
        $attr = array(
            'category' => MFacade_Api::$category_type[$aOldData['category_id']],
            'workorder_id' => $id,
            'oldstatus' => $aOldData['status'],
            'newstatus' => $data['status'],

        );

        \apps\MFacade_Log_Api::serverEvent(\apps\MFacade_Log_Api::SAPP_SALES, 'workorder_update', $attr);

        $r = self::oGetInfo()->infoDao->iUpdate($id, $data);
        if ($r) {
            self::_vRecordUpdate($aOldData, $data);
        }

        return $r;
    }

    /**
     * 修改工单信息（给客服系统用）
     * @param $id
     * @param $data
     * @return int|void
     */
    public static function bUpdateWorkOrderV2($id, $data)
    {
        $aOldData = self::aGetWorkOrderByid($id);

        if (isset($data['status'])) {
            if ($data['status'] == 2 && $aOldData['status'] == 1) {
                if (!intval($aOldData['start_time'])) $data['start_time'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] > 2 && $aOldData['status'] == 2) {
                $data['end_time'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] == 4) {
                $data['close_time'] = date('Y-m-d H:i:s');
            }
        }
        $attr = array(
            'category' => MFacade_Api::$category_type[$aOldData['category_id']],
            'workorder_id' => $id,
            'oldstatus' => $aOldData['status'],
            'newstatus' => $data['status'],

        );

        \apps\MFacade_Log_Api::serverEvent(\apps\MFacade_Log_Api::SAPP_SALES, 'workorder_update', $attr);
        $r = self::oGetInfo()->infoDao->iUpdate($id, $data);
        if ($r) {
            self::_vRecordUpdate($aOldData, $data);
        }
        return $r;
    }

    /**
     * 删除单条工单
     * @param $id
     * @return int|void
     */
    public static function bDelWorkOrder($id)
    {
        return self::oGetInfo()->infoDao->iDelete($id);
    }

    private static function _vRecordUpdate($aOldData, $aUpdateData)
    {
        unset($aUpdateData['id']);

        if (isset($aUpdateData['status'])) {
            if ($aUpdateData['status'] != $aOldData['status']) {
                self::bAddRecord($aOldData['id'], \apps\workorder\record\MFacade_Api::RECORD_TYPE_UPDATE_STATUS,
                    self::_aGetRecordStyle($aOldData['status'], $aUpdateData['status']));
            }
            unset($aUpdateData['status']);
        }

        if (isset($aUpdateData['deal_uid'])) {
            if ($aUpdateData['deal_uid'] != $aOldData['deal_uid']) {
                self::bAddRecord($aOldData['id'], \apps\workorder\record\MFacade_Api::RECORD_TYPE_UPDATE_DEAL,
                    self::_aGetRecordStyle(\apps\user\MFacade_Api::iLoginUid(), $aUpdateData['deal_uid']));
            }
            unset($aUpdateData['deal_uid']);
        }

        $aFields = array('ctime', 'mtime', 'start_time', 'end_time', 'close_time');
        foreach ($aFields as $f) {
            unset($aUpdateData[$f]);
        }

        if (!empty($aUpdateData)) {
            $arr = array();
            foreach ($aUpdateData as $key => $val) {
                if ($key == 'note') {
                    $ratherVal = str_replace(array("\r", "\n"), "", $val);
                    $ratherOldVal = str_replace(array("\r", "\n"), "", $aOldData[$key]);
                } else {
                    $ratherVal = $val;
                    $ratherOldVal = $aOldData[$key];
                }

                if ($ratherVal != $ratherOldVal) {
                    $arr[$key] = $aOldData[$key];
                } else {
                    unset($aUpdateData[$key]);
                }
            }

            if (!empty($arr)) {
                self::bAddRecord($aOldData['id'], \apps\workorder\record\MFacade_Api::RECORD_TYPE_UPDATE_INFO,
                    self::_aGetRecordStyle($arr, $aUpdateData));
            }
        }
    }

    private static function _aGetRecordStyle($aOldData, $aUpdateData)
    {
        return array('old' => $aOldData, 'update' => $aUpdateData);
    }

    /**
     * 存储工单相关附件
     * @param int $iWorkOrderId 工单id
     * @param $uid
     * @param $data
     * @return int|void
     */
    public static function bAddAttachment($iWorkOrderId, $uid, $data)
    {
        $data['workorder_id'] = $iWorkOrderId;
        $data['uid'] = $uid;
        $data['ctime'] = date('Y-m-d H:i:s', time());
        return \apps\workorder\attachment\MFacade_Api::bAddAttachment($data);
    }

    /**
     * 根据工单号查询和工单相关的附件
     * @param $iWorkOrderId
     * @return array
     */
    public static function aGetAttachmentListById($iWorkOrderId)
    {
        return \apps\workorder\attachment\MFacade_Api::aGetAttachmentListById($iWorkOrderId);
    }

    /**
     * 删除附件
     * @param $iAttachmentId
     * @return int|void
     */
    public static function bDelAttachment($iAttachmentId)
    {
        return \apps\workorder\attachment\MFacade_Api::bDelAttachment($iAttachmentId);
    }


    /**
     * @param $iWorkorderId
     * @param $type
     * @param $data
     * @return int|void
     */
    public static function bAddRecord($iWorkorderId, $type, $data)
    {
        $record = array();
        $record['type'] = $type;
        $record['note'] = is_array($data) ? json_encode($data) : $data;
        $record['workorder_id'] = $iWorkorderId;
        $record['ctime'] = date('Y-m-d H:i:s');
        $record['uid'] = \apps\user\MFacade_Api::iLoginUid();

        if ($type < \apps\workorder\record\MFacade_Api::RECORD_TYPE_ADD_NOTE) {
            $data = \apps\workorder\record\MFacade_Api::aGetNoteFormatData(
                $record['type'], $record['note'], $record['uid']);
            $data['ctime'] = $record['ctime'];

            self::_vNotice($iWorkorderId, $data);
        }

        return \apps\workorder\record\MFacade_Api::bAddRecord($record);
    }

    /**
     * 修改工单操作记录
     * @param $iRecordId
     * @param $data
     * @return int|void
     * @internal param $note
     */
    public static function bUpdateRecord($iRecordId, $data)
    {
        return \apps\workorder\record\MFacade_Api::bUpdateRecord($iRecordId, $data);
    }


    /**
     * 获取工单列表
     * @param $option
     * @return array
     */
    public static function aGetWorkOrderList($option)
    {
        $orderList = self::oGetInfo()->infoDao->aGetList($option);
        if ($orderList) {
            if (isset($orderList['total'])) {
                $list = & $orderList['list'];
            } else {
                $list = & $orderList;
            }

            foreach ($list as &$workOrder) {
                MFacade_Api::setUserName($workOrder);
                $workOrder['status_name'] = MFacade_Api::$_aStatusConf[$workOrder['status']];
                $workOrder['status_style'] = MFacade_Api::$_aStatusStyleConf[$workOrder['status']];
            }
        }

        return $orderList;
    }

    /**
     * 获取全部工单列表
     * @return array
     */
    public static function aGetWorkOrderAllList()
    {
        $option = new \Ko_Tool_SQL();
        $option->oSelect('*');
        $orderList = self::oGetInfo()->infoDao->aGetList($option);
        if ($orderList) {
            if (isset($orderList['total'])) {
                $list = & $orderList['list'];
            } else {
                $list = & $orderList;
            }
        }

        return $orderList;
    }


    /**
     * 通过data type数据获取相关工单
     * @param $sDataInfo
     * @param int $iDataType
     * @return array
     */
    public static function aGetWorkOrderListByData($sDataInfo, $iDataType = 2)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('data_type = ? and data_info = ?', $iDataType, $sDataInfo);

        return self::aGetWorkOrderList($option);
    }


    /**
     * 获取工单附件
     * @param $iWorkOrderId
     * @return array
     */
    public static function aGetAttachments($iWorkOrderId)
    {
        $list = self::aGetAttachmentListById($iWorkOrderId);

        if ($list) {
            $img_style = new \apps\MFacade_File_Image_Style();
            $img_style->vResize(100, 100, \apps\MFacade_File_Image_Style::RESIZE_ZOOM_LTE);

            $aIimgExts = array('bmp', 'jpg', 'jpeg', 'png', 'gif');

            array_walk($list, function (&$v) use ($img_style, $aIimgExts) {
                $v['url'] = \apps\MFacade_File_Url::SBuild($v['file_path']);

                if (strpos($v['file_type'], 'image') !== false || in_array($v['file_type'], $aIimgExts)) {
                    $v['url_thumb'] = \apps\MFacade_File_Url::SBuild($v['file_path'], $img_style);
                } else {
                    $v['url_thumb'] = 'attachment';
                }
            });
        }

        return $list;
    }

    /**
     * 获取工单处理记录
     * @param int $iWorkOrderId
     * @param int $type
     * @return array
     */
    public static function aGetRecord($iWorkOrderId, $type = 0)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('workorder_id = ? and type = ?', $iWorkOrderId, $type);
        $records = \apps\workorder\record\MFacade_Api::aGetRecordByOption($option);
        foreach ($records as &$record) {
            $arr = \apps\workorder\record\MFacade_Api::aGetNoteFormatData(
                $record['type'], $record['note'], $record['uid']);
            $record = array_merge($record, $arr);
        }
        return $records;
    }


    /**
     * 添加备注
     * @param $iWorkOrderId
     * @param $note
     * @return bool|int|void
     * @internal param $uid
     */
    public static function bAddNote($iWorkOrderId, $note)
    {
        if (!$iWorkOrderId || !$note) return false;
        return self::bAddRecord($iWorkOrderId, \apps\workorder\record\MFacade_Api::RECORD_TYPE_ADD_NOTE, $note);
    }

    /**
     * 添加备注
     * @param $id
     * @param $note
     * @return bool|int|void
     * @internal param $iWorkOrderId
     * @internal param $uid
     */
    public static function bUpdateNote($id, $note)
    {
        if (!$id || !$note) return false;
        return self::bUpdateRecord($id, array('note' => $note));
    }

    private static function _vNotice($iWorkOrderId, $data)
    {

        $info = self::aGetWorkOrderByid($iWorkOrderId);
        $title = "[蚂蜂窝工单-{$iWorkOrderId}]" . $info['title'];
        $url = "http://im.mafengwo.cn/workorder/view?id={$info['id']}";

        $body = <<<EOF

<table width="680" border="0" cellspacing="0" cellpadding="0" align="center"
style="font-family:Microsoft Yahei;font-size:12px; color:#555;background-color:#fff; margin:0 auto;">
    <tr style="background-color:#ffb000; color:#fff;">
    <td style="width:205px; height:65px; padding:15px 0 15px 30px;">
    <img src="http://images.mafengwo.net/images/seo/email/order/logo1.png"
    width="167" height="34" style="border:0; vertical-align:middle;"></td>
    <td style="font-size:36px; height:65px; line-height:65px; padding:15px 0;">蚂蜂窝工单提醒邮件</td></tr>
</table>

<table style="width:620px; margin:0 auto;
background-color:#fffaef; border:2px solid #ffb50f;font-size: 12px"border="0"
 cellspacing="0" cellpadding="0" align="center" >

  <tr>
                    <td style=" padding:20px 50px 0;">
                        <p style="color:#515252; font-size:16px; padding:20px 0; margin:0;">{$title}</p>
                        <dl style="line-height:24px; border-bottom:1px solid #ffdb8c; padding:15px 0; margin:0;">

                            <dt style=" float:left; width:75px;">操作事项：</dt><dd>{$data['title']}</dd>
                            <dt style=" float:left; width:75px;">操作时间：</dt><dd>{$data['ctime']}</dd>
                            <dt style=" float:left; width:75px;">操作详细：</dt><dd>{$data['detail']}</dd>
                            <dt style=" float:left; width:75px;">访问链接：</dt>
                            <dd style="color:#eb7b04; font-size:14px;">
                                <a href="{$url}" target="_blank"
                                 style="color:#eb7b04;text-decoration: none">{$url}</a>
                            </dd>
                        </dl>
                    </td>
                </tr>
 <tr><td style="height:50px;line-height:50px;
 background-color:#ffb100; text-align:center; color:#fff;
  font-size:18px;">此邮件由系统自动发出，请勿回复</td></tr>
</table>
EOF;


        $recipients = array();
        //取邮件信息
        $iLoginUid = \apps\user\MFacade_Api::iLoginUid();
        if ($info['create_uid'] && $info['create_uid'] != $iLoginUid) {
            if ($email = MFacade_Api::aGetEmpleyeeById($info['create_uid'], 'email')) {
                $recipients[] = $email;
            }
        }

        if ($info['deal_uid'] && $info['deal_uid'] != $iLoginUid) {
            if ($email = MFacade_Api::aGetEmpleyeeById($info['deal_uid'], 'email')) {
                $recipients[] = $email;
            }
        }

        self::_vSendMail($title, $body, $recipients);

    }

    private static function _vSendMail($title, $body, $recipients)
    {
        $mail = new \PHPMailer();

        $title = \Ko_Tool_Str::SConvert2GB18030($title);
        $body = \Ko_Tool_Str::SConvert2GB18030($body);
        $mail->Host = 'smtp.mafengwo.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'business@mafengwo.com';
        $mail->Password = '0I08QSro2M';
        $mail->From = 'business@mafengwo.com';
        $mail->FromName = \Ko_Tool_Str::SConvert2GB18030('蚂蜂窝自由行');
        $mail->CharSet = 'GB2312';
        $mail->Body = $body;
        $mail->Subject = "=?GB2312?B?" . base64_encode($title) . "?=";
        $mail->Timeout = 30;
        $mail->IsSMTP();
        $mail->IsHTML(true);

        foreach ($recipients as $address) {
            $mail->AddAddress($address);
        }

        $mail->Send();
    }
}