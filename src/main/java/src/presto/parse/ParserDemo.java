package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;

import java.util.*;

/**
 * Created by kirk on 2017/7/7.
 */
public class ParserDemo {
    //    public static HashSet<String> tableList = new HashSet<>();
    public static List<String> tableList = new ArrayList<>();
    public static List<String> withTableList = new ArrayList<>();
    public static String sql = "";
    public static void main(String[] args) {

        String sql = " select hour,count(*) from mobile_event_parquet where dt='20170703' group by hour order by hour union all select hour,count(*) from mobile_event_parquet ";
        SqlParser parser = new SqlParser();
        Query query = (Query) parser.createStatement(sql);
        ShowTables showTables = (ShowTables) parser.createStatement("show tables");
//        QuerySpecification querySpecification = (QuerySpecification)query.getQueryBody();
        Union union = query.getQueryBody() instanceof Union ? (Union) query.getQueryBody() : null;
        QuerySpecification querySpecification = query.getQueryBody() instanceof QuerySpecification ? ((QuerySpecification) query.getQueryBody()) : null;

//        Select select = body.getSelect();
        querySpecification.getWhere();
//        System.out.println("select :" +select);
        System.out.println("FROM : " + querySpecification.getFrom());
//        ((QuerySpecification) query.getQueryBody()).getFrom()
//        Optional<Relation> join = ((QuerySpecification) query.getQueryBody()).getFrom();
        Optional<With> with = query.getWith();
        if (with.isPresent()) {
            List<WithQuery> queries = with.get().getQueries();
            for (WithQuery withQuery : queries) {
                withTableList.add(withQuery.getName());
                QuerySpecification querybody = withQuery.getQuery().getQueryBody() instanceof QuerySpecification ? ((QuerySpecification) withQuery.getQuery().getQueryBody()) : null;
                Union getUnion = withQuery.getQuery().getQueryBody() instanceof Union ? ((Union) withQuery.getQuery().getQueryBody()) : null;
                if (querybody != null) {
                    Optional<Relation> getFrom = querybody.getFrom();
                    parseTableNameInFrom(getFrom);
                }
                if (getUnion != null) {
                    List<Relation> unionRelations = getUnion.getRelations();
                    for (Relation relation :
                            unionRelations) {

                    }
                }
            }
        }


        Optional<Relation> getFrom = querySpecification.getFrom();
        parseTableNameInFrom(getFrom);
        System.out.println(tableList.size());
        for (String tableName :
                tableList) {
            System.out.println(tableName);
        }
    }

    /**
     * 解析from,从from下的内容获取Table
     *
     * @param from
     */
    public static void parseTableNameInFrom(Optional<Relation> from) {
        if (from.isPresent()) {
            Table table = from.get() instanceof Table ? ((Table) from.get()) : null;
            if (table != null) {
                tableList.add(table.getName().toString());
            }
            Join join = from.get() instanceof Join ? ((Join) from.get()) : null;
            if (join != null) {
                parseTableNameInJoin(join);
            }
        }
    }


    /**
     * 递归调用getTableName方法,
     * 若Join的Left仍然是Join类型,则继续向下调用
     *
     * @param join
     */
    public static void parseTableNameInJoin(Join join) {
        AliasedRelation right = (AliasedRelation) join.getRight();
        Table tb = (Table) right.getRelation();
        String tableName = tb.getName().toString();
        tableList.add(tableName);
        if (join.getLeft() instanceof Join) {
            parseTableNameInJoin((Join) join.getLeft());
        } else {
            AliasedRelation left = (AliasedRelation) join.getLeft();
            tb = (Table) left.getRelation();
            tableName = tb.getName().toString();
            tableList.add(tableName);
        }
    }

    /**
     * 递归调用getTableName方法,
     * 若Join的Left仍然是Join类型,则继续向下调用
     *
     * @param union
     */
//    public static void parseTableNameFromUnion(Union union) {
//        AliasedRelation right= (AliasedRelation)union.getRight();
//        Table tb = (Table)right.getRelation();
//        String tableName = tb.getName().toString();
//        tableList.add(tableName);
//        if (union.getLeft() instanceof Join){
//            parseTableNameInJoin((Join)union.getLeft());
//        }else{
//            AliasedRelation left= (AliasedRelation)union.getLeft();
//            tb = (Table)left.getRelation();
//            tableName = tb.getName().toString();
//            tableList.add(tableName);
//        }
//    }
}
