package src.presto.test;

import src.presto.parse.LimitVerify;
import src.presto.parse.ParseDtScope;
import src.presto.parse.SelectItemParser;
import src.presto.parse.TableNameParser;
import src.presto.utils.SQL;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;

/**
 * Created by kirk on 2017/7/19.
 */
public class ParserTest {
    public static void main(String[] args) throws ParseException {
        dateScope();
//        List<String> tables = TableNameParser.getTargetTables(SQL.sql1);
//        List<String> with = TableNameParser.getWithTables(SQL.sql1);
//        List<String> selectList = SelectItemParser.parseSelectItems(SQL.sql1);
//        for (String tb:tables
//             ) {
//            System.out.println("target:"+tb);
//        }
//        System.out.println(LimitVerify.limitVerify(SQL.sql1));

//        for (String tb:with
//             ) {
//            System.out.println("with :"+ tb);
//        }
//        for (String item:selectList
//                ) {
//            System.out.println("Item :"+ item);
//        }
//        dateScope();
//        SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd-HH:mm:ss");
//        Date date = new Date();
//        System.out.println(sdf.format(date));
    }

    public static void  dateScope() throws ParseException {
        String sql = SQL.sql1;
        ParseDtScope.parseDtScope(sql).toString();
    }
}
