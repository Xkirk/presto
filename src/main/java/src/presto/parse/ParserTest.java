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
        Date date = new Date();
        SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd");
        System.out.println(sdf.format(date));
    }

    public static void  stringToSDF() throws ParseException {
        String dsf = "yyyyMMdd";
        SimpleDateFormat simpleDateFormat = new SimpleDateFormat(dsf);
        long rs = (simpleDateFormat.parse("20110301").getTime() / 1000) - (simpleDateFormat.parse("20100301").getTime() / 1000);

        System.out.println(rs/3600/24);

    }
}
