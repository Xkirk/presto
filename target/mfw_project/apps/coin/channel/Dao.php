<?php

namespace apps\coin\channel;

class MDao extends \Ko_Dao_Factory
{
    protected $_aDaoConf = array(
        'channel' => array(
            'type' => 'db_single',
            'kind' => 'coin_send_channels',
            'key' => 'ch_id',
        ),
        'mcache' => array (
            'type' => 'mcache',
        ),
    );
}
