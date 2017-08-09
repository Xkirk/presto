<?php
namespace apps\workorder;
use apps\workorder\type\MCategoryApi;
use apps\workorder\wincall\MApi;

/**
 * @property \Ko_Dao_Config $infoDao
 */
class MInfoApi extends \Ko_Busi_Api
{
    const VCC_CODE = "8015040801";
    const SECRET = "e10adc3949ba59abbe56e057f20f883e";

    public static $_aStatusConf = array(
        1 => '新建',
        2 => '处理中',
        3 => '完成',
        4 => '关闭',
    );

    public function aGetComplaintList($startDate='', $endDate='')
    {
        $aComplaintList = array();
        if (strlen($startDate) <= 0) {
            $startDate = date("Y-m-d") . " 00:00:00";
        }
        if (strlen($endDate) <= 0) {
            $endDate = date("Y-m-d") . " 23:59:59";
        }
        $oCategoryApi = new MCategoryApi();
        $aComplaintCategoryIds = $this->aGetComplaintCategoryIds();

        //hardCode value in workorder_business_type for temporary
        $option = new \Ko_Tool_SQL();
        $option
            ->oWhere('(ctime>? and ctime<?) or (mtime>? and mtime<?)', $startDate, $endDate, $startDate, $endDate)
            ->oAnd('business_id in (1,2,5,6,27,30)');            //商城业务id
        $aWorkOrderList = $this->infoDao->aGetList($option);
        foreach ($aWorkOrderList as $aWorkOrder) {
            if (!in_array($aWorkOrder['category_id'], $aComplaintCategoryIds)) {
                continue;
            }
            $aCategory = $oCategoryApi->aGetCategoryById($aWorkOrder['category_id']);
            $aComplaint = array(
                'order_id' => $aWorkOrder['data_info'],
                'record_time' => $aWorkOrder['mtime'],
                'desc' => $aWorkOrder['note'],
                'type' => $aCategory['name'],
                'result' => self::$_aStatusConf[$aWorkOrder['status']],
                'result_type' => self::$_aStatusConf[$aWorkOrder['status']],
                'duty' => '',   //暂时没有责任方数据
            );
            $aComplaintList[] = $aComplaint;
        }

        return $aComplaintList;
    }

    public function aGetCallData($iQueNum, $startDate='', $endDate='')
    {
        $oWincallApi = new MApi();
        $oWincallApi->setAccount(self::VCC_CODE, self::SECRET);

        if (strlen($startDate) <= 0) {
            $startDate = date("Y-m-d") . " 00:00:00";
        }
        if (strlen($endDate) <= 0) {
            $endDate = date("Y-m-d") . " 23:59:59";
        }
        $iQueId = $oWincallApi->iGetQueIdByQueNum($iQueNum);
        $sQueueParams = 'info=' . json_encode(
                array("filter" => array(
                    "start_date" => date('Y-m-d', strtotime($startDate)),
                    "end_date" => date('Y-m-d', strtotime($endDate)),
                    "que_id" => strval($iQueId),
                ))
            );
        $aQueueCallData = json_decode($oWincallApi->aGetQueueService($sQueueParams), true);
        if (count($aQueueCallData['rows']) > 0) {
            return array(
                'que_num' => $iQueNum,
                'date' => $aQueueCallData['rows'][0]['date'],
                'in_num' => $aQueueCallData['rows'][0]['in_num'],
                'conn_num' => $aQueueCallData['rows'][0]['conn_num'],
            );
        } else {
            return array();
        }
    }

    private function aGetComplaintCategoryIds()
    {
        $aComplaintCategoryIds = array(18, 849);    //hardCode 投诉分类父id

        $oCategoryApi = new MCategoryApi();
        $aCateList18 = $oCategoryApi->aGetCategoryListByPid(18);
        $aCateList849 = $oCategoryApi->aGetCategoryListByPid(849);
        foreach ($aCateList18 as $aCategory) {
            $aComplaintCategoryIds[] = $aCategory['id'];
        }
        foreach ($aCateList849 as $aCategory) {
            $aComplaintCategoryIds[] = $aCategory['id'];
        }

        return $aComplaintCategoryIds;
    }
}