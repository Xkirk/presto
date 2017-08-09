<?php
/**
 * Created by PhpStorm.
 * Date: 2017/5/23
 * Time: 下午3:51
 * 数据库命名下划线 接口驼峰
 */

namespace apps\pay_core\esign;
use PHPCheckstyle\VariableInfo;


/**
 * Class MControl_AuthApi
 * @property MModel_ESignUserApi Model_ESignUserApi
 * @package apps\pay_core\esign
 */
class MControl_AuthApi
{
    const AGRICULTURALBANK = '中国农业银行';
    const AUTHQUERY = 'esign_auth_query';
    const IS_AUTHENTICATED = 1;//出行人状态为已认证
    const CERTIFIED = 2; //已认证过的状态，由蚂蜂窝发送短信
    const RECTANGLE = 'rectangle'; //矩形印章
    const STAR = 'star'; //星行印章
    const RED = 'red';

    const AUTHSUCCESS = 1;//成功 //企业认证当前状态
    const AUTHFAILURE = 0;//失败
    const AUTHING = 2;//认证中

    const AUTHREQUEST = 0; //企业认证当前步骤
    const AUTHPAY = 1;
    const AUTHPAYVIRIFY = 2;

    const CODEUSC = 1; //统一社会信用码
    const CODEORG = 0; //组织机构代码

    private $AuthNum = 3;//一个身份证可认证次数
    private $ORGAuthNum = 1;//一个身份证可认证次数

