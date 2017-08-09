<?php
/**
 * Created by PhpStorm.
 * User: Loin
 * Date: 2016/12/19
 * Time: 下午4:03
 */
namespace apps\office;

class MFacade_AirApi extends \Ko_Busi_Api
{
    private static $aDeviceConf = array(
        '50000C24B79D' => array(),
        '50000F5F1807' => array(),
        '50001DD94793' => array(),
    );

    private static $aSensorTypeMap = array(
        '' => array(
            'key' => 'aqi',
            'name' => 'AQI',
            'unit' => '',
            'display' => 'AQI',
        ),
        'C9' => array(
            'key' => 'temp',
            'name' => '温度',
            'unit' => '℃',
            'display' => '星球地表温度',
        ),
        'CA' => array(
            'key' => 'hum',
            'name' => '湿度',
            'unit' => '%RH',
            'display' => '星球湿度',
        ),
        'D8' => array(
            'key' => 'pm25',
            'name' => 'PM2.5',
            'unit' => 'ug/m3',
            'display' => 'PM2.5',
        ),
        'D9' => array(
            'key' => 'voc',
            'name' => '挥发性有机化合物',
            'unit' => '',
            'display' => '挥发性有机化合物',
        ),
        '30' => array(
            'key' => 'co2',
            'name' => '二氧化碳',
            'unit' => 'ppm',
            'display' => '二氧化碳',
        ),
    );

    private static $aAQIMap = array(
        array(
            'pm25_min' => 0,
            'pm25_max' => 35,
            'min' => 0,
            'max' => 50,
        ),
        array(
            'pm25_min' => 35,
            'pm25_max' => 75,
            'min' => 50,
            'max' => 100,
        ),
        array(
            'pm25_min' => 75,
            'pm25_max' => 115,
            'min' => 100,
            'max' => 150,
        ),
        array(
            'pm25_min' => 115,
            'pm25_max' => 150,
            'min' => 150,
            'max' => 200,
        ),
        array(
            'pm25_min' => 150,
            'pm25_max' => 250,
            'min' => 200,
            'max' => 300,
        ),
        array(
            'pm25_min' => 250,
            'pm25_max' => 350,
            'min' => 300,
            'max' => 400,
        ),
        array(
            'pm25_min' => 350,
            'pm25_max' => 500,
            'min' => 400,
            'max' => 500,
        ),
    );

    private static $aStateMap = array(
        'aqi' => array(
            '|50' => '优',
            '50|100' => '良',
            '100|150' => '轻度污染',
            '150|200' => '中度污染',
            '200|300' => '重度污染',
            '300|' => '严重污染',
        ),
        'temp' => array(
            '|16' => '偏凉',
            '16|32' => '舒适',
            '32|' => '偏热',
        ),
        'hum' => array(
            '|40' => '干燥',
            '40|60' => '舒适',
            '60|' => '潮湿',
        ),
        'pm25' => array(
            '|35' => '优',
            '35|75' => '良',
            '75|115' => '轻度污染',
            '115|150' => '中度污染',
            '150|250' => '重度污染',
            '250|' => '严重污染',
        ),
        'voc' => array(
            '|10' => '清爽',
            '10|20' => '宜居',
            '20|40' => '有害',
            '40|' => '严重',
        ),
        'co2' => array(
            '|485' => '极优',
            '485|600' => '优',
            '600|800' => '良好',
            '800|1000' => '轻度污染',
            '1000|1200' => '中度污染',
            '1200|1500' => '重度污染',
            '1500|' => '严重污染',
        ),
    );

    public static function aGetInsideData()
    {
        $oApi = new MAirApi();
        $aData = $oApi->aGetData(MFacade_Const::AIR_INSIDE_DEVICE_ID);
        !empty($aData) && $aData['data']['aqi'] = self::_iComputeAQI($aData['data']['pm25']);
        self::_vParseData($aData);
        return $aData;
    }

    public static function aGetOutsideData()
    {
        $oApi = new MAirApi();
        $aData = $oApi->aGetData(MFacade_Const::AIR_OUTSIDE_DEVICE_ID);
        !empty($aData) && $aData['data']['aqi'] = self::_iComputeAQI($aData['data']['pm25']);
        self::_vParseData($aData);
        return $aData;
    }

    public static function vParseInsideData()
    {
        $oApi = new MAirApi();
        $aUserData = self::_aParseUserData();
        if(!empty($aUserData))
        {
            $aCommonData = array();
            foreach (self::$aDeviceConf as $sDeviceId => $aDevice)
            {
                $aDeviceData = self::_aParseDataByDevice($sDeviceId, $aUserData);
                $oApi->bSetData($aDeviceData, $sDeviceId);
                foreach ($aDeviceData as $sKey => $iVal)
                {
                    $aCommonData[$sKey] += $iVal;
                }
            }

            foreach ($aCommonData as &$iVal)
            {
                $iVal = intval(floor($iVal / count(self::$aDeviceConf)));
            }
            unset($iVal);

            $oApi->bSetData($aCommonData, MFacade_Const::AIR_INSIDE_DEVICE_ID);
        }
    }

