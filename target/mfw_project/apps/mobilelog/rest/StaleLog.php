<?php
namespace apps\mobilelog;

class MRest_StaleLog
{
    public static $s_aAuthConf = array(
        'appname' => array('allow' => array('mobile_api')),
        'login' => array('required' => false)
    );

    public static $s_aConf = array(
        'unique' => array(),
        'poststylelist' => array(
            'default' => array(
                'type' => 'object',
                'properties' => array(
                    'type' => array(
                        'type' => 'string',
                        'enum' => array('cdn'),
                        'description' => '日志类型'
                    ),
                    'data' => array(
                        'type' => 'object',
                        'properties' => array(
                            'host' => array(
                                'type' => 'string',
                                'description' => '域名, 形如: xxx.mafengwo.net',
                            ),
                            'ip' => array(
                                'type' => 'integer',
                                'description' => '将IP转成int类型，如果是ipv6，只保留后面四个字节',
                            ),
                            'time' => array(
                                'type' => 'integer',
                                'description' => 'unix时间戳, 图片请求的开始时间',
                            ),
                            'net' => array(
                                'type' => 'integer',
                                'description' => '取值 wifi:1 2g:2 3g:3 4g:4 other:-1 unknown:-2',
                                'enum' => array(1, 2, 3, 4, -1, -2)
                            ),
                            'size' => array(
                                'type' => 'integer',
                                'description' => '图片尺寸，单位：字节',
                            ),
                            'spend' => array(
                                'type' => 'integer',
                                'description' => '客户端从buffer中read的时间, 单位：毫秒, 如果请求失败，该值为-1',
                            ),
                            'destinationId' => array(
                                'type' => 'integer',
                                'description' => 'mdd_id, 其实就是mdd_id, android叫这个名字',
                            ),
                            'mdd_id' => array(
                                'type' => 'integer',
                                'description' => 'mdd_id',
                            ),
                            'code' => array(
                                'type' => 'integer',
                                'description' => 'HTTP Code'
                            )
                        )
                    )
                )
            )
        )
    );

    public function postMulti($logList)
    {
        $cdnLogs = array();
        foreach($logList as $log) {
            if ($log['update']['type'] == 'cdn') {
                if ($log['update']['data']['destinationId']) {
                    $log['update']['data']['mdd_id'] = $log['update']['data']['destinationId'];
                }
                $cdnLogs[] = $log['update']['data'];
            }
        }
        \apps\mobilelog\cdn\MWriteApi::VWriteLogs($cdnLogs);
        if (\apps\MFacade_Mobile_AppCodeApi::BIsAndroid(\apps\MFacade_Mobile_RequestApi::SAppCode())) {
            $basic = array(
                'app_code' => \apps\MFacade_Mobile_RequestApi::SAppCode(),
                'event_code' => 'image_load_log',
                'dt' => date('Ymd'),
                'open_udid' => \apps\MFacade_Mobile_RequestApi::SDeviceID(),
            );
            $attr = array(
                'json_list' => json_encode($logList)
            );
            \apps\MFacade_Log_Api::mobileEvent($basic, $attr);
        }
    }
}