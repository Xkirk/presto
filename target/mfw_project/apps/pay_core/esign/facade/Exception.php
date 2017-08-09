<?php
namespace apps\pay_core\esign;

class MFacade_Exception extends \apps\MFacade_Exception_Common
{
    protected $conf_exception = array(
        "too many certification" => array(
            'code' => 1,
            'msg' => '认证次数过多，请稍后再试',
        ),"not login" => array(
            'code' => 1,
            'msg' => '未登录~',
        ),"lock because many" => array(
            'code' => 1,
            'msg' => '认证次数过多,请联系客服解除锁定',
        ),"sign because many" => array(
            'code' => 1,
            'msg' => '该订单签署次数过多,请明天再尝试~',
        ),"wrong info" => array(
            'code' => 1,
            'msg' => '认证失败，请仔细核对信息，重新提交！',
        ),"order has signed" => array(
            'code' => 1,
            'msg' => '该订单已签署',
        ),"sign false" => array(
            'code' => 1,
            'msg' => '签署失败',
        ),"Invalid Request" => array(
            'code' => 1,
            'msg' => '无效的请求',
        ),"cardInfo wrong" => array(
            'code' => 1,
            'msg' => '无效的银行卡信息',
        ),"wrong environment" => array(
            'code' => 1,
            'msg' => '当前环境不允许此操作',
        )
    );
}
