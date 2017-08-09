<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2017/5/27
 * Time: 下午5:10
 */

namespace apps\pay_core\esign;

/**
 * Class MControl_OrgNotifyApi
 * @property MModel_ESignUserApi Model_ESignUserApi
 * @package apps\pay_core\esign
 */
class MControl_OrgNotifyApi
{
    const STAR = 'star';//标准
    const OVAL = 'oval';//椭圆
    const RED = 'red';
    const BLUE = 'blue';
    const BLACK = 'black';
    const AUTHSUCCESS = 1; //企业认证当前状态
    const AUTHFAILURE = 0;
    const AUTHING = 2;
    const AUTHREQUEST = 0; //企业认证当前步骤
    const AUTHPAY = 1;
    const AUTHPAYVIRIFY = 2;
    /**
     * MControl_AuthApi constructor.
     * 引入sdk入口文件
     */
    public function __construct()
    {
        new \apps\MFacade_3rd_Esign_Api();
    }


    public function orgAuthNotify($code)
    {
        if (isset($code['result'])  && !empty($code['serviceId'])) {
            $aUpdate = array(
                'auth_status' => isset($code['result']) && $code['result']
                                ==='SUCCESS'?self::AUTHSUCCESS:self::AUTHFAILURE,
                'cur_step' =>self::AUTHPAY,
            );
        }
        $oModel = new MModel_ESignUserApi();
        $iRet = $oModel->iUpdateUserInfo($code['serviceId'], $aUpdate);
        $this->iInsSignRequest('toPayNotify',$code,$code['result']);
        //通知业务更新打款状态
        $aList= $oModel->aGetUserByServiceId($code['serviceId']);
        \apps\sales\ota\MFacade_OtaStatusHistoryApi::iSetPayStatus(json_decode($aList[0]['ext'],true)
                        , $aList[0]['auth_status']==self::AUTHSUCCESS?2:1);//sales工程2成功  1失败
        return $iRet ? true : false;

    }

    /**不认证直接生成e签宝账户
     * @param $code
     * @return bool
     */
    public function orgNoAuthInfo($code)
    {
        $accountAdd = $this->addOrganize($code);
        if (isset($accountAdd['errCode']) && $accountAdd['errCode'] === 0 && !empty($accountAdd['accountId'])) {
            $sealAdd = $this->addOrgTemplateSeal($accountAdd['accountId']);
            if (isset($sealAdd['errCode']) && $sealAdd['errCode'] === 0) {
                $buff = base64_decode(str_replace(' ', '+', $sealAdd['imageBase64']));
                list($res, $base64_id) = \apps\MFacade_File_Access::AUploadContent($buff);
                $code['image_base64'] = $base64_id;
                $code['account_id'] = $accountAdd['accountId'];
                $code['auth_status'] = 0;
                $result = $this->bSetNoAuthInfo($code);

            }
        }
        return $code;
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
    private function addOrgTemplateSeal($accountId)
    {
        $esign = new \apps\MFacade_3rd_Esign_Api();

        $ret = $esign->addTemplateSeal(
            $accountId,
            $templateType = self::STAR,
            $color = self::RED
            , $hText = '合同专用章'
//            $qText = '测试章'
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
        $model = new MModel_ESignUserApi();
        $iRet = $model->iUpdateUserInfo($service_id, $aUpdate);
        return $iRet ? true : false;
    }

    /**插入单条用户信息
     * @param $code
     * @param $account_id
     * @param $image_base64
     * @return bool
     */
    private function bSetNoAuthInfo($code)
    {
        $model = new MModel_ESignUserApi();
        $result = $model->iAddUserInfo($code);
        return $result ? true : false;
    }
    private function  iInsSignRequest($sUrl,$aData,$aResult){
        $aInsData['req_url']= $sUrl;
        $aInsData['service_id'] = $aData['serviceId'];
        $aInsData['req_data']= json_encode($aData,true);
        $aInsData['result']= $aResult;
        $oModel = new MModel_ESignLogApi();
        $iInsertId = $oModel->iAddRequestLog($aInsData);
        return $iInsertId;
    }


}