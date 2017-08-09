<?php

namespace apps\xauth;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'pftoken' => array (
            'type' => 'db_single',
            'kind' => 'platform_xauth_token',
            'key' => 'token',
        ),
        // 这个表和上面一样，就是使用另外一组唯一键进行查询
        // 需要自己处理缓存的更新
        'pfuidcid' => array (
            'type' => 'db_single',
            'kind' => 'platform_xauth_token',
            'key' => array('uid', 'cid'),
        ),
        'pfchgpass' => array (
            'type' => 'db_single',
            'kind' => 'platform_xauth_chgpass',
            'key' => 'token',
        ),
    );
}
