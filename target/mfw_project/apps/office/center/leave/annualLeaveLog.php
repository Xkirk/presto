<?php
/*
 * 操作年假记录日志模块
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */

namespace apps\office\center;


class MLeave_annualLeaveLog extends \Ko_Busi_Api {

    public static $aOprationType = array(
        '减少' => 0,
        '增加' => 1,
        '初始化' => 9
    );

    /*
     * 插入操作年假信息日志
     *
     * @access  public
     * @author  Devin
     * @param   array  $aLog  插入的数据
     * @return  int   返回插入后的主键
     * @date    2017-03-21
     */
    public function iInsert($aLog) {

        return $this->annualLeaveLogDao->iInsert($aLog);
    }

    /*
     * 年假初始化时添加的日志，(刚好满6个月的员工)
     *
     * @access  public
     * @author  Devin
     * @param   array  $aData
     * @date    2017-03-21
     */
    public function iInit($aData) {

        if ($aData['mfw_id'] < 1)
            return 0;
        $aLog = array(
            'mfw_id' => $aData['mfw_id'],
            'type' => 9,
            'days' => $aData['totalday'],
            'remark' => serialize($aData)
        );
        return $this->iInsert($aLog);
    }

    public function iDelete($id) {
        $this->annualLeaveLogDao->iDelete($id);
    }

}