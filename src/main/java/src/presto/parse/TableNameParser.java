package src.presto.parse;

import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.tree.*;

import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

/**
 * Created by kirk on 2017/7/12.
 */
public class TableNameParser {
    //    public static HashSet<String> tableList = new HashSet<>();
    public static List<String> tableList = new ArrayList<>();
    public static List<String> withTableList = new ArrayList<>();

    public static void main(String[] args) {
        String sql = SQL.sql;

        parseWithTableBySQL(sql);
        parseTableBySQL(sql);
        for (String withTable :
                withTableList) {
            System.out.println(withTable);
        }
        System.out.println("\n======================\n");
        for (String table :
                tableList) {
            System.out.println(table);
        }
    }

    /**
     * 从SQL中获取所有自定义表的List
     *
     * @param sql
     */
    private static List<String> parseWithTableBySQL(String sql) {
        SqlParser parser = new SqlParser();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        if (query != null) {
            Optional<With> with = query.getWith();
            parseWithTable(with);
        }
        return withTableList;
    }

    /**
     * 从With中获取所有自定义表的List
     *
     * @param with
     */
    private static void parseWithTable(Optional<With> with) {
        if (with.isPresent()) {
            List<WithQuery> queries = with.get().getQueries();
            for (WithQuery withQuery : queries) {
                withTableList.add(withQuery.getName());
            }
        }
    }

    /**
     * 从SQL中获取表名
     * 从SQL中解析出 Query,Union,QuerySpecification,With
     *
     * @param sql
     * @return
     */
    private static List<String> parseTableBySQL(String sql) {
        SqlParser parser = new SqlParser();
        Query query = parser.createStatement(sql) instanceof Query ? (Query) parser.createStatement(sql) : null;
        if (query != null) {
            Union union = query.getQueryBody() instanceof Union ? (Union) query.getQueryBody() : null;
            QuerySpecification querySpecification = query.getQueryBody() instanceof QuerySpecification ? ((QuerySpecification) query.getQueryBody()) : null;
            Optional<With> with = query.getWith();
            if (with != null) parseTableNameInWith(with);
            if (querySpecification != null) {
                Optional<Relation> from = querySpecification.getFrom();
                if (from != null) parseTableNameInFrom(from);
            }
            if (union != null) {
                parseTableNameInUnion(union);
            }
        }
        return tableList;
    }

    private static void parseTableNameInWith(Optional<With> with) {
        if (with.isPresent()) {
            List<WithQuery> queries = with.get().getQueries();
            for (WithQuery withQuery : queries) {
                QuerySpecification querySpecification = withQuery.getQuery().getQueryBody() instanceof QuerySpecification ? ((QuerySpecification) withQuery.getQuery().getQueryBody()) : null;
                Union union = withQuery.getQuery().getQueryBody() instanceof Union ? ((Union) withQuery.getQuery().getQueryBody()) : null;
                if (querySpecification != null) {
                    Optional<Relation> from = querySpecification.getFrom();
                    parseTableNameInFrom(from);
                }
                if (union != null) {
                    parseTableNameInUnion(union);
                }
            }
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
     * 递归调用方法parseTableNameInJoin,
     * 若Join的Left仍然是Join类型,则继续向下调用
     *
     * @param join
     */
    public static void parseTableNameInJoin(Join join) {
        AliasedRelation right = join.getRight() instanceof AliasedRelation ? (AliasedRelation) join.getRight() : null;
        TableSubquery tableSubquery = join.getRight() instanceof TableSubquery ? (TableSubquery) join.getRight() : null;
        if (right != null) {
            Table tb = right.getRelation() instanceof Table ? (Table) right.getRelation() : null;
            TableSubquery subqueryRalation = right.getRelation() instanceof TableSubquery ? (TableSubquery) right.getRelation() : null;
            if (subqueryRalation != null) {
                parseTableInSubQuery(subqueryRalation);
            }
            if (tableSubquery != null) {
                parseTableInSubQuery(tableSubquery);
            }
            if (tb != null) {
                String tableName = tb.getName().toString();
                tableList.add(tableName);
            }
            //如果Left仍然是Join类型,则递归调用
            Join joinLeft = join.getLeft() instanceof Join ? (Join) join.getLeft() : null;
            TableSubquery subqueryLeft = join.getLeft() instanceof TableSubquery ? (TableSubquery) join.getLeft() : null;
            if (subqueryLeft != null) {
                parseTableInSubQuery(subqueryLeft);
            }
            if (joinLeft != null) {
                parseTableNameInJoin(joinLeft);
                //否则从取出Right中取出TableName
            } else {
                AliasedRelation relationLeft = (AliasedRelation) join.getLeft();
                tb = (Table) relationLeft.getRelation();
                String tableName = tb.getName().toString();
                tableList.add(tableName);
            }

        }
    }


    /**
     * 递归调用parseTableNameFromUnion,
     * 若Union的下层仍然是Union类型,则继续向下调用
     *
     * @param union
     */
    public static void parseTableNameInUnion(Union union) {
        QuerySpecification querySpecification = (QuerySpecification) union.getRelations().get(1);
        Optional<Relation> from = querySpecification.getFrom();
        parseTableNameInFrom(from);
        if (union.getRelations().get(0) instanceof Union) {
            Union nextUnion = (Union) union.getRelations().get(0);
            parseTableNameInUnion(nextUnion);
        }
    }

    /**
     * 处理子查询
     *
     * @param tableSubquery
     */
    private static void parseTableInSubQuery(TableSubquery tableSubquery) {
        Query query = tableSubquery.getQuery();
        if (query != null) {
            QuerySpecification querySpecification = query.getQueryBody() instanceof QuerySpecification ? ((QuerySpecification) query.getQueryBody()) : null;
            if (querySpecification != null) {
                Optional<Relation> from = querySpecification.getFrom();
                parseTableNameInFrom(from);
            }
        }
    }
}
