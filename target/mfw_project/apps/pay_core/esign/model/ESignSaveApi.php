<?php
namespace apps\pay_core\esign;
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/3/10
 * Time: 下午4:20
 */
class MModel_ESignSaveApi extends \Ko_Busi_Api
{
    const  SAVEDONE = 1;//文档已保存
    const  NOTSETTLE = 0;
    /**插入文档保全信息
     * @param $aData
     * @return mixed
     */
    public function iAddSaveInfo($aData) {
        return  $this->saveDao->iInsert($aData,array(),array(),null);
    }

    /**订单结算
     * @param $order_id
     * @return mixed
     */
    public function aGetSaveByOrderId($order_id)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('order_id=?', $order_id);
        $option->oAnd('status=?', self::SAVEDONE);
        $option->oAnd('is_settle=?', self::NOTSETTLE);
        return $this->saveDao->aGetList($option);
    }
    public function iUpdateSignInfoByOrderId($order_id)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('order_id=?', $order_id);
        $option->oAnd('status=?', self::SAVEDONE);
        $updateData =array('is_settle'=>1);
        return $this->saveDao->iUpdateByCond($option,$updateData);
    }

    /**
     * @param $filename
     * @return mixed
     */
    public function aGetSaveByPath($filename)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('filename=?', $filename);
        $option->oAnd('status=?', self::SAVEDONE);
        return $this->saveDao->aGetList($option);
    }

    public function aGetSaveByOtaId($begin, $end, $otaId)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('ota_id=?', $otaId);
        $option->oAnd('ctime >= ?', $begin);
        $option->oAnd('ctime <= ?', $end);
        $option->oAnd('length(filename) !=?', 0);
        return $this->saveDao->aGetList($option);
    }


}