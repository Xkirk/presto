package src.presto.test;

import src.presto.parse.TableNameParser;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;

/**
 * Created by kirk on 2017/7/19.
 */
public class ParserTest {
    public static void main(String[] args) throws ParseException {
//        stringToSDF();
        List<String> tables = TableNameParser.getTargetTables(SQL.sql1);

        List<String> with = TableNameParser.getWithTables(SQL.sql1);
        for (String tb:tables
             ) {
            System.out.println("target:"+tb);
        }
        for (String tb:with
             ) {
            System.out.println("with :"+ tb);
        }
    }

    public static void  stringToSDF() throws ParseException {
        String dsf = "yyyyMMdd";
        SimpleDateFormat simpleDateFormat = new SimpleDateFormat(dsf);
        long rs = (simpleDateFormat.parse("20110301").getTime() / 1000) - (simpleDateFormat.parse("20100301").getTime() / 1000);

        System.out.println(rs/3600/24);

    }
}
