<?php
/**
 * 记录用户行为操作的日志的数据库配置
 * @author  Devin
 * @version 1.0
 * @date    2017-06
 *
 * 注：
 * 1.只针对单个表的操作进行记录日志
 * 2.表名和数据库操作Dao属性名要保持一致
 *
 *
 * CREATE TABLE `office_user_behavior_log` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `action` tinyint(1) NOT NULL DEFAULT '0' COMMENT '操作类型  0-insert 1-update 2-delete',
    `url` varchar(512) NOT NULL DEFAULT '' COMMENT '请求URL',
    `kind` varchar(256) NOT NULL DEFAULT '' COMMENT '操作表',
    `infoid` int(11) NOT NULL COMMENT '对应表的主键值',
    `uid` int(11) NOT NULL COMMENT '操作者的UID ',
    `op_name` varchar(256) NOT NULL DEFAULT '' COMMENT '操作者拼音名 ',
    `content` text NOT NULL COMMENT '相应表上对应行的前后变化',
    `time` datetime NOT NULL COMMENT '操作时间',
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 */

namespace apps\office\common;

class MFacade_Dao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'log' => array(
            'type' => 'db_single',
            'kind' => 'office_user_behavior_log',
            'key' => 'id',
        ),

        //需要记录变化的表

        //员工部门表
        'office_department_hr' => array(
            'type' => 'db_single',
            'kind' => 'office_department_hr',
            'key' => 'id',
        ),
        //员工年假表
        'office_leave_annual' => array(
            'type' => 'db_single',
            'kind' => 'office_leave_annual',
            'key' => 'id',
        ),
        //员工关爱假表
        'office_careoff' => array(
            'type' => 'db_single',
            'kind' => 'office_careoff',
            'key' => 'id',
        ),
        //员工表
        'office_employee' => array(
            'type' => 'db_single',
            'kind' => 'office_employee',
            'key' => 'id',
        ),
    );
}