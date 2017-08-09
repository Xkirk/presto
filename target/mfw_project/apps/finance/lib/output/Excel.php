<?php
namespace apps\finance;
require_once ('/mfw_www/include/ko/vendor/phpExcel/PHPExcel.php');
/**
 * Created by PhpStorm.
 * User: liqin
 * Date: 2017/6/14
 * Time: 下午4:49
 */

class MLib_Output_Excel
{
    private static $_aFormat = array(
        'price' => array(
            'type' => 'number', //数据类型
            'format' => '0.00'  //数据格式
        ),
        'string' => array(
            'type' => 'string',
            'format' => 's'
        ),
    );


    /**
     * @param array $data
     * @param array $title
     * @param string $name
     * @param array $properties
     * @notice 导出excel文档
     */
    public static function vOutput(array $data = array(), array $title = array(),
                                   $name = 'output',array $properties = array())
    {
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=".$name.".xls");
        header("Cache-control: no-cache");
        header("Pragma: no-cache");
        header("Expires: 0");

        $oPHPExcel = new \PHPExcel();

        //写入文档基础属性
        self::_vSetProperties($oPHPExcel, $properties);

        //写入当前Sheet
        $oCurrentSheet = $oPHPExcel->getActiveSheet();
        self::_vSetSheet($oCurrentSheet, $data, $title);

        $oObjWriter = \PHPExcel_IOFactory::createWriter($oPHPExcel, 'Excel5');
        $oObjWriter->save('php://output');

    }

    /**
     * @param $sheet
     * @param $data
     * @param $title
     * @param $sheet_name
     * @notice 填充每个sheet
     */
    private static function _vSetSheet(&$sheet, $data, $title, $sheet_name = 'mafengwo')
    {
        $sheet->setTitle($sheet_name);
        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->getDefaultColumnDimension()->setWidth(20);

        $aColumnNames = array_values($title);
        $aColumnTypes = array();
        if(!isset($title[count($title)-1])){
            $aColumnNames = array_keys($title);
            $aColumnTypes = array_values($title);
        }

        self::_vSetRow($sheet, $aColumnNames, $aColumnTypes);
        if(count($data)){
            $iRowNo = 2;
            foreach($data as $aRow){
                self::_vSetRow($sheet, $aRow, $aColumnTypes, $iRowNo++);
            }
        }
    }

    /**
     * @param $sheet
     * @param $row
     * @param array $types
     * @param int $row_no
     * @notice 写入每行数据
     */
    private static function _vSetRow(&$sheet, $row, $types = array(), $row_no = 1)
    {
        if(is_array($row) && count($row)){
            $sChar = "A";
            foreach($row as $iKey => $sVal){
                $sCurCellName = $sChar.$row_no;
                $bInsert = false;
                $aFormat = self::$_aFormat[$types[$iKey]];
                if(!empty($aFormat) > 0){
                    if($aFormat['type'] === 'number'){
                        $sheet->setCellValue($sCurCellName, $sVal);
                        $oCellStyle = $sheet->getStyle($sCurCellName);
                        $oCellStyle->getNumberFormat()->setFormatCode($aFormat['format']);
                        $bInsert = true;
                    }
                    else if($aFormat['type'] === 'string'){
                        $sheet->setCellValueExplicit($sCurCellName, $sVal, $aFormat['format']);
                        $bInsert = true;
                    }
                }

                if(!$bInsert){
                    $sheet->setCellValue($sCurCellName, $sVal);
                }
                $sChar++;
            }
        }
    }

    /**
     * @param $PHPExcel
     * @param $properties
     * @notice 设置文档基础属性
     */
    private static function _vSetProperties(&$PHPExcel, $properties)
    {
        $oProperties = $PHPExcel->getProperties();

        $sPropertiesTitle = $properties['title'] ?: 'mafengwo';
        $oProperties->setTitle($sPropertiesTitle);
        $sPropertiesCreator = $properties['creator'] ?: 'mafengwo';
        $oProperties->setCreator($sPropertiesCreator);
        $sPropertiesCompany = $properties['company'] ?: 'mafengwo';
        $oProperties->setCompany($sPropertiesCompany);

        if(isset($properties['last_modified'])){
            $oProperties->setLastModifiedBy($properties['last_modified']);
        }
        if(isset($properties['subject'])){
            $oProperties->setSubject($properties['subject']);
        }
        if(isset($properties['keywords'])){
            $oProperties->setKeywords($properties['keywords']);
        }
        if(isset($properties['manager'])){
            $oProperties->setManager($properties['manager']);
        }
        if(isset($properties['category'])){
            $oProperties->setCategory($properties['category']);
        }
        if(isset($properties['description'])){
            $oProperties->setDescription($properties['description']);
        }
    }
}