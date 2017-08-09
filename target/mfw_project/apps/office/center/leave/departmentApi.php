<?php
namespace apps\office\center;

class MLeave_departmentApi extends \Ko_Mode_Item {
    protected $_aConf = array(
        'item' => 'department',
    );

    public function aGetListByParentId($iId = 0) {
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('parent_id = ?', $iId);
        return $this->aGetList($oOption);
    }

    public function aGetInfoById($iId = 0) {
        return $this->aGet($iId);
    }

    public function iGetFirstLeaderIdByDid($iDid) {
        $iRet = 0;
        $aDepartmentInfo = $this->aGetInfoById($iDid);
        if ($aDepartmentInfo['parent_id'] == 0) {
            return $iRet;
        }
        $i = 0;
        while($i < 10) {
            $aDepartmentInfo = $this->aGetInfoById($aDepartmentInfo['parent_id']);
            if ($aDepartmentInfo['parent_id'] == 0) {
                $iRet = $aDepartmentInfo['leader_id'];
                break;
            }
            $i++;
        }
        return $iRet;
    }
}