    /**
     * @param $query_cond
     * @return array
     */
    public function bCheck($query_cond)
    {
        try {
            $result = array();
            $arrayId = array_map('end', $query_cond);
            $arrayId = array_map('strtolower', $arrayId);
            $model = new MModel_ESignUserApi();
            $list = $model->aGetUserByIds($arrayId);
            foreach ($query_cond as $key => $value) {
                if (array_key_exists(strtolower($value['idno'] . '-' . $value['name']), $list)) {
                    $result[$value['idno'] . '-' . $value['name']] = true;
                } else {
                    $result[$value['idno'] . '-' . $value['name']] = false;
                }
            }
            return $result;
        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('realname_check_msg', $e->getMessage());
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**
     * 实名认证请求
     * @param $query_cond
     */
    public function bRequestRealName($query_cond)
    {
        try {
            $iLoginUid = \apps\user\MFacade_Api::iLoginUid();
            if (empty($iLoginUid)) {
                throw new MFacade_Exception("not login");
            }
            $model = new MModel_ESignUserApi();
            $oLimit = new MModel_LimitApi();
            $list = $model->aGetInvalidUser($query_cond);
            $limitUidRes = $oLimit->bCheckDay($iLoginUid, self::AUTHQUERY . '_day_name_auth', 1, 3);
            if ((count($list) >= $this->AuthNum) || !$limitUidRes) {
                throw new MFacade_Exception("lock because many");
            }
            $redis = \Ko_Data_RedisAgent::OInstance('virgo');
            $aInser = array_intersect_key($query_cond, array_flip(array('mobile', 'id', 'idno', 'name')));
            $redis->vHSet(self::AUTHQUERY, "esign" . $iLoginUid, \Ko_Tool_Enc::SEncode($aInser));

            //已验证过，但是待验证列表内联系人再次请求认证，由蚂蜂窝发送短信
            $list = $model->aGetUserByIdno(strtolower($query_cond['idno']));
            if (!empty($list)) {
                if ($list[0]['name'] == $query_cond['name']) {
                    $limitRes = $oLimit->bCheckDay($query_cond['mobile'], self::AUTHQUERY . '_day_sendsns', 1, 5);
                    if (!$limitRes) {
                        throw new MFacade_Exception("too many certification");
                    }
                    $sCode = \apps\MFacade_User_verifyCodeApi::SGenerateByMobile($query_cond['mobile']);
                    \apps\sms\MFacade_Api::bSendNoticeSms($query_cond['mobile'], '蚂蜂窝实名认证验证码：' . $sCode .
                        '(验证码15分钟内有效，请勿泄露)');

                    return array('errCode' => 2, 'msg' => '');
                } else {
                    throw new MFacade_Exception("lock because many");
                }
            }

            //校验数据，错误抛出异常
            $this->checkParams($query_cond);
            $nameUrl = Mlib_Conf_EsignConfig::$business_config['nameRequest_api_url'];
            //请求第三方
            $result = $this->bInsHttpRequest($nameUrl, $query_cond, true);
            \apps\MFacade_Log_Api::webdlog('esign_realname_request', $result, '-');

            if (isset($result['errCode']) && $result['errCode'] === 0 && !empty($result['serviceId'])) {
                $redis->bset("esignServiceId" . $iLoginUid, $result['serviceId']);
                //待存储信息
                $recode = $query_cond;
                $recode['serviceId'] = $result['serviceId'];
                $recode['uid'] = $iLoginUid;
                //记录请求信息
                $this->recordRealNameInfo($recode);
                return array('errCode' => 0, 'msg' => '');
            } else {
                //待存储信息
                $recode = $query_cond;
                $recode['uid'] = $iLoginUid;
                //记录请求信息
                $this->recordRealNameInfo($recode);
                throw new MFacade_Exception("wrong info");
            }

        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('realname_request_msg', $e->getMessage());
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**
     * 实名认证验证
     * @param $query_cond
     *
     */
    public function bAuthRealName($code)
    {
        try {
            $iLoginUid = \apps\user\MFacade_Api::iLoginUid();
            if (empty($iLoginUid)) {throw new MFacade_Exception("not login");}
            //已认证用户再次认证，
            $redis = \Ko_Data_RedisAgent::OInstance('virgo');
            $query_cond = \Ko_Tool_Enc::ADecode($redis->vHGet(self::AUTHQUERY, "esign" . $iLoginUid));
            if ($code['scodestatus'] == self::CERTIFIED) {
                if (\apps\MFacade_User_verifyCodeApi::bPreCheckByMobile($query_cond['mobile'], $code['code'])) {
                    $this->aGenPassenger($query_cond['id'], array('passenger_name' => $query_cond['name'],
                        'mobile'=>$query_cond['mobile'], 'id_no' => $query_cond['idno']),$code);
                }
                return array('errCode' => 0, 'msg' => '');
            }
            $code['serviceId'] = $redis->vGet("esignServiceId" . $iLoginUid);
            $model = new MModel_ESignUserApi();
            $aServiceId = $model->aGetOrgUserByService_id($code['serviceId']);
            if ($aServiceId['auth_status'] !== self::AUTHREQUEST) {
                throw new MFacade_Exception("Invalid Request");
            }
            $oLimit = new MModel_LimitApi();
            $limitRes = $oLimit->bCheckDay($code['serviceId'], self::AUTHQUERY . '_day_auth', 1, 3);
            if (!$limitRes) {
                throw new MFacade_Exception("too many certification");
            }
            $aData = array_intersect_key($code, array_flip(array('code', 'serviceId')));
            $nameUrl = Mlib_Conf_EsignConfig::$business_config['nameAuth_api_url'];
            $result = $this->bInsHttpRequest($nameUrl, $aData, true);
            \apps\MFacade_Log_Api::webdlog('realname_auth', $result);

            if (isset($result['errCode']) && $result['errCode'] === 0 && !empty($result['serviceId'])) {
                $person_info = $model->aGetUserByServiceId($result['serviceId']);
                $accountAdd = $this->esignAddPerson($person_info[0]);
                if (isset($accountAdd['errCode']) && $accountAdd['errCode'] === 0 && !empty($accountAdd['accountId'])){
                    $sealAdd = $this->addPersonTemplateSeal($accountAdd['accountId']);
                    if (isset($sealAdd['errCode']) && $sealAdd['errCode'] === 0) {
                        $buff = base64_decode(str_replace(' ', '+', $sealAdd['imageBase64']));
                        list($res, $path) = \apps\MFacade_File_Access::AUploadContent($buff);
                        $this->bSetUserInfo($code['serviceId'], $accountAdd['accountId'], $path);
                        $iPassage_id = $this->aGenPassenger($query_cond['id'], array('passenger_name' =>
                        $query_cond['name'], 'mobile'=>$query_cond['mobile'], 'id_no' => $query_cond['idno']),$code);
                        $accountAdd['id'] = $iPassage_id;

                        return $accountAdd;
                    }
                }
                return false;
            }
            return false;
        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('realname_request_msg', $e->getMessage());
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /*
     * 判断出行人信息  不正确则修改
     */
    public function aGenPassenger($iId, array $aQueryInfo,$code)
    {
        $iLoginUid = \apps\user\MFacade_Api::iLoginUid();
        try {
            if (($iId === '')) {
                $iPassage_id = \apps\flight\MFacade_Api::iInsertPassenger($iLoginUid, $aQueryInfo['passenger_name'],
                    '', '', $aQueryInfo['mobile'], '', '', '', 1,
                    array(array('id_type' => 1, 'id_no' => $aQueryInfo['id_no'])));
                $this->bInsLog('iInsertPassenger',array_merge($aQueryInfo,$code),$iPassage_id);
            } else {
                $aPassenger = \apps\flight\MFacade_Api::aGetPassengerById($iId);
                foreach ($aPassenger['identity_list'] as &$aItem) {
                    if ($aItem['id_type'] == 1) {
                        $iIdentityId = $aItem['id'];
                        $iPassengerIdNo = $aItem['id_no'];
                    }
                }
                if ($aPassenger['passenger_name'] != $aQueryInfo['passenger_name']
                    || $aQueryInfo['id_no'] != $iPassengerIdNo) {
                    $iPassage_id =\apps\flight\MFacade_Api::iUpdatePassenger($iId, \apps\user\MFacade_Api::iLoginUid(),
                        $aQueryInfo['passenger_name'], '', '', $aQueryInfo['mobile'], '', '', '', 1,
                        array(array('id_type' => 1, 'id' => $iIdentityId, 'id_no' => $aQueryInfo['id_no'])));
                }
            }
            return $iPassage_id ;
        } catch (\Exception $e) {
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**企业认证check
     * @param $name
     * @param $org_code
     * @param $cardno
     * @return array
     */
    public function aCheckOrg($name, $org_code, $cardno)
    {
        try {
            $oModel = new MModel_ESignUserApi();
            $aOrgInfo = $oModel->aGetOrgUserStatus($name, $org_code, $cardno);

            if (empty($aOrgInfo) || empty($name) || empty($org_code) || !$result = max($aOrgInfo)) {
                $result['cur_step'] = self::AUTHREQUEST;
                $result['auth_status'] = -1;
            } else {
                foreach ($aOrgInfo as $aItem) {
                    if ($aItem['cur_step'] == self::AUTHPAYVIRIFY && $aItem['auth_status'] == self::AUTHSUCCESS) {
                        return array('step' => $aItem['cur_step'], 'status' => $aItem['auth_status']);
                    }
                }
            }

            return array('step' => $result['cur_step'], 'status' => $result['auth_status']);
        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('realname_orgcheck_msg', $e->getMessage());
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**企业信息认证请求
     */

    public function aOrgAuthRequest($aQuery)
    {
        try {
            $sOrgCode = $aQuery['regType'] == self::CODEUSC ? $aQuery['codeUSC'] : $aQuery['codeORG'];
            if ($aQuery['regType'] == self::CODEUSC) {
                unset($aQuery['codeORG']);
            } else if ($aQuery['regType'] == self::CODEORG) {
                unset($aQuery['codeUSC']);
            }
            $iLoginUid = \apps\user\MFacade_Api::iLoginUid();
            if (empty($iLoginUid)) {
                throw new MFacade_Exception("not login");
            }

            $model = new MModel_ESignUserApi();
            $list = $model->aGetOrgInvalidUser($aQuery['name'], $sOrgCode);

            if ((count($list) >= $this->ORGAuthNum)) {
                throw new MFacade_Exception("lock because many");
            }

            $this->checkParams($aQuery);

            $sNameUrl = Mlib_Conf_EsignConfig::$business_config['orgInfo_api_url'];
            $aData = array_intersect_key($aQuery, array_flip(array(
                'name', 'codeORG', 'codeUSC', 'legalName', 'legalIdno')));

            $aResult = $this->bInsHttpRequest($sNameUrl, $aData, true);
            \apps\MFacade_Log_Api::webdlog('esign_org_auth_request', $aResult, '-');
            $aRecode = $aQuery;
            if (isset($aResult['errCode']) && $aResult['errCode'] === 0 && !empty($aResult['serviceId'])) {
                \Ko_Data_RedisAgent::OInstance('virgo')->bset("esignOrg" . $iLoginUid, $aResult['serviceId']);
                //待存储信息
                $aRecode['auth_status'] = self::AUTHSUCCESS;
            } else {
                $aRecode['auth_status'] = self::AUTHFAILURE;
            }
            //待存储信息
            $aRecode['org_code'] = $sOrgCode;
            $aRecode['service_id'] = $aResult['serviceId'];
            $aRecode['uid'] = $iLoginUid;
            $aRecode['cur_step'] = $aResult['cur_step'] = self::AUTHREQUEST;
            //记录请求信息
            $this->iRecordOrgInfo($aRecode);
            return $aResult;

        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('esign_org_auth_request', $e->getMessage(), '-');
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }

    }

    /**企业对公打款
     */
    public function aOrgAuthPay($query_cond)
    {
        try {
            if (isset($query_cond['cardno']) && $query_cond['bank'] ==
                self::AGRICULTURALBANK && strlen($query_cond['cardno']) <= 15) {
                throw new MFacade_Exception("cardInfo wrong");
            }
            $model = new MModel_ESignUserApi();
            $list = $model->aGetOrgUserByService_id($query_cond['serviceId']);
            if (empty($list) || $list['auth_status'] !== self::AUTHSUCCESS
                || $list['cur_step'] !== self::AUTHREQUEST) {
                throw new MFacade_Exception("Invalid Request");
            }
            $query_cond['notify'] = Mlib_Conf_EsignConfig::$business_config['orgNotify_url'];
            $nameUrl = Mlib_Conf_EsignConfig::$business_config['orgToPay_api_url'];
            $result = $this->bInsHttpRequest($nameUrl, $query_cond, true);
            \apps\MFacade_Log_Api::webdlog('esign_org_auth_pay', $result, '-');
            $result['cur_step'] = $query_cond['cur_step'] = self::AUTHPAY;
            $query_cond['auth_status'] = $result['errCode'] === 0 ? self::AUTHING : self::AUTHFAILURE;
            $this->iUpdateOrgUserInfo($query_cond);
            return $result;

        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('esign_org_auth_pay', $e->getCode() . '-' . $e->getMessage(), '-');
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }

    }

    /**企业对公打款验证
     */
    public function aOrgAuthPayVerify($sServiceId, $sCash)
    {
        try {
            $query_cond['serviceId'] = $sServiceId;
            $query_cond['cash'] = $sCash;
            $redis = \Ko_Data_RedisAgent::OInstance('virgo');
            $model = new MModel_ESignUserApi();
            $list = $model->aGetOrgUserByService_id($query_cond['serviceId']);
            if (empty($list) || $list['auth_status'] === self::AUTHFAILURE) {
                return array('code' => 1, 'msg' => $redis->vGet($sServiceId));
            }
            $nameUrl = Mlib_Conf_EsignConfig::$business_config['orgPayAuth_api_url'];
            $result = $this->bInsHttpRequest($nameUrl, $query_cond, true);
            \apps\MFacade_Log_Api::webdlog('esign_org_auth_check', $result, '-');

            $oLimit = new MModel_LimitApi();
            $limitUidRes = $oLimit->bCheckDay($result['errCode'] . '-' . $sServiceId,
                self::AUTHQUERY . '_day_pay_verify', 1, 3);
            if (!$limitUidRes) {
                \Ko_Data_RedisAgent::OInstance('virgo')->bset($sServiceId,$result['msg'] );
                $query_cond['auth_status'] = self::AUTHFAILURE;
                $result['cur_step'] = $query_cond['cur_step'] = self::AUTHPAYVIRIFY;
                $this->iUpdateOrgUserInfo($query_cond);
            }
            if ($result['errCode'] === 0) {
                $query_cond['auth_status'] = self::AUTHSUCCESS;
                $result['cur_step'] = $query_cond['cur_step'] = self::AUTHPAYVIRIFY;
                $this->iUpdateOrgUserInfo($query_cond);
            }
            return $result;
        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('esign_org_auth', $e->getCode() . '-' . $e->getMessage(), '-');
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

//创建 企业账户
    public function oCreateOrgAccount($query_cond)
    {
        try {
            $model = new MModel_ESignUserApi();
            $aOrgInfo = $query_cond['aOrgInfo'];
            $aSealInfo = $query_cond['aSealInfo'];
            $aOrgList = $model->aGetAuthedOrg($aOrgInfo['name'], $aOrgInfo['org_code'], $aOrgInfo['cardno']);
            $aAccountAdd = $this->addOrganize($aOrgList[0]);
            if (isset($aAccountAdd['errCode']) && $aAccountAdd['errCode'] === 0 && !empty($aAccountAdd['accountId'])) {
                $aSealAddRet = $this->addOrgTemplateSeal($aAccountAdd['accountId'], $aSealInfo['sTemplateType'],
                    $aSealInfo['sColor'], $aSealInfo['sHText'], $aSealInfo['sQText']);
                if (isset($aSealAddRet['errCode']) && $aSealAddRet['errCode'] === 0) {
                    $sBuff = base64_decode(str_replace(' ', '+', $aSealAddRet['imageBase64']));
                    list($res, $sBase64Id) = \apps\MFacade_File_Access::AUploadContent($sBuff);
                    $query_cond['accountId'] = $aAccountAdd['accountId'];
                    $query_cond['image_base64'] = $sBase64Id;
                    $iRet = $this->iUpdateOrgAccount($query_cond);
                    return $iRet ? true : false;
                }
            }
            \apps\MFacade_Log_Api::webdlog('esign_org_create_account', $aAccountAdd, '-');
            return false;
        }catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('esign_create_org_account', $e->getCode() . '-' . $e->getMessage(), '-');
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }

    }

    //创建个人账户
    private function esignAddPerson($code)
    {
        $esign = new \apps\MFacade_3rd_Esign_Api();

        $mobile = $code['mobile'];
        $name = $code['name'];
        $idNo = $code['id_no'];
        $personarea = !empty($code['area']) ? $code['area'] : 0;
        $email = !empty($code['email']) ? $code['email'] : '';
        $organ = !empty($code['organ']) ? $code['organ'] : '';
        $title = !empty($code['title']) ? $code['title'] : '';
        $address = !empty($code['address']) ? $code['address'] : '';
        $ret = $esign->addPersonAccount($mobile, $name, $idNo, $personarea, $email, $organ, $title, $address);
        \apps\MFacade_Log_Api::webdlog('esgin_AddPerson', $ret, '-');
        return $ret;
    }


    //个人模板印章，返回印章imgbase64
    private function addPersonTemplateSeal($accountId)
    {
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $ret = $esign->addTemplateSeal(
            $accountId,
            $templateType = self::RECTANGLE,
            $color = self::RED
        );
        \apps\MFacade_Log_Api::webdlog('esgin_PersonSal', $ret, '-');
        return $ret;
    }

    /**
     * 创建企业用户
     */
    private function addOrganize($code)
    {
        $esign = new \apps\MFacade_3rd_Esign_Api();

        $name = $code['name'];
        $organCode = $code['org_code'];
        $regType = $code['reg_type'];
        $mobile = !empty($code['mobile']) ? $code['mobile'] : '';
        $email = !empty($code['email']) ? $code['email'] : '';
        $organType = !empty($code['org_type']) ? $code['org_type'] : 0;
        $legalArea = !empty($code['legal_area']) ? $code['legal_area'] : 0;
        $userType = !empty($code['user_type']) ? $code['user_type'] : '';
        $agentName = !empty($code['agent_name']) ? $code['agent_name'] : '';
        $agentIdNo = !empty($code['agent_idno']) ? $code['agent_idno'] : '';
        $legalName = !empty($code['legal_name']) ? $code['legal_name'] : '';
        $legalIdNo = !empty($code['legal_idno']) ? $code['legal_idno'] : '';
        $ret = $esign->addOrganizeAccount(
            $mobile, $name, $organCode, $regType, $email, $organType, $legalArea, $userType, $agentName, $agentIdNo,
            $legalName, $legalIdNo, $address = '', $scope = '');
        return $ret;
    }

//企业模板印章，返回印章imgbase64
    private  function addOrgTemplateSeal($accountId, $sTemplateType, $sColor, $sHText = '', $sQText = '')
    {
        $esign = new \apps\MFacade_3rd_Esign_Api();

        $ret = $esign->addTemplateSeal(
            $accountId,
            $templateType = !empty($sTemplateType) ? $sTemplateType : self::STAR,
            $color = !empty($sColor) ? $sColor : self::RED,
            $hText = $sHText,
            $qText = $sQText
        );
        return $ret;
    }

    private function bSetUserInfo($service_id, $account_id, $image_base64)
    {
        $aUpdate = array(
            'image_base64' => $image_base64,
            'account_id' => $account_id,
            'auth_status' => 1,
        );
        $omodel = new MModel_ESignUserApi();
        $iRet = $omodel->iUpdateUserInfo($service_id, $aUpdate);
        return $iRet ? true : false;
    }

//企业认证更新当前状态
    private function iUpdateOrgUserInfo($code)
    {
        $aUpdate = array(
            'auth_status' => $code['auth_status'],
            'cur_step' => $code['cur_step'],
        );
        if ($code['cur_step'] == self::AUTHPAYVIRIFY) {
            $aUpdate['account_id'] = $code['accountId'];
            $aUpdate['image_base64'] = $code['image_base64'];
        }
        if ($code['cur_step'] == self::AUTHPAY) {
            $aUpdate['card_no'] = md5($code['cardno']);
        }

        $omodel = new MModel_ESignUserApi();
        $iRet = $omodel->iUpdateUserInfo($code['serviceId'], $aUpdate);
        return $iRet ? true : false;
    }

//企业账户新增
    private function iUpdateOrgAccount($code)
    {
        $aUpdate = array(
            'account_id' => $code['accountId'],
            'image_base64' => $code['image_base64'],
        );
        $omodel = new MModel_ESignUserApi();
        $iRet = $omodel->iUpdateOrgAccount($code['aOrgInfo'], $aUpdate);
        return $iRet ? true : false;
    }

    private function checkParams($query_cond)
    {
        $idcheck = new Mlib_infoCheckApi();
        $idchecked = isset($query_cond['idno']) && $idcheck->bCheck_IdCard($query_cond['idno']);
        $mobileCheck = isset($query_cond['mobile']) && Mlib_infoCheckApi::bCheck_Mobile($query_cond['mobile']);
        $orgCodeCheck = !empty($query_cond['codeUSC']) && $query_cond['regType'] == self::CODEUSC
            && Mlib_infoCheckApi::check_group($query_cond['codeUSC']);
        if (isset($query_cond['idno']) && !$idchecked) {
            throw new \Exception('身份证填写错误', Mlib_Constant::IDCARD_ERROR);
        }
        if (isset($query_cond['mobile']) && !$mobileCheck) {
            throw new \Exception('手机号填写错误', Mlib_Constant::INVALID_MOBILE);
        }
        if (!empty($query_cond['codeUSC']) && $query_cond['regType'] == self::CODEUSC && !$orgCodeCheck) {
            throw new \Exception('统一社会信用码错误', Mlib_Constant::ACCOUNT_NOT_EXIST);
        }

    }


//请求数据入个人用户表
    private function recordRealNameInfo(array $query_cond)
    {
        $aAdd = array(
            'id' => '',
            'name' => $query_cond['name'],
            'id_no' => strtolower($query_cond['idno']),
            'mobile' => $query_cond['mobile'],
            'card_no' => md5($query_cond['cardno']),
            'service_id' => !empty($query_cond['serviceId']) ? $query_cond['serviceId'] : '',
            'uid' => $query_cond['uid'],
        );
        $omodel = new MModel_ESignUserApi();
        $iInsertId = $omodel->iAddUserInfo($aAdd);
        return $iInsertId;

    }

//请求数据入企业用户表
    private function iRecordOrgInfo(array $query_cond)
    {
        $aAdd = array(
            'id' => '',
            'name' => $query_cond['name'],
            'id_no' => strtolower($query_cond['legalIdno']),
            'card_no' => md5($query_cond['cardno']),
            'org_code' => $query_cond['org_code'],
            'ext' => json_encode($query_cond['ext']),
            'reg_type' => $query_cond['regType'],
            'service_id' => !empty($query_cond['service_id']) ? $query_cond['service_id'] : '',
            'uid' => $query_cond['uid'],
            'auth_status' => $query_cond['auth_status'],
            'cur_step' => $query_cond['cur_step'],
        );
        $oModel = new MModel_ESignUserApi();
        $iInsertId = $oModel->iAddUserInfo($aAdd);
        return $iInsertId;
    }
    private function  bInsHttpRequest($sUrl, $aData, $bIsJson){
        $aInsData=array();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $aResult = $esign->request()->buildSignHttpRequest($sUrl, $aData, $bIsJson);
        $aInsData['req_url'] = $sUrl;
        $aInsData['service_id'] = $aData['serviceId']?:$aResult['serviceId'];
        $aInsData['req_data'] = json_encode($aData, true);
        $aInsData['result'] = json_encode($aResult, true);
        $oModel = new MModel_ESignLogApi();
        $oModel->iAddRequestLog($aInsData);
        return $aResult;
    }
    private function  bInsLog($sUrl, $aData,$aResult){
        $aInsData=array();
        $aInsData['req_url'] = $sUrl;
        $aInsData['service_id'] = isset($aData['serviceId'])?$aData['serviceId']:'';
        $aInsData['req_data'] = json_encode($aData, true);
        $aInsData['result'] = json_encode($aResult, true);
        $oModel = new MModel_ESignLogApi();
        $oModel->iAddRequestLog($aInsData);
        return $aResult;
    }

}

