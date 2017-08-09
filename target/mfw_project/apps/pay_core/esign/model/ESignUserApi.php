<?php

namespace apps\pay_core\esign;
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/3/10
 * Time: 下午4:20
 */
class MModel_ESignUserApi extends \Ko_Busi_Api
{
    const AUTHINPROGRESS = 0;
    const AUTHDONE = 1;
    const AUTHSUCCESS = 1; //企业认证当前状态
    const AUTHFAILURE = 0;
    const AUTHING = 2;
    const AUTHREQUEST = 0; //企业认证当前步骤
    const AUTHPAY = 1;
    const AUTHPAYVIRIFY = 2;

    /**
     * @param $value
     * @return mixed
     */
    public function aGetUserByIdAndName($value)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id_no=?', strtolower($value['idno']));
        $option->oAnd('name=?', $value['name']);
        $option->oAnd('auth_status=?', self::AUTHDONE);
        return $this->userDao->aGetList($option);
    }

    public function aGetUserByIds($ids)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id_no in(?)', $ids);
        $option->oAnd('auth_status=?', self::AUTHDONE);
        $aList = $this->userDao->aGetList($option);

        $aSkuInfoMap = array();
        foreach ($aList as $aItem) {
            if (!isset($aSkuInfoMap[$aItem['id_no'] . '-' . $aItem['name']])) {
                $aSkuInfoMap[$aItem['id_no'] . '-' . $aItem['name']] = $aItem;
            }
        }
        return $aSkuInfoMap;
    }

    public function aGetOrgUserStatus($name, $org_code, $cardno = '')
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('name =?', $name);
        $option->oOr('org_code=?', $org_code);
        if (!empty($cardno)) {
            $option->oAnd('card_no=?', md5($cardno));
        }
        $option->oOrderBy('ctime');
        $aList = $this->userDao->aGetList($option);
        return $aList ;

    }

    public function aGetOrgUserByService_id($service_id)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('service_id =?', $service_id);
        $aList = $this->userDao->aGetList($option);
        return array('cur_step' => $aList[0]['cur_step'], 'auth_status' => $aList[0]['auth_status']);

    }
    public function aGetAuthedOrg($name,$org_code,$cardno)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('name =?', $name);
        $option->oAnd('org_code=?', $org_code);
        $option->oAnd('card_no=?', md5($cardno));
        $option->oAnd('cur_step=?', self::AUTHPAYVIRIFY);
        $option->oAnd('auth_status=?', self::AUTHSUCCESS);
        $aList = $this->userDao->aGetList($option);
        return $aList;

    }

    /**
     * @param $query
     * @return mixed
     */
    public function aGetInvalidUser($query)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id_no=?', strtolower($query['idno']));
        $option->oOr('card_no=?', md5($query['cardno']));
        $option->oOr('mobile=?', $query['mobile']);
        $option->oAnd('auth_status=?', self::AUTHINPROGRESS);
        return $this->userDao->aGetList($option);
    }

    public function aGetOrgInvalidUser($name,$org_code)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('name=?', $name);
        $option->oOr('org_code=?', $org_code);
        $option->oAnd('length(account_id)!= ?', 0);
        $option->oAnd('auth_status !=?', self::AUTHSUCCESS);
        $option->oAnd('cur_step !=?', self::AUTHPAYVIRIFY);
        return $this->userDao->aGetList($option);
    }

    public function aGetUserByServiceId($serviceId)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('service_id = ?', $serviceId);
        return $this->userDao->aGetList($option);
    }

    public function aGetUserByIdno($personId)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id_no = ?', strtolower($personId));
        $option->oAnd('length(account_id)!= ?', 0);
        $option->oAnd('auth_status= ?', self::AUTHDONE);
        return $this->userDao->aGetList($option);
    }

    public function aGetUserByWrongName($personId, $name)
    {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id_no = ?', strtolower($personId));
        $option->oAnd('name!= ?', $name);
        $option->oAnd('length(account_id)!= ?', 0);
        $option->oAnd('auth_status= ?', self::AUTHDONE);
        return $this->userDao->aGetList($option);
    }

    public function aGetUserByOrg_code($sOrgInfo)
    {

        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('org_code = ?', $sOrgInfo['org_code']);
        $oOption->oAnd('card_no = ?', md5($sOrgInfo['cardno']));
        $oOption->oAnd('length(account_id)!= ?', 0);
        $oOption->oAnd('auth_status = ?', self::AUTHSUCCESS);
        $oOption->oAnd('cur_step = ?', self::AUTHPAYVIRIFY);
        return $this->userDao->aGetList($oOption);
    }

    public function aGetUserNOAuthByOrg_code($sOrgInfo)
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('org_code = ?', $sOrgInfo['org_code']);
        $oOption->oAnd('length(account_id)!= ?', 0);
        $oOption->oAnd('auth_status= ?', self::AUTHFAILURE);
        $oOption->oAnd('cur_step= ?', self::AUTHREQUEST);
        return $this->userDao->aGetList($oOption);
    }

    /**插入个人认证信息
     * @param $aData
     * @return mixed
     */
    public function iAddUserInfo($aData)
    {
        return $this->userDao->iInsert($aData, array(), array(), null);
    }

    /**更新个人认证信息
     * @param $service_id
     * @param $aUpdate
     * @return mixed
     */
    public function iUpdateUserInfo($service_id, $aUpdate)
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('service_id = ?', $service_id);
        return $this->userDao->iUpdateByCond($oOption, $aUpdate);
    }
    public function iUpdateOrgAccount(array $aCode, $aUpdate)
    {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('name = ?', $aCode['name']);
        $oOption->oAnd('org_code = ?', $aCode['org_code']);
        $oOption->oAnd('card_no = ?', md5($aCode['cardno']));
        $oOption->oAnd('auth_status = ?', self::AUTHSUCCESS);
        $oOption->oAnd('cur_step = ?', self::AUTHPAYVIRIFY);
        return $this->userDao->iUpdateByCond($oOption, $aUpdate);
    }
}