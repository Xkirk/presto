package src.presto.parse;

/**
 * Created by kirk on 2017/7/19.
 */
public class ParserTest {
    public static void main(String[] args) {

        String sql = LimitVerify.limitVerify(SQL.sql);
        System.out.println(sql);
    }
}
