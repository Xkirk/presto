<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2017/6/14
 * Time: 上午11:38
 */
namespace apps\pay_core\esign;
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/3/10
 * Time: 下午4:20
 */
class MModel_ESignLogApi extends \Ko_Busi_Api
{
    /**
     * @param $value
     * @return mixed
     */
    public function iAddRequestLog($aData)
    {
        $aData['uid']= \apps\user\MFacade_Api::iLoginUid();
        $aData['ip'] = \Ko_Tool_Ip::SGetClientIP();
        return $this->reqLogDao->iInsert($aData, array(), array(), null);
    }
    public function aGetListReqLog($sBeginDate,$sEndDate)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('add_time >=?',$sBeginDate.' 00:00:00');
        $option->oAnd('add_time <=?',$sEndDate.' 23:59:59');
        return $this->reqLogDao->aGetList($option);
    }
}