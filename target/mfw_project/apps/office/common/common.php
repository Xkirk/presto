<?php
/**
 * office模块 公用类
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-30
 */

namespace apps\office;


class MCommon_common extends \Ko_Busi_Api {

    /**
     * 上传图片操作
     *
     * @access  public
     * @author  Devin
     * @param   array   $aFile    文件数组
     * @param   string  $sThumbs  缩略图大小
     * @return  array  通过判断$result['status']是否为200来判断是否上传成功
     * @date    2017-03-30
     */
    public function aUploadFile($aFile, $sThumbs = '') {
        if (!empty($aFile)) {
            $aUpdateRet = \apps\MFacade_File_Access::AUploadFormData($aFile);
            if (in_array($aUpdateRet[0], array(\apps\MFacade_File_Access::ERR_UPLOAD_OK,
                \apps\MFacade_File_Access::ERR_UPLOAD_EXIST))) {
                $aRet['status'] = '200';
                $sFileId = $aUpdateRet[1];
                $result = array(
                    'file_id' => $sFileId,
                    'url' => \apps\MFacade_File_Url::SBuild($sFileId)
                );
                if (!empty($sThumbs)) {
                    $aThumbs = explode(',', $sThumbs);
                    foreach ($aThumbs as $sThumb) { // c_100_50_90 or z_100_0_90
                        $aThumbConfig = explode('_', $sThumb);
                        if ($aThumbConfig[0] == 'c') {
                            $iWidth = intval($aThumbConfig[1]);
                            $iHeight = intval($aThumbConfig[2]);
                            $iQuality = isset($aThumbConfig[3]) && $aThumbConfig[3] > 0 ?
                                intval($aThumbConfig[3]) : 90;
                            if ($iWidth > 0 && $iHeight > 0) {
                                $result['thumb'][$sThumb]
                                    = \apps\MFacade_File_Url::SCropStretch($sFileId, $iWidth, $iHeight, $iQuality);
                            }
                        } elseif ($aThumbConfig[0] == 'z') {
                            $iWidth = intval($aThumbConfig[1]);
                            $iHeight = intval($aThumbConfig[2]);
                            $iQuality = isset($aThumbConfig[3]) && $aThumbConfig[3] > 0 ?
                                intval($aThumbConfig[3]) : 90;
                            if ($iWidth > 0 || $iHeight > 0) {
                                $result['thumb'][$sThumb]
                                    = \apps\MFacade_File_Url::SZoomLTE($sFileId, $iWidth, $iHeight, $iQuality);
                            }
                        }
                    }
                }
                $aRet['result'] = $result;
            } else {
                $aRet['status'] = '404';
                $result = array('msg' => '图片上传失败', 'code' => $aUpdateRet[0]);
                $aRet['result'] = $result;
            }
        } else {
            $aRet['status'] = '404';
            $result = array('msg' => '图片上传失败');
            $aRet['result'] = $result;
        }
        return $aRet;
    }

    /**
     * 根据system_id,resource_id,operator,uid鉴定权限
     *
     * @access  public
     * @author  Devin
     * @param   string  $sResourceId    资源ID
     * @param   string  $sOperator      操作名称  ('CREATE', 'UPDATE', 'READ', 'DELETE', 'EXECUTE')
     * @param   int     $iUid           用户ID
     * @param   int     $iSystemId      使用系统ID （默认都是6）
     * @return  boolean
     * @date    2017-04-10
     */
    public function bIdentifyPermission($sResourceId, $sOperator, $iUid, $iSystemId = 6) {
        $permissionApi = new \apps\permission\MFacade_Api();
        //(1)通过UID来鉴定权限
        $aRet = $permissionApi->bIdentifyPermission1($iSystemId, $sResourceId, $sOperator, $iUid);
        if ($aRet['status'] === 0) {
            return true;
        }
        //(2)通过GID来鉴定权限
        $aGroupList = $permissionApi->aGetGroupListByUid($iUid);
        foreach ($aGroupList as $aGroupInfo) {
            $aRet = $permissionApi->bIdentifyPermission1($iSystemId, $sResourceId, $sOperator, $aGroupInfo['id']);
            if ($aRet['status'] === 0) {
                return true;
            }
        }
        return false;
    }
}