<?php
/**
 * Created by PhpStorm.
 * Date: 2017/5/25
 * Time: 下午6:19
 */

namespace apps\pay_core\esign;

/**
 * Class MControl_SignApi
 * @property MModel_ESignUserApi Model_ESignUserApi
 * @property MModel_ESignSaveApi Model_ESignSaveApi
 * @package apps\pay_core\esign
 */
class MControl_SignApi
{

    const SIGNQUERY = 'esign_sign_query';
    const retryNum = 3;      //签章重试次数
    const waitTime = 5000;  //重试间隔
    const path = '/mfw_rundata/tmp/'; //签署文档路径
//    const TomcatPath = '/mfw_data/docker_share/tomcat/';//应用所在机器签署文档路径


    /**
     * @return int
     */
    public function getWaitTime()
    {
        return self::waitTime;
    }

    /**
     * @return int
     */
    public function getRetryNum()
    {
        return self::retryNum;
    }


    /**获取上传url，并上传
     * @param $signArray
     * @param $path
     */
    public function aSaveFile($signArray, $path)
    {
        try {
            $code = array();
            $code['eviName'] = basename($path) . date("Y-m-d h:i:s");
            $content = new \stdClass();
            $content->contentDescription = basename($path) . date("Y-m-d h:i:s");
            $content->contentLength = strlen(file_get_contents($path));
            $content->contentBase64Md5 = base64_encode(md5_file($path, true));
            $code['content'] = $content;
            $code['eSignIds'] = array();
            $eSignIds = new \stdClass();
            $eSignIds->type = 0;
            foreach ($signArray as $item) {
                $eSignIds->value = $item;
                array_push($code['eSignIds'], $eSignIds);
            }
            $nameUrl = Mlib_Conf_EsignConfig::$business_config['savepdf'];
            $result = $this->bInsHttpRequest($nameUrl, $code, true);
            \apps\MFacade_Log_Api::webdlog('aSignOrderUrl', $result, '-');

            if (!empty($result['url'])) {
                $ret = $this->bInsUploadRequest('aPdfUpload', $path, $result['url']);
                if ($ret['errCode'] == 0) {
                    return $result;
                } else {
                    for ($i = 0; $i < $this->getRetryNum(); $i++) {
                        if ($this->bInsUploadRequest('aPdfUpload', $path, $result['url'])) {
                            return $result;
                            break;
                        }
                        sleep($this->getWaitTime());
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            \apps\MFacade_Log_Api::webdlog('esign_save_msg', $e->getMessage());
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }


    /**订单合同
     * @param $personId
     * @param $companyId
     * @param $pdfPath
     * @param $signPos
     * @param string $signType
     * @return array|bool|mixed
     */
    public function aSignOrder($personId, array $sOrgInfo, $pdfPath, $signPos, $orderInfo, $aOrgNoAuthInfo, $signType)
    {
        try {
//            签署前检查
            $aQuery = func_get_args();
            $oModel = new MModel_ESignSaveApi();
            $oLimit = new MModel_LimitApi();
            $save_info = $oModel->aGetSaveByOrderId($orderInfo['order_id']);
            if ($save_info) {
                throw new MFacade_Exception("order has signed");
            }
            $limitUidRes = $oLimit->bCheckDay($orderInfo['order_id'], self::SIGNQUERY . '_order_id', 1, 3);
            if (!$limitUidRes) {
                throw new MFacade_Exception("sign because many");
            }
            $localPdfPath = self::path . uniqid('esign') . '.pdf';
            \apps\MFacade_File_Access::BDownloadToFile($pdfPath, $localPdfPath);

            $resulet_person = $this->userSignPDF($personId, $signPos[0], $localPdfPath, $signType);
            $resulet_org = $this->orgSignPDF($sOrgInfo, $signPos[1], $localPdfPath, $aOrgNoAuthInfo, $signType);
            $signArray = $accountIdArray = array();
            if ($resulet_person['errCode'] === 0) {
                array_push($signArray, $resulet_person['signServiceId']);
                array_push($accountIdArray, $resulet_person['accountId']);
            } else {
                throw new MFacade_Exception("sign false");
            }
            if ($resulet_org['errCode'] === 0) {
                array_push($signArray, $resulet_org['signServiceId']);
                array_push($accountIdArray, $resulet_org['accountId']);
            } else {
                throw new MFacade_Exception("sign false");
            }

            $result = $this->aSaveFile($signArray, $localPdfPath);
            $this->iInsSignPDFRequest('aSignOrder', $aQuery, $result);
            if ($result['errCode'] == 0) {
                list($res, $urlId) = \apps\MFacade_File_Access::AUploadFile($localPdfPath);
                $this->bSaveSignInfo($urlId, $accountIdArray, $signArray, $result['evid'], $orderInfo);
                return array('errCode' => 0, 'msg' => '', 'signServiceId' => $signArray,
                    'filePath' => $urlId);
            }

            return $result;
        } catch (\Exception $e) {
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }

    /**
     * @param $companyId
     * @param $mfwBusiId
     * @param $pdfPath
     * @param $signPos
     * @param string $signType
     * @return array|bool|mixed
     */
    public function aSignBusi($sOrgInfo, $mfwOrgInfo, $pdfPath, $signPos, $aOrgNoAuthInfo, $signType)
    {
        try {//签署前检查
            $aQuery = func_get_args();
            $oModel = new MModel_ESignSaveApi();
            $oLimit = new MModel_LimitApi();
            $save_info = $oModel->aGetSaveByPath($this->sPdfPath($pdfPath));
            if (!empty($save_info)) {
                throw new MFacade_Exception("order has signed");
            }
            $limitUidRes = $oLimit->bCheckDay($pdfPath, self::SIGNQUERY . '_busi_pdfpath', 1, 3);
            if (!$limitUidRes) {
                throw new MFacade_Exception("sign because many");
            }
            $localPdfPath = self::path . uniqid('esign');
            \apps\MFacade_File_Access::BDownloadToFile($pdfPath, $localPdfPath);

            $resulet_self = $this->selfSignPDF($signPos[0], $localPdfPath, $signType);
            $resulet_company = $this->orgSignPDF($sOrgInfo, $signPos[1], $localPdfPath, $aOrgNoAuthInfo, $signType);
            $resulet_mfw = $this->orgSignPDF($mfwOrgInfo, $signPos[2], $localPdfPath, $aOrgNoAuthInfo, $signType);

            $signArray = $accountIdArray = array();
            if ($resulet_company['errCode'] === 0) {
                array_push($signArray, $resulet_company['signServiceId']);
                array_push($accountIdArray, $resulet_company['accountId']);

            }
            if ($resulet_mfw['errCode'] === 0) {
                array_push($signArray, $resulet_mfw['signServiceId']);
                array_push($accountIdArray, $resulet_mfw['accountId']);

            }
            if ($resulet_self['errCode'] === 0) {
                array_push($signArray, $resulet_self['signServiceId']);
                array_push($accountIdArray, 'self');

            }
            $result = $this->aSaveFile($signArray, $localPdfPath);
            $this->iInsSignPDFRequest('aSignBusi', $aQuery, $result);
            if ($result['errCode'] == 0) {
                list($res, $urlId) = \apps\MFacade_File_Access::AUploadFile($localPdfPath);
                $this->bSaveSignInfo($urlId, $accountIdArray, $signArray, $result['evid']);
                return array('errCode' => 0, 'msg' => '', 'signServiceId' => $signArray,
                    'filePath' => $urlId);
            }
            return $result;
        } catch (\Exception $e) {
            return array('errCode' => $e->getCode(), 'msg' => $e->getMessage());
        }
    }


    /**个人签章
     * @param $personId
     * @param string $signType
     * @param $signPos
     * @param $pdfPath
     * @return array|mixed
     */
    private function userSignPDF($personId, $signPos, $pdfPath, $signType)
    {
        $aQuery = func_get_args();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $oModel = new MModel_ESignUserApi();
        $person_info = $oModel->aGetUserByIdno($personId);
        $accountId = $person_info[0]['account_id'];
        $sealData = $person_info[0]['image_base64'];

        $signFile = array(
            'srcPdfFile' => $pdfPath,
            'dstPdfFile' => $pdfPath,
            'fileName' => '',
            'ownerPassword' => ''
        );
        $sealData = \apps\MFacade_File_Access::VDownloadToBuff($sealData);
        $sealData = base64_encode($sealData);

        $resulet_sign = $esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
        if ($resulet_sign['errCode'] === 0) {
            $resulet_sign['accountId'] = $accountId;
        }
        $this->iInsSignPDFRequest('userSignPDF', $aQuery, $resulet_sign);
        return $resulet_sign;
    }


    /**企业签章  位置在个人签章右边150单位
     * @param $companyId
     * @param $signPos
     * @param $pdfPath
     * @param string $signType
     * @return array|mixed
     */
    private function orgSignPDF($sOrgInfo, $signPos, $pdfPath, $aOrgNoAuthInfo, $signType)
    {
        $aQuery = func_get_args();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $oModel = new MModel_ESignUserApi();
        $aAuthOrg = $oModel->aGetUserByOrg_code($sOrgInfo);
        $aNoAuthOrg = $oModel->aGetUserNOAuthByOrg_code($sOrgInfo);
        $person_info = !empty($aAuthOrg) ? $aAuthOrg : current($aNoAuthOrg);
        if (!$person_info) {
            $orgNoAuthInfo = new MControl_OrgNotifyApi();
            $person_info = $orgNoAuthInfo->orgNoAuthInfo($aOrgNoAuthInfo);
        }
        $accountId = $sealData = '';
        if (count($person_info) == count($person_info, 1)) {
            // 一维数组
            $accountId = $person_info['account_id'];
            $sealData = $person_info['image_base64'];
        } else {
            // 多维数组
            $accountId = $person_info[0]['account_id'];
            $sealData = $person_info[0]['image_base64'];
        }
//        $signPos['posX'] = strval(intval($signPos['posX']) + self::PosOffset);
        $signFile = array(
            'srcPdfFile' => $pdfPath,
            'dstPdfFile' => $pdfPath,
            'fileName' => '',
            'ownerPassword' => ''
        );
        $sealData = \apps\MFacade_File_Access::VDownloadToBuff($sealData);
        $sealData = base64_encode($sealData);
        $resulet_sign = $esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
        if ($resulet_sign['errCode'] === 0) {
            $resulet_sign['accountId'] = $accountId;
        }
        $this->iInsSignPDFRequest('orgSignPDF', $aQuery, $resulet_sign);
        return $resulet_sign;
    }

    /**
     * 平台自身签署
     */
    private function selfSignPDF($signPos, $pdfPath, $signType, $sealId = 0)
    {
        $aQuery = func_get_args();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $sealId = !empty($sealId) ? $sealId : 0;
        $signFile = array(
            'srcPdfFile' => $pdfPath,
            'dstPdfFile' => $pdfPath,
            'fileName' => '',
            'ownerPassword' => ''
        );
        $ret = $esign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);
        $this->iInsSignPDFRequest('selfSignPDF', $aQuery, $ret);
        return $ret;
    }

    //保全信息存储
    private function bSaveSignInfo($pdfPath, $accountIdArray, $signArray, $esave_id, $orderInfo = array())
    {
        $Add = array(
            'id' => '',
            'order_id' => !empty($orderInfo['order_id']) ? $orderInfo['order_id'] : 0,
            'ota_id' => !empty($orderInfo['ota_id']) ? $orderInfo['ota_id'] : 0,
            'filename' => $pdfPath,
            'account_id' => json_encode($accountIdArray, true),
            'sign_id' => json_encode($signArray, true),
            'esave_id' => $esave_id,
            'status' => 1,
        );
        $omodel = new MModel_ESignSaveApi();
        $iInsertId = $omodel->iAddSaveInfo($Add);

        if (empty($iInsertId['affectedrows'])) {
            \apps\MFacade_Log_Api::webdlog('bSaveSignInfo', $Add, '-');
        }
        return $iInsertId;
    }

    //保全信息存储
    private function sPdfPath($pdfPath)
    {
        return self::path . basename($pdfPath);
    }

    //导出订单合同签署信息
    public function aGetSignInfo($order_id)
    {
        $oModel = new MModel_ESignSaveApi();
        $iRet = $oModel->aGetSaveByOrderId($order_id);
        $iRet = current($iRet);
        return $iRet;
    }

    //更新签署结算状态
    public function iUpdateSignInfo($order_id)
    {
        $oModel = new MModel_ESignSaveApi();
        $iRet = $oModel->iUpdateSignInfoByOrderId($order_id);
        return $iRet;
    }

    private function bInsHttpRequest($sUrl, $aData, $bIsJson)
    {
        $aInsData = array();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $aResult = $esign->request()->buildSignHttpRequest($sUrl, $aData, $bIsJson);
        $aInsData['req_url'] = $sUrl;
        $aInsData['service_id'] = $aResult['evid'];
        $aInsData['req_data'] = json_encode($aData, true);
        $aInsData['result'] = json_encode($aResult, true);
        $oModel = new MModel_ESignLogApi();
        $oModel->iAddRequestLog($aInsData);
        return $aResult;
    }

    private function iInsSignPDFRequest($sUrl, $aData, $aResult)
    {
        $aInsData['req_url'] = $sUrl;
        $aInsData['service_id'] = $aResult['signServiceId']?:$aResult['evid'];
        $aInsData['req_data'] = json_encode($aData, true);
        $aInsData['result'] = json_encode($aResult, true);
        $oModel = new MModel_ESignLogApi();
        $iInsertId = $oModel->iAddRequestLog($aInsData);
        return $iInsertId;
    }

    private function bInsUploadRequest($sUrl, $sPath, $sUploadUrl)
    {
        $aInsData = array();
        $esign = new \apps\MFacade_3rd_Esign_Api();
        $aResult = $esign->uploadPdfFile($sPath, $sUploadUrl);
        $aInsData['req_url'] = $sUrl;
        $aInsData['req_data'] = $sPath;
        $aInsData['result'] = json_encode($aResult, true);
        $oModel = new MModel_ESignLogApi();
        $oModel->iAddRequestLog($aInsData);
        return $aResult;
    }

}