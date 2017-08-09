<?php
/**
 * 记录用户行为操作的日志类
 * @author  Devin
 * @version 1.0
 * @date    2017-06
 *
 * 注：
 *  1.只针对单张表的操作记录日志
 *  2.执行insert、update、delete这3个操作的时候，就需要进行日志，而日志执行的先后顺序如下:
 *    insert  在insert后执行
 *    update  在update前后都要执行，操作前获取操作前数据，操作后获取操作后数据
 *    delete  在delete前执行
 */

namespace apps\office\common;

class MFacade_behaviorLog extends \Ko_Busi_Api {


    public $aActionDesc = array(
        0 => 'insert',
        1 => 'update',
        2 => 'delete',
    );
    public static function sayHi() {

    }


    /**
     * insert后记录日志
     *
     * @access  public
     * @author  Devin
     * @param   array   $aParam     ('kind' => '操作表'， 'infoid' => '对应表的主键值'， 'remark' => '操作说明')
     * @return  int     返回主键id
     * @date    2017-06
     *
     */
    public function wirteLogInsertEnd($aParam) {
        $aContent = array();
        $aContent['end'] = $this->aGetInfoById($aParam);
        $aInsert = array(
            'action'    => 0,
            'url'       => $this->sGetUrl(),
            'kind'      => $aParam['kind'],
            'infoid'    => $aParam['infoid'],
            'uid'       => \apps\user\MFacade_Api::iLoginUid(),
            'op_name'   => $this->sGetNameByUid(\apps\user\MFacade_Api::iLoginUid()),
            'remark'    => empty($aParam['remark']) ? '' : $aParam['remark'],
            'content'   => json_encode($aContent),
            'time'      => date('Y-m-d H:i:s', time()),
        );
        return $this->logDao->iInsert($aInsert);
    }

    /**
     * update前记录日志
     *
     * @access  public
     * @author  Devin
     * @param   array   $aParam     ('kind' => '操作表'， 'infoid' => '对应表的主键值'， 'remark' => '操作说明')
     * @return  int     返回主键id
     * @date    2017-06
     *
     */
    public function wirteLogUpdateStart($aParam) {
        $aContent = array();
        $aContent['start'] = $this->aGetInfoById($aParam);
        $aInsert = array(
            'action'    => 1,
            'url'       => $this->sGetUrl(),
            'kind'      => $aParam['kind'],
            'infoid'    => $aParam['infoid'],
            'uid'       => \apps\user\MFacade_Api::iLoginUid(),
            'op_name'   => $this->sGetNameByUid(\apps\user\MFacade_Api::iLoginUid()),
            'remark'    => empty($aParam['remark']) ? '' : $aParam['remark'],
            'content'   => json_encode($aContent),
            'time'      => date('Y-m-d H:i:s', time()),
        );
        return $this->logDao->iInsert($aInsert);
    }

    /**
     * update后记录日志
     *
     * @access  public
     * @author  Devin
     * @param    int     $ilogId     update
     * @param   array   $aParam     ('kind' => '操作表'， 'infoid' => '对应表的主键值'， 'remark' => '操作说明')
     * @return  int     返回更新时影响的行数
     * @date    2017-06
     *
     */
    public function wirteLogUpdateEnd($ilogId, $aParam) {
        $aLogInfo = $this->logDao->aGet($ilogId);
        $aContent = json_decode($aLogInfo['content'], true);
        $aContent['end'] = $this->aGetInfoById($aParam);
        $aUpdate = array(
            'content'   => json_encode($aContent),
        );
        $oOption = new \Ko_Tool_SQL();
        $oOption->oWhere('id = ?', $ilogId);
        return $this->logDao->iUpdateByCond($oOption, $aUpdate);
    }

    /**
     * delete前记录日志
     *
     * @access  public
     * @author  Devin
     * @param   array   $aParam     ('kind' => '操作表'， 'infoid' => '对应表的主键值'， 'remark' => '操作说明')
     * @return  int     返回主键id
     * @date    2017-06
     *
     */
    public function wirteLogDeleteStart($aParam) {
        $aContent = array();
        $aContent['start'] = $this->aGetInfoById($aParam);
        $aInsert = array(
            'action'    => 2,
            'url'       => $this->sGetUrl(),
            'kind'      => $aParam['kind'],
            'infoid'    => $aParam['infoid'],
            'uid'       => \apps\user\MFacade_Api::iLoginUid(),
            'op_name'   => $this->sGetNameByUid(\apps\user\MFacade_Api::iLoginUid()),
            'remark'    => empty($aParam['remark']) ? '' : $aParam['remark'],
            'content'   => json_encode($aContent),
            'time'      => date('Y-m-d H:i:s', time()),
        );
        return $this->logDao->iInsert($aInsert);
    }

    /**
     * 根据表和表主键值获取对应行的信息
     *
     * @access  public
     * @author  Devin
     * @param   array   $aParam     ('kind' => '操作表'， 'infoid' => '对应表的主键值')
     * @return  string
     * @date    2017-06
     *
     */
    private function aGetInfoById($aParam) {
        $aRet = array();
        if ($aParam['infoid'] < 0) {
            return $aRet;
        }
        $dbDao = $aParam['kind'] . 'Dao';
        $aRet = $this->$dbDao->aGet($aParam['infoid']);
        return $aRet;
    }


    /**
     * 获取当前请求的完整URL
     *
     * @access  public
     * @author  Devin
     * @return  string
     * @date    2017-06
     *
     */
    private static function sGetUrl() {
        $sUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        if (empty($_SERVER['QUERY_STRING']) == false) {
            $sUrl .= '?' . $_SERVER['QUERY_STRING'];
        }
        return $sUrl;
    }

    /**
     * 根据UID获取对应的姓名
     *
     * @access  public
     * @author  Devin
     * @param   int   $iUid 员工的UID
     * @return  string
     * @date    2017-06
     *
     */
    private function sGetNameByUid($iUid) {
        $aLoginUserInfo = \apps\MFacade_Office_Api::aGetEmployeeByUid($iUid);
        return $aLoginUserInfo['name_py'];
    }

}