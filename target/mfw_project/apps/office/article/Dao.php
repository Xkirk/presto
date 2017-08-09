<?php
/**
 * 数据库模型配置
 *
 * @author  Zhoulei
 * @version  1.0
 * @date  2017-04-04
 */
namespace apps\office;
/*
 *CREATE TABLE `office_article_ext` (
 *	 `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
 *        `article_id` int(11) NOT NULL COMMENT '文章 office_article id',
 *        `comments` longtext COMMENT '用户文章评论  json',
 *        `likes` longtext COMMENT '文章点赞记录 json',
 *        `ctime` datetime NOT NULL COMMENT '记录创建时间 create time',
 *        `mtime` datetime NOT NULL COMMENT '记录修改时间 modify time',
 *        PRIMARY KEY (`id`),
 *        KEY `aid` (`article_id`) COMMENT '文章id索引'
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='文章用户扩展数据'
*/

class MArticle_Dao extends \Ko_Dao_Factory {
    protected $_aDaoConf = array(
        'articleExt' => array(
            'type' => 'db_single',
            'kind' => 'office_article_ext',
            'key' => 'article_id',
        ),
    );
}
