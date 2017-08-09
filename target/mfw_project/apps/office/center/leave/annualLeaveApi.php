<?php
/**
 * 个人中心入口 -- 年假信息操作类
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */

namespace apps\office\center;


class MLeave_annualLeaveApi extends \Ko_Busi_Api {
    public $iLimit = 10;

    public function aGetInfoByUid($iUid) {
        $aRet = $this->aGetList(array('mfw_id' => array($iUid)));
        if (count($aRet))
            return $aRet[0];
        return array();

    }

    public function aGetById($iId) {
        if ($iId < 1) {
            return array();
        }
        return $this->annualLeaveDao->aGet($iId);
    }

    public function aGetList($aParams) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id>0');
        if (isset($aParams['mfw_id']) and count($aParams['mfw_id']))
            $option->oAnd('mfw_id in (?)', $aParams['mfw_id']);
        if (!isset($aParams['iLimit']))
            $aParams['iLimit'] = $this->iLimit;
        if (isset($aParams['iOffSet']))
            $option->oOffset($aParams['iOffSet'])->oLimit($aParams['iLimit']);

        $option->oOrderBy('baseday asc');
        $aRet = $this->annualLeaveDao->aGetList($option);
        if (is_array($aRet) && count($aRet))
            return $aRet;
        return array();


    }

    public function aInsert($aData) {
        if ($aData['mfw_id'] > 0) {
            $aInfo = $this->aGetInfoByUid($aData['mfw_id']);
            if (empty($aInfo)) {
                $aRet = $this->annualLeaveDao->aInsert($aData);
                return $aRet['data'];
            }
        }
        return array();
    }

    public function iInsert($aData) {
        if (!empty($aData) && $aData['mfw_id'] > 0) {
            $aInfo = $this->aGetInfoByUid($aData['mfw_id']);
            if (empty($aInfo)) {
                $isn = $this->annualLeaveDao->iInsert($aData);
                return $isn;
            }
        }
        return 0;
    }

    public function iDelete($id) {
        return $this->annualLeaveDao->iDelete($id);
    }

    public function iUpdate($iId, $aUpdate) {
        if ($iId > 0 and !empty($aUpdate)) {
            $aUpdate['mtime'] = date('Y-m-d H:i:s', time());
            return $this->annualLeaveDao->iUpdate($iId, $aUpdate);
        }
        return 0;
    }

}