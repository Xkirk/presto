<?php
/**
 * office_careoff表操作模型类
 *
 * @author      Devin
 * @version     1.0
 * @date        2017-06
 */
namespace apps\office\center;

class MLeave_careOffDao extends \Ko_Busi_Api {

    /**
     * 插入操作
     *
     * @access  public
     * @author  Devin
     * @param   array  $aData  array('mfw_id', 'curr_available', 'used')
     * @return  int    主键ID
     * @date    2017-06
     */
    public function iInsertCareOff($aData) {
        $aInsert = array(
            'mfw_id'            => $aData['mfw_id'],
            'curr_available'    => $aData['curr_available'],
            'used'              => $aData['used'],
            'ctime'             => time(),
            'mtime'             => time(),
        );
        return $this->careOffDao->iInsert($aInsert);
    }

    /**
     * 根据mfw_id获取该用户的关爱假信息
     *
     * @access  public
     * @author  Devin
     * @param   int   $iUid   员工UID
     * @return  array
     * @date    2017-06
     */
    public function aGetCareOffInfoByUid($iUid) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('mfw_id = ?', $iUid);
        $aRet = $this->careOffDao->aGetList($option);
        if (empty($aRet)) {
            return array();
        }
        return $aRet[0];
    }

    /**
     * 根据id更新用户的关爱假信息
     *
     * @access  public
     * @author  Devin
     * @param   int   $iId      主键ID
     * @param   array $aUpdate  需要更新的数据 array('curr_available', 'used')
     * @return  bool
     * @date    2017-06
     */
    public function bUpdateCareOffById($iId, $aUpdate) {
        $option = new \Ko_Tool_SQL();
        $option->oWhere('id = ?', $iId);
        $aUpdate['mtime'] = time();
        $iRet = $this->careOffDao->iUpdateByCond($option, $aUpdate);
        if ($iRet) {
            return true;
        }
        return false;
    }

    /**
     * 根据uid返回该员工的可用关爱假天数
     *
     * @access  public
     * @author  Devin
     * @param   int   $iUid  员工UID
     * @return  float 员工可用的关爱假天数
     * @date    2017-06
     */
    public function fGetCareOffDayByUid($iUid) {
        $aCareOffInfo = $this->aGetCareOffInfoByUid($iUid);
        if (empty($aCareOffInfo)) {
            return 0.0;
        }
        return $aCareOffInfo['curr_available'];
    }
}