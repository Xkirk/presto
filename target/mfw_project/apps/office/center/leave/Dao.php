<?php
/**
 * 数据库模型配置
 *
 * @author  Devin
 * @version  1.0
 * @date  2017-03-01
 */
namespace apps\office\center;

class MLeave_Dao extends \Ko_Dao_Factory {
    protected $_aDaoConf = array(
        'leaveInfo' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_info',
            'key' => 'id',
        ),
        'approveRule' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_approve_rule',
            'key' => 'id',
        ),
        'approveInfo' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_approve_info',
            'key' => 'id',
        ),
        'systemSetting' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_setting',
            'key' => 'id',
        ),
        'annualLeave' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_annual',
            'key' => 'id',
        ),
        'annualLeaveLog' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_annual_opration',
            'key' => 'id',
        ),
        'careOff' => array(
            'type' => 'db_single',
            'kind' => 'office_careoff',
            'key' => 'id',
        ),

        'department' => array(
            'type' => 'db_single',
            'kind' => 'office_department_hr',
            'key' => 'id',
        ),
        'departmenttree' => array(
            'type' => 'db_single',
            'kind' => 'office_department_tree_hr',
            'key' => 'id',
        ),
        'modulemc' => array(
            'type' => 'mcache',
        ),
    );
}