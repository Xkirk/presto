<?php
/**
 * Created by PhpStorm.
 * User: lipei
 * Date: 16/3/3
 * Time: 11:41
 */
namespace apps\workorder\record;

/**
 * @property \Ko_Dao_Config $logDao
 */
class MLogApi extends \Ko_Busi_Api
{
    private static $_oLog;

    private static function oGetLog()
    {
        if (!is_object(self::$_oLog)) {
            self::$_oLog = new MLogApi();
        }
        return self::$_oLog;
    }


    public static function bAddLog($data, $type, $oldWorkOrder)
    {
        $log = array();
        $log['workorder_id'] = $data['workorder_id'];
        $log['log'] = self::sGetFormatedLog($data, $type, $oldWorkOrder);
        $log['ctime'] = date('Y-m-d H:i:s');
        $log['uid'] = \apps\user\MFacade_Api::iLoginUid();

        if (!empty($log['log'])) {
            self::oGetLog()->logDao->iInsert($log);
        }
    }

    public static function aGetLogListById($iWorkOrderId)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('workorder_id = ?', $iWorkOrderId);
        $workOrderList = self::oGetLog()->logDao->aGetList($option);

        foreach ($workOrderList as &$item) {
            $item['deal_name'] = \apps\workorder\MFacade_Api::getUserNameById($item['uid']);
        }
        return $workOrderList;
    }

    private static function sGetFormatedLog($data, $type, $oldWorkOrder)
    {
        $businessType = \apps\workorder\type\MFacade_Api::aGetBusinessTypeById($data['business_id']);
        $createName = \apps\workorder\MFacade_Api::getUserNameById(\apps\user\MFacade_Api::iLoginUid());
        switch ($type) {
            case MFacade_Api::LOG_TYPE_CREATEANDCLOSE:
                $log = self::sGetOrderInfo($data)
                    . "分类: <font color=\"#41c6ad\">" . $businessType['name'] . ' '
                    . \apps\workorder\type\MFacade_Api::sGetCategoryName($data['category_id']) . "</font><br>"
                    . " 状态: <font color=\"#41c6ad\">"
                    . \apps\workorder\MFacade_Api::$_aStatusConf[$data['status']] . "</font>"
                    . ($data['note'] ? ('<br>备注:' . $data['note']) : '')
                    . ('<br>' . $createName . ' 创建并直接关闭了工单');
                break;
            case MFacade_Api::LOG_TYPE_SAVE:
            case MFacade_Api::LOG_TYPE_TRANSFER:
            case MFacade_Api::LOG_TYPE_CLOSE:
            case MFacade_Api::LOG_TYPE_REOPEN:
                $log = self::sGetSaveLog($data, $oldWorkOrder);
                break;
            case MFacade_Api::LOG_TYPE_CREATE:
                $log = self::sGetOrderInfo($data)
                    . "分类: <font color=\"#41c6ad\">" . $businessType['name'] . ' '
                    . \apps\workorder\type\MFacade_Api::sGetCategoryName($data['category_id']) . "</font><br>"
                    . $createName . " 创建了工单<br>备注: " . $data['note'];
                break;
            default:
                $log = $data['note'];
                break;
        }
        return $log;
    }

    private static function sGetOrderInfo($data)
    {
        if ($data['data_info'] && $data['business_id']) {
            $phone = '';
            switch ($data['business_id']) {
                case 1:
                case 2:
                case 5:
                    $url = 'https://seller.mafengwo.cn/#/sales/order/' . $data['data_info'];
                    break;
                case 3:
                    $url = 'http://admin.mafengwo.cn/hotel/youyu/?order_id=' . $data['data_info'];
                    $param = array('order_id' => $data['data_info']);
                    $youyuOrder = \apps\MFacade_Hotel_Api::searchHotelOrder($param);
                    foreach ($youyuOrder['list'] as $one) {
                        $hotel = \apps\MFacade_Hotel_Api::aGetYouYuHotel($one['hotel_id']);
                        $phone = $hotel['phone'];
                    }
                    break;
                case 4:
                    $url = 'http://admin.mafengwo.cn/hotel/statistics/allota_order_list.php?'
                        . 'order_id=' . $data['data_info'];
                    $param = array('order_id' => $data['data_info']);
                    $directHotelOrder = \apps\MFacade_Hotel_Api::aGetDirectHotel($param);
                    foreach ($directHotelOrder as $one) {
                        $phone = \apps\MFacade_Hotel_Api::aGetPhoneByPoiId($one['poi_id']);
                    }
                    break;
                case 10:
                    $url = 'http://admin.mafengwo.cn/hotel/statistics/allota_order_list.php?'
                        . 'order_id=' . $data['data_info'];
                    $param = array('order_id' => $data['data_info']);
                    $aHotelData = \apps\MFacade_Hotel_Api::aGetAllOtaOrder($param);
                    foreach ($aHotelData as $one) {
                        $phone = \apps\MFacade_Hotel_Api::aGetPhoneByPoiId($one['poi_id']);
                    }
                    break;
                default:
                    $url = '';
                    break;
            }
            return "<a target=\"_blank\" href=\"" . $url . "\">订单号：" . $data['data_info'] . "</a></br>"
            . (!empty($phone) ? "酒店联系电话: " . $phone . "</br>" : "")
            . "</br>";
        } else {
            return '';
        }
    }

    private static function sGetSaveLog($data, $oldWorkOrder)
    {
        $log = '';
        if (isset($data['status'])) {
            if ($data['status'] != $oldWorkOrder['status']) {
                $log .= "状态由 <font color=\"#41c6ad\">"
                    . \apps\workorder\MFacade_Api::$_aStatusConf[$oldWorkOrder['status']]
                    . "</font> 变为: <font color=\"#41c6ad\">"
                    . \apps\workorder\MFacade_Api::$_aStatusConf[$data['status']] . "</font><br>";
            }
        }
        if (isset($data['category_id'])) {
            if ($data['category_id'] != $oldWorkOrder['category_id']) {
                $oldBusinessType=\apps\workorder\type\MFacade_Api::aGetBusinessTypeById($oldWorkOrder['business_id']);
                $businessType = \apps\workorder\type\MFacade_Api::aGetBusinessTypeById($data['business_id']);
                $log .= "分类由 <font color=\"#41c6ad\">" . $oldBusinessType['name'] . ' '
                    . \apps\workorder\type\MFacade_Api::sGetCategoryName($oldWorkOrder['category_id'])
                    . "</font> 变为: <font color=\"#41c6ad\">" . $businessType['name'] . ' '
                    . \apps\workorder\type\MFacade_Api::sGetCategoryName($data['category_id'])
                    . "</font><br>";
            }
        }
        if (isset($data['deal_uid'])) {
            if ($data['deal_uid'] != $oldWorkOrder['deal_uid']) {
                $log .= "处理人由 <font color=\"#41c6ad\">"
                    . \apps\workorder\MFacade_Api::getUserNameById($oldWorkOrder['deal_uid'])
                    . "</font> 变为: <font color=\"#41c6ad\">" .
                    \apps\workorder\MFacade_Api::getUserNameById($data['deal_uid']) . "</font><br>";
            }
        }
        if ($data['note']) {
            $log .= '备注: ' . $data['note'];
        }
        return $log;
    }
}