    public static function vParseOutsideData()
    {
        $oApi = new MAirApi();
        $aWeatherData = self::_aParseWeatherData();
        if(!empty($aWeatherData))
        {
            $aData = array(
                'pm25' => $aWeatherData['p']['p2'],
                'co2' => $aWeatherData['p']['p3'],
            );
            $oApi->bSetData($aData, MFacade_Const::AIR_OUTSIDE_DEVICE_ID);
        }
    }

    private static function _vParseData(&$aData)
    {
        $aConf = \Ko_Tool_Utils::AObjs2map(self::$aSensorTypeMap, 'key');
        foreach ($aData['data'] as $sKey => &$iVal)
        {
            $aOneConf = $aConf[$sKey];
            $aOneConf['value'] = $iVal;
            $aOneConf['state'] = self::_sParseState($sKey, $iVal);
            $iVal = $aOneConf;
        }
        $aData['level'] = self::_iGetLevel($aData['data']['pm25']['value']);
    }

    private static function _aParseUserData()
    {
        $query = array(
            'PHONE' => MFacade_Const::AIR_USERNAME,
            'PASSWORD' => MFacade_Const::AIR_PASSWORD,
        );
        $ch = curl_init(MFacade_Const::AIR_LOGIN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        if(isset($response['code']) && 1 === intval($response['code']))
        {
            return array(
                'user_id' => $response['dataObject']['ma001'],
                'session_id' => $response['dataObject']['ma010'],
            );
        }
        return array();
    }

    private static function _aParseDataByDevice($sDeviceId, $aUserData)
    {
        $query = array(
            'USERID' => $aUserData['user_id'],
            'SESSIONID' => $aUserData['session_id'],
            'TOPHONE' => MFacade_Const::AIR_USERNAME,
            'DEVICEID' => $sDeviceId,
        );
        $ch = curl_init(MFacade_Const::AIR_DATA_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        if(isset($response['code']) && 1 === intval($response['code']))
        {
            ksort($response['dataObject']['param']);
            return self::_aParseSensorData($response['dataObject']['param']);
        }
        return array();
    }

    private static function _aParseWeatherData()
    {
        $ch = curl_init(MFacade_Const::API_OUTSIDE_URL . '?_=' . time());
        curl_setopt($ch, CURLOPT_REFERER, MFacade_Const::API_OUTSIDE_REFER);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1)'
            . ' AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if(0 === strpos($response, 'var aqi='))
        {
            return json_decode(str_replace('var aqi=', '', $response), true);
        }
        return array();
    }

    private static function _aParseSensorData($aSensorData)
    {
        $aData = array();
        foreach ($aSensorData as $sKey => $sVal)
        {
            if(false === strstr($sKey, '_SensorType')
                || !isset(self::$aSensorTypeMap[$sVal]))
            {
                continue;
            }
            $aSensorConf = self::$aSensorTypeMap[$sVal];
            $aData[$aSensorConf['key']] = intval($aSensorData[str_replace('_SensorType', '_SensorData', $sKey)]);
        }
        return $aData;
    }

    private static function _iComputeAQI($iPM25)
    {
        $iAQI = 0;
        foreach (self::$aAQIMap as $aVal)
        {
            if($iPM25 >= $aVal['pm25_min'] && $iPM25 <= $aVal['pm25_max'])
            {
                $iAQI = intval(floor(($aVal['max'] - $aVal['min'])/($aVal['pm25_max'] - $aVal['pm25_min'])
                    * ($iPM25 - $aVal['pm25_min']) + $aVal['min']));
                break;
            }
        }
        return $iAQI;
    }

    private static function _sParseState($sKey, $iVal)
    {
        $sState = '';
        foreach (self::$aStateMap[$sKey] as $sScope => $sText)
        {
            list($iMin, $iMax) = explode('|', $sScope);
            if(('' === $iMin || $iVal > $iMin)
                && ('' === $iMax || $iVal <= $iMax))
            {
                $sState = $sText;
                break;
            }
        }
        return $sState;
    }

    private static function _iGetLevel($iPM25)
    {
        if($iPM25 <= 35)
        {
            return 1;
        }
        if($iPM25 <= 75)
        {
            return 2;
        }
        if($iPM25 <= 115)
        {
            return 3;
        }
        if($iPM25 <= 150)
        {
            return 4;
        }
        return 5;
    }
}