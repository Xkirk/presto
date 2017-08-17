package src.presto.test;

import src.presto.utils.StringUtil;

public class NomalTest {
    public static void main(String[] args) {
        String str = "\"asdf\"fsdfad\"adfa\"sd\"";
        System.out.println(str);
        String rs = StringUtil.trimFirstAndLastChar(str, '\"');
        System.out.println(rs);
    }

}
