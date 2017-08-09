<?php
/**
 * Created by PhpStorm.
 * Date: 2017/5/23
 * Time: 下午3:51
 * 数据库命名下划线  接口驼峰
 */

namespace apps\pay_core\esign;
/**
 * Class MFacade_AuthApi
 * @method  static MControl_AuthApi Control_AuthApi
 * @package apps\pay_core\esign
 */
class MFacade_AuthApi extends Mlib_BaseApi
{

    /**
     * 查询用户是否认证
     * @param $name ,$id
     * @param $offset
     * @param $limit
     */
    public static function bCheck($query_cond)
    {
        return self::Control_AuthApi()->bCheck($query_cond);

    }
/**
     * 查询企业用户是否认证
     * @param $name ,$id
     * @param $offset
     * @param $limit
     */
    public static function aCheckOrg($name,$org_code,$cardno)
    {
        return self::Control_AuthApi()->aCheckOrg($name,$org_code,$cardno);

    }


    /**
     * 实名认证请求
     * @param $query_cond
     */
    public static function bRequestRealName($query_cond)
    {
        return self::Control_AuthApi()->bRequestRealName($query_cond);

    }

    /**
     * 实名认证验证
     * @param $query_cond
     *
     */
    public static function bAuthRealName($code)
    {
        return self::Control_AuthApi()->bAuthRealName($code);

    }

    /**企业信息认证请求
     */
    public static function aOrgAuthRequest($aQuery)
    {
        return self::Control_AuthApi()->aOrgAuthRequest($aQuery);

    }

    /**企业对公打款
     */
    public static function aOrgAuthPay($query_cond)
    {
        return self::Control_AuthApi()->aOrgAuthPay($query_cond);
    }

    /**企业对公打款验证
     */
    public static function aOrgAuthPayVerify($sServiceId,$sCash)
    {
        return self::Control_AuthApi()->aOrgAuthPayVerify($sServiceId,$sCash);
    }

    /**创建企业信息接口
     */
    public static function oCreateOrgAccount($query_cond)
    {
        return self::Control_AuthApi()->oCreateOrgAccount($query_cond);
    }


}

