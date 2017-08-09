<?php

/** table sql

CREATE TABLE `t_delivery_apply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `uid` int(11) unsigned NOT NULL COMMENT '员工蚂蜂窝账号',
  `department_id` int(11) unsigned NOT NULL COMMENT '员工部门id',
  `delivery_id` varchar(20) NOT NULL COMMENT '快递编号id',
  `delivery_company` tinyint(4) NOT NULL COMMENT '快递公司 1 顺丰 2 圆通',
  `apply_desc` varchar(30) NOT NULL COMMENT '申请描述',
  `audit_desc` varchar(30) NOT NULL COMMENT '审批结果描述',
  `tag` tinyint(4) NOT NULL DEFAULT '0' COMMENT '快递标签 1 已提交 2 审核通过 3 审核不通过 4 强制通过',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `ct` (`ctime`) COMMENT '创建时间索引'
) ENGINE=InnoDB AUTO_INCREMENT=774 DEFAULT CHARSET=utf8 COMMENT='快递申请表'
# 插入申请记录 insert into delivery_apply set uid=123,department_id=444,delivery_id=123402,delivery_company=1,package_desc='asdf',mtime='2017-06-01 00:00:00';
# 审核通过 update delivery_apply set tag=2 and audit_desc='pass'  where id=111;
# 审核拒绝 update delivery_apply set tag=3 and audit_desc='reject'  where id=111;
# 查看待审核申请 select * from delivery_apply where ctime between 1 and 2 offset 1 limit 10;
 
CREATE TABLE `t_delivery_package` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `apply_id` int(11) unsigned NOT NULL COMMENT '申请单id',
  `delivery_id` varchar(20) NOT NULL COMMENT '快递编号',
  `delivery_company` tinyint(4) NOT NULL COMMENT '快递公司 1 顺丰 2 圆通',
  `pay_type` tinyint(4) NOT NULL COMMENT '包裹支付类型 1 到付 2 寄付',
  `receiver` varchar(30) NOT NULL COMMENT '收件人',
  `receiver_phone` varchar(30) NOT NULL COMMENT '收件人电话',
  `receiver_company` varchar(30) NOT NULL COMMENT '收件公司',
  `receiver_address` varchar(560) NOT NULL COMMENT '收件地址',
  `cost` float NOT NULL COMMENT '花费',
  `package_desc` varchar(30) NOT NULL COMMENT '包裹描述',
  `tag` varchar(30) NOT NULL COMMENT '快递标签 1 已创建 2 账单数据一致 3 账单数据异常 4 账单比对缺失',
  `elec_order` text NOT NULL COMMENT '电子面单html',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  `sender` varchar(30) DEFAULT NULL,
  `sender_phone` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ei` (`delivery_id`) COMMENT '快递单号索引',
  KEY `ct` (`ctime`) COMMENT '创建时间索引'
) ENGINE=InnoDB AUTO_INCREMENT=614 DEFAULT CHARSET=utf8 COMMENT='快递，包裹详情'
#sql
# 插入包裹信息 insert into delivery_package  set apply_id=111,delivery_id=1223402,delivery_company=1,pay_type=1,receiver='abc',receiver_phone='15612345678',receiver_company='xxx',receiver_address='aaa',package_desc='',tag=1;
# 更新包裹花费 update delivery_package  set cost=123.56 and tag=2 where delivery_id=123;                        //订单号，数据比对一>致
# 更新包裹花费 update delivery_package  set cost=123.56 and tag=3 where delivery_id=123;                        //订单号一致，数据异>常
# 更新包裹花费 update delivery_package  set tag = 4 where tag=1 and ctime between 1 and 2;                  //对账，数据缺失
# 查看包裹详情 select * from delivery_package where delivery_id in (1,2,3,4,5);
 
CREATE TABLE `t_delivery_excel_shunfeng` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `delivery_id` varchar(20) NOT NULL COMMENT '快递编号',
  `delivery_time` timestamp NULL DEFAULT NULL COMMENT '快递日期',
  `sender` varchar(30) NOT NULL COMMENT '发件人',
  `receiver_address` varchar(100) NOT NULL COMMENT '收件地址',
  `receiver_company` varchar(64) NOT NULL COMMENT '收件公司',
  `cost` float NOT NULL COMMENT '花费',
  `extra_cost` float NOT NULL COMMENT '增值费用',
  `pay_money` float NOT NULL COMMENT '应付金额',
  `weight` float NOT NULL COMMENT '重量',
  `pay_type` varchar(20) NOT NULL COMMENT '包裹支付类型',
  `product_type` varchar(20) NOT NULL COMMENT '包裹类型',
  `discount` tinyint(4) NOT NULL COMMENT '折扣',
  `tag` tinyint(4) NOT NULL COMMENT '快递标签 1 多余记录 2 记录一致 3 异常记录 ',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ei` (`delivery_id`) USING BTREE COMMENT '快递单号索引',
  KEY `ct` (`ctime`) COMMENT '创建时间索引'
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8
#sql
# 插入快递商返回数据 insert into excel_yuantong set delivery_id=12345601, sender='xxx',receiver='yyy',receiver_address='asdf',cost=14.4,weight=5.2,tag=1;


CREATE TABLE `t_delivery_excel_yuantong` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `delivery_id` varchar(20) NOT NULL COMMENT '快递编号',
  `sender` varchar(30) NOT NULL COMMENT '发件人',
  `receiver` varchar(30) NOT NULL COMMENT '收件人',
  `receiver_address` varchar(100) NOT NULL COMMENT '收件地址',
  `cost` float NOT NULL COMMENT '花费',
  `weight` float NOT NULL COMMENT '重量',
  `tag` tinyint(4) NOT NULL COMMENT '快递标签 1 多余记录 2 记录一致 3 异常记录 ',
  `ctime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  `pay_money` float NOT NULL COMMENT '应付金额',
  PRIMARY KEY (`id`),
  KEY `ei` (`delivery_id`) COMMENT '快递单号索引',
  KEY `ct` (`ctime`) COMMENT '创建时间索引'
) ENGINE=InnoDB AUTO_INCREMENT=10651 DEFAULT CHARSET=utf8
 
 **/


/**
 * 数据库模型配置
 *
 * @author  zhoulei
 * @version  1.0
 * @date  2017-06-01
 */
namespace apps\office;
class MDelivery_Dao extends \Ko_Dao_Factory {
    protected $_aDaoConf = array(
        'apply' => array(
            'type' => 'db_single',
            'kind' => 't_delivery_apply',
            'key' => 'id',
        ),
        'package' => array(
            'type' => 'db_single',
            'kind' => 't_delivery_package',
            'key' => 'apply_id',
        ),
        'excelShunfeng' => array(
            'type' => 'db_single',
            'kind' => 't_delivery_excel_shunfeng',
            'key' => 'delivery_id',
        ),
        'excelYuantong' => array(
            'type' => 'db_single',
            'kind' => 't_delivery_excel_yuantong',
            'key' => 'delivery_id',
        ),
    );
}
