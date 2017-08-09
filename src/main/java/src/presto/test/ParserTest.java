package src.presto.parse;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;

/**
 * Created by kirk on 2017/7/19.
 */
public class ParserTest {
    public static void main(String[] args) throws ParseException {
//        stringToSDF();
        SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd kk:mm:ss");
        Date date = new Date();
        String s= sdf.format(date);
        System.out.println(s);
    }

    public static void  stringToSDF() throws ParseException {
        String dsf = "yyyyMMdd";
        SimpleDateFormat simpleDateFormat = new SimpleDateFormat(dsf);
        long rs = (simpleDateFormat.parse("20110301").getTime() / 1000) - (simpleDateFormat.parse("20100301").getTime() / 1000);

        System.out.println(rs/3600/24);

    }
}
