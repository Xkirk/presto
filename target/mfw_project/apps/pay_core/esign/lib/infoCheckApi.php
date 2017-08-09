<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2017/5/23
 * Time: 下午6:24
 */
namespace apps\pay_core\esign;

class Mlib_infoCheckApi
{
    //身份证检查
    public  function bCheck_IdCard($id_card)
    {
        if (strlen($id_card) == 18) {
            return $this->idcard_checksum18($id_card);
        } elseif ((strlen($id_card) == 15)) {
            $id_card = $this->idcard_15to18($id_card);
            return $this->idcard_checksum18($id_card);
        } else {
            return false;
        }
    }
    //手机号检查
    public static function bCheck_Mobile($mobile)
    {
        $isMatched = preg_match('/^0?(13|14|15|17|18)[0-9]{9}$/', $mobile, $matches);

        return (($isMatched)!=0);

    }

    /*
      16-19 位卡号校验位采用 Luhm 校验方法计算：
        1，将未带校验位的 15 位卡号从右依次编号 1 到 15，位于奇数位号上的数字乘以 2
        2，将奇位乘积的个十位全部相加，再加上所有偶数位上的数字
        3，将加法和加上校验位能被 10 整除。
    */
    public static function bCheck_Cardno($card) {
        $num = 0;
        $card = str_split(trim($card));
        krsort($card);
        $i = 1;
        foreach($card as $val){
            if ($i % 2) {//奇数
                $num += $val;
            } else {//偶数
                $n = $val * 2;
                if ($n > 9) $n -= 9;
                $num += $n;
            }
            $i++;
        }
        return (($num % 10) == 0);
    }


    //自动转换为大写进行的检验
    //入库前需要所有字母转为大写strtoupper
    //统一社会信用代码为18位无‘-’
    /*
     统一社会信用代码是新的全国范围内唯一的、始终不变的法定代码标识。
     由18位数字（或大写拉丁字母）组成
     第一位是           登记部门管理代码
     第二位是           机构类别代码
     第三位到第八位是   登记管理机关行政区域码
     第九位到第十七位   主体标识码（组织机构代码）
     第十八位           校验码
     校验码按下列公式计算：
     C18 = 31 - MOD ( ∑Ci * Wi ，31) (1)
     MOD  表示求余函数；
     i    表示代码字符从左到右位置序号；
     Ci   表示第i位置上的代码字符的值，采用附录A“代码字符集”所列字符；
     C18  表示校验码；
     Wi   表示第i位置上的加权因子，其数值如下表：
      i 1 2 3 4  5  6  7  8  9  10 11 12 13 14 15 16 17
     Wi 1 3 9 27 19 26 16 17 20 29 25 13  8 24 10 30 28
     当MOD函数值为0（即 C18 = 31）时，校验码用数字0表示。
     */

    public static  function check_group($str)
    {
        $one = '159Y';//第一位可以出现的字符
        $two = '12391';//第二位可以出现的字符
        $str = strtoupper($str);
        if(!strstr($one,$str['0']) || !strstr($two,$str['1']) || !empty($array[substr($str,2,6)])){
            return false;
        }
        $wi = array(1,3,9,27,19,26,16,17,20,29,25,13,8,24,10,30,28);//加权因子数值
        $str_organization = substr($str,0,17);
        $num =0;
        for ($i=0; $i <17; $i++) {
            $num +=self::transFormation($str_organization[$i])*$wi[$i];
        }
        switch ($num%31) {
            case '0':
                $result = 0;
                break;
            default:
                $result = 31-$num%31;
                break;
        }
        if(substr($str,-1,1) == self::transFormation($result,true)){
            return true;
        }else{
            return false;
        }
    }

    private function transFormation($num,$status=false)
    {
        $list =array(0,1,2,3,4,5,6,7,8,9,'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,
            'F'=>15,'G'=>16,'H'=>17,'J'=>18,'K'=>19,'L'=>20,'M'=>21,'N'=>22,'P'=>23,
            'Q'=>24,'R'=>25,'T'=>26,'U'=>27,'W'=>28,'X'=>29,'Y'=>30);//值转换
        if($status == true){
            $list = array_flip($list);//翻转key/value
        }
        return $list[$num];
    }

// 计算身份证校验码，根据国家标准GB 11643-1999
    private function idcard_verify_number($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

// 将15位身份证升级到18位
    private function idcard_15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . $this->idcard_verify_number($idcard);
        return $idcard;
    }

// 18位身份证校验码有效性检查
    private function idcard_checksum18($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        if ($this->idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }
}

//$app = new KPayment_idCheck_Api();
//$id = !empty($_SERVER['argv'][1]) ? trim($_SERVER['argv'][1]) : '123456';
//$app->bCheck_Mobile($id);
