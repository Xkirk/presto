<?php
/**
 * Created by PhpStorm.
 * User: caodi
 * Date: 2017/2/22
 * Time: 15:22
 * 给7天还未领取快递的事件发送短信
 */
namespace apps\office;

include_once("/mfw_www/htdocs/global.php");

set_time_limit(0);
$result = \apps\MFacade_Office_Api::bGetInfoByDate();
foreach ($result['list'] as $key => $value) {
    $ordernum = $value['ordernum'];
    $company = $value['company'];
    $mobile = $value['mobile'];
    $advancefee = $value['advancefee'];
    if (empty($advancefee)) {
    $msg = sprintf("你7天前的快递,订单尾号后4位为(%s)的快递(%s)到了,快递公司为（%s）,请速到北门去签收!",
        $ordernum, \apps\MFacade_Office_Api::typeConfig($value['type']), $company);
    } else {
    $msg = sprintf("你7天前的快递,订单尾号后4位为(%s)的快递(%s)到了,快递公司为（%s）,已垫付（%s元）请速到北门去签收!",
        $ordernum, \apps\MFacade_Office_Api::typeConfig($value['type']), $company, $advancefee);
    }
    \apps\MFacade_Office_Api::sendMessage($params = array($mobile, $msg));
}
