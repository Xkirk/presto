<?php
namespace apps\mobilelog;

\Ko_Web_Event::On ('ko.error', 'exception', function (\Exception $ex) {
    echo json_encode(array(
        'rc' => $ex->getCode(),
        'rm' => $ex->getMessage()
    ));
});

\Ko_Web_Event::On ('ko.error', '500', function ($errno, $errstr, $errfile, $errline, $errcontext) {
    if (IS_WEBTEST_SERVER || $_REQUEST['debug'] == 1) {
        \Ko_Web_Error::V500($errno, $errstr, $errfile, $errline, $errcontext);
    } else {
        \apps\MFacade_Mobile_MonitorApi::OnError($errstr, $errfile, $errline, $errcontext);
        echo json_encode(array(
            'rc' => 91111,
            'rm' => '服务器错误'
        ));
    }
});

\Ko_Web_Event::On ('ko.dispatch', '404', function () {
    if (IS_WEBTEST_SERVER || $_REQUEST['debug'] == 1) {
        \Ko_Web_Route::V404();
    } else {
        echo json_encode(array(
            'rc' => 91111,
            'rm' => 'uri error'
        ));
    }
    exit;
});

\Ko_Web_Event::On ('ko.web', 'gzip', function () {
    $encoding = $_SERVER['HTTP_CONTENT_ENCODING'];

    if ($encoding === 'gzip') {
        $body = substr(file_get_contents('php://input'), 10, -8);
        $inflated = gzinflate($body);
        parse_str($inflated, $params);
        $_POST = $params;
        $_REQUEST = array_merge($_GET, $_COOKIE, $params);
    }
});
\Ko_Web_Event::Trigger('ko.web', 'gzip');


\Ko_Web_Event::On ('ko.rewrite', 'before', function () {
    if (\Ko_Web_Request::SRequestUri() === '/mobilelog/rest/EventLog/' && isset($_POST['jsondata'])) {
        $jsondata = @json_decode($_POST['jsondata'], true);
        if (is_array($jsondata) && $jsondata['update']['data'] === '[]') {
            echo json_encode(array(
                'rc' => 0,
                'rm' => 0,
                'data' => array()
            ));
            exit;
        }
    }
});
