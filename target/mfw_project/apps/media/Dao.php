<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2017/1/11
 * Time: 下午9:22
 */
namespace apps\media;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'wechatActLog' => array(
            'type' => 'db_split',
            'kind' => 'media_wechat_actlog',
            'key' => 'open_id',
            'split' => 'app_id',
        ),
        'wechatOpLog' => array(
            'type' => 'db_split',
            'kind' => 'media_wechat_oplog',
            'key' => 'id',
            'split' => 'type_key',
        ),
    );
}