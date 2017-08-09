<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2017/5/24
 * Time: 上午11:43
 */
namespace apps\pay_core\esign;
/**
 * Class EsignConfig
 * @package apps\pay_core\esign
 */
class Mlib_Conf_EsignConfig
{
    public static $business_config = array(

        /*个人实名认证请求地址*/
        //模拟环境
//        'nameRequest_api_url' => 'https://smlrealname.tsign.cn:443/realname/rest/external/person/bankauth/infoValid',
//        'nameAuth_api_url' => 'https://smlrealname.tsign.cn:443/realname/rest/external/person/bankauth/codeValid',
        //正式环境
        'nameRequest_api_url' => 'https://openapi2.tsign.cn:8444/realname/rest/external/person/bankauth/infoValid',
        'nameAuth_api_url' => 'https://openapi2.tsign.cn:8444/realname/rest/external/person/bankauth/codeValid',

        /*企业实名认证请求地址*/
        //模拟环境
//        'orgInfo_api_url' => 'https://smlrealname.tsign.cn:443/realname/rest/external/organ/infoAuth',
//        'orgToPay_api_url' => 'https://smlrealname.tsign.cn:443/realname/rest/external/organ/toPay',
//        'orgPayAuth_api_url' => 'https://smlrealname.tsign.cn:443/realname/rest/external/organ/payAuth',
        //正式环境
        'orgInfo_api_url' => 'https://openapi2.tsign.cn:8444/realname/rest/external/organ/infoAuth',
        'orgToPay_api_url' => 'https://openapi2.tsign.cn:8444/realname/rest/external/organ/toPay',
        'orgPayAuth_api_url' => 'https://openapi2.tsign.cn:8444/realname/rest/external/organ/payAuth',

        //e签宝企业实名认证回调地址
//        'orgNotify_url' => 'http://payitf.mafengwo.cn/esign/orgAuth/notify',
        'orgNotify_url' => 'http://payitf.mafengwo.cn/esign/orgAuth/notify',

        //e签宝原文保全请求地址
//        'savepdf' => 'http://smlcunzheng.tsign.cn:8083/evi-service/evidence/v1/preservation/original/url',
        'savepdf' => 'http://evislb.tsign.cn:8080/evi-service/evidence/v1/preservation/original/url',


    );
}