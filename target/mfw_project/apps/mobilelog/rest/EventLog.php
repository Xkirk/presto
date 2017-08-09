<?php
namespace apps\mobilelog;

class MRest_EventLog
{
    public static $s_aAuthConf = array(
        'appname' => array('allow' => array('mobile_api', 'www')),
        'login' => array('required' => false),
    );

    public static $s_aConf = array(
        'unique' => array(),
        'poststylelist' => array(
            'default' => array(
                'type' => 'object',
                'properties' => array(
                    'sign' => array(
                        'type' => 'string',
                        'description' => 'sign part'
                    ),
                    'data' => array(
                        'type' => 'string',
                        'description' => 'data part'
                    )
                )
            )
        )
    );

    /**
     * 添加日志
     * 有返回信息
     * $.post('/mobilelog/rest/EventLog/'
     */
    public function post($update, $after_style = null, $post_style = "default")
    {
        $data = json_decode($update['data'], true);
        if (strlen($update['data']) > 200000 && count($data) > 1000) {
            \apps\MFacade_Log_Api::dlog('event_log_too_log',
                strlen($update['data'])."__".count($data)."__".json_encode($data[0]['basic']));
        }

        \apps\MFacade_Mobile_LogApi::Log($data);
    }
}