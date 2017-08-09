<?php
/**
 * Created by PhpStorm.
 * Date: 2017/5/25
 * Time: 下午6:19
 */

namespace apps\pay_core\esign;

/**
 * Class MFacade_SignApi
 * @method  static MControl_SignApi Control_SignApi
 * @package apps\pay_core\esign
 */
class MFacade_SignApi extends  Mlib_BaseApi
{
    /**获取上传url，并上传
     * @param $signArray
     * @param $path
     */
    public static function aSaveFile($signArray, $path)
    {
        return self::Control_SignApi()->aSaveFile($signArray, $path);
    }


    /**订单合同
     * @param $personId
     * @param $companyId
     * @param $pdfPath
     * @param $signPos
     * @param string $signType
     */
    public static function aSignOrder($personId,$sOrgInfo,$pdfPath,$signPos,
                                      $orderInfo,$aOrgNoAuthInfo=array(),$signType='Single')
    {
     return self::Control_SignApi()->aSignOrder($personId,$sOrgInfo,$pdfPath,$signPos,$orderInfo,
                                    $aOrgNoAuthInfo,$signType);

    }

    /**商家合同
     * @param $companyId
     * @param $mfwBusiId
     * @param $pdfPath
     * @param $signPos
     * @param string $signType
     */
    public static function aSignBusi($sOrgInfo, $sMfwOrgInfo, $pdfPath,
                                     $signPos,$aOrgNoAuthInfo=array(), $signType='Single')
    {
        return self::Control_SignApi()->aSignBusi($sOrgInfo,$sMfwOrgInfo,$pdfPath,$signPos,$aOrgNoAuthInfo,$signType);

    }

    /**订单结算
     * @param $order_id
     * @return mixed
     */
    public static function aGetSignInfo($order_id)
    {
        return self::Control_SignApi()->aGetSignInfo($order_id);
    }
    public static function iUpdateSignInfo($order_id)
    {
        return self::Control_SignApi()->iUpdateSignInfo($order_id);
    }


}