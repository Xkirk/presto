package src.presto.utils;

import com.facebook.presto.Session;
import com.facebook.presto.execution.QueryIdGenerator;
import com.facebook.presto.metadata.Metadata;
import com.facebook.presto.metadata.SessionPropertyManager;
import com.facebook.presto.spi.security.Identity;
import com.facebook.presto.spi.type.Type;
import com.facebook.presto.sql.analyzer.ExpressionAnalysis;
import com.facebook.presto.sql.analyzer.FeaturesConfig;
import com.facebook.presto.sql.gen.ExpressionCompiler;
import com.facebook.presto.sql.parser.SqlParser;
import com.facebook.presto.sql.planner.Symbol;
import com.facebook.presto.sql.planner.SymbolToInputRewriter;
import com.facebook.presto.sql.relational.RowExpression;
import com.facebook.presto.sql.tree.*;
import com.facebook.presto.testing.LocalQueryRunner;
import com.facebook.presto.testing.MaterializedResult;
import com.google.common.collect.ImmutableList;
import com.google.common.collect.ImmutableMap;
import com.google.common.collect.Iterables;

import java.util.*;

import static com.facebook.presto.metadata.FunctionKind.SCALAR;
import static com.facebook.presto.spi.type.BigintType.BIGINT;
import static com.facebook.presto.spi.type.BooleanType.BOOLEAN;
import static com.facebook.presto.spi.type.DoubleType.DOUBLE;
import static com.facebook.presto.spi.type.IntegerType.INTEGER;
import static com.facebook.presto.spi.type.TimeZoneKey.UTC_KEY;
import static com.facebook.presto.spi.type.TimestampWithTimeZoneType.TIMESTAMP_WITH_TIME_ZONE;
import static com.facebook.presto.spi.type.VarbinaryType.VARBINARY;
import static com.facebook.presto.spi.type.VarcharType.VARCHAR;
import static com.facebook.presto.sql.ExpressionUtils.rewriteIdentifiersToSymbolReferences;
import static com.facebook.presto.sql.analyzer.ExpressionAnalyzer.analyzeExpressionsWithSymbols;
import static com.facebook.presto.sql.analyzer.ExpressionAnalyzer.getExpressionTypesFromInput;
import static com.facebook.presto.sql.planner.optimizations.CanonicalizeExpressions.canonicalizeExpression;
import static com.facebook.presto.sql.relational.SqlToRowExpressionTranslator.translate;
import static java.util.Locale.ENGLISH;
import static java.util.Objects.requireNonNull;

public class DateFormatFncParser {

    private Session session;
    private static final SqlParser SQL_PARSER = new SqlParser();
    private final LocalQueryRunner runner;
    private final Metadata metadata;
    private final ExpressionCompiler compiler;
    private static final Map<Symbol, Integer> INPUT_MAPPING = ImmutableMap.<Symbol, Integer>builder()
            .put(new Symbol("bound_long"), 0)
            .put(new Symbol("bound_string"), 1)
            .put(new Symbol("bound_double"), 2)
            .put(new Symbol("bound_boolean"), 3)
            .put(new Symbol("bound_timestamp"), 4)
            .put(new Symbol("bound_pattern"), 5)
            .put(new Symbol("bound_null_string"), 6)
            .put(new Symbol("bound_timestamp_with_timezone"), 7)
            .put(new Symbol("bound_binary_literal"), 8)
            .put(new Symbol("bound_integer"), 9)
            .build();


    public DateFormatFncParser() {
        this.session = requireNonNull(getTestSession(), "session is null");
        runner = new LocalQueryRunner(session, new FeaturesConfig());
        metadata = runner.getMetadata();
        compiler = runner.getExpressionCompiler();
    }

//    public static void main(String[] args) {
//        System.out.println("xx");
//        new DateFormatFncParser().dateFucParser();
//    }

    public String dateFucParser(String function) {
        MaterializedResult result = runner.execute("SELECT " + function);
        return (String) Iterables.getOnlyElement(result.getMaterializedRows()).getField(0);
    }

    private List<Object> executeProjectionWithAll(String projection, Session session, ExpressionCompiler compiler) {
        requireNonNull(projection, "projection is null");
        projection = "date_format(date_parse('20170809','%Y%m%d')- interval '1' day,'%Y%m%d')";
        MaterializedResult result = runner.execute("SELECT " + projection);
        List<Object> results = new ArrayList<>();
        System.out.println(result);
        //
        // If the projection does not need bound values, execute query using full engine


        return results;
    }

    private RowExpression toRowExpression(Expression projection, Map<NodeRef<Expression>, Type> expressionTypes) {
        return translate(projection, SCALAR, expressionTypes, metadata.getFunctionRegistry(), metadata.getTypeManager(), session, false);
    }

    private static Session getTestSession() {
        return sessionBuilder().setCatalog("tpch").setSchema("tiny").build();
    }


    public static Expression createExpression(String expression, Metadata metadata, Map<Symbol, Type> symbolTypes) {
        return null;
    }

    private static Session.SessionBuilder sessionBuilder() {
        return Session.builder(new SessionPropertyManager())
                .setQueryId(new QueryIdGenerator().createNextQueryId())
                .setIdentity(new Identity("user", Optional.empty()))
                .setSource("test")
                .setCatalog("catalog")
                .setSchema("schema")
                .setTimeZoneKey(UTC_KEY)
                .setLocale(ENGLISH)
                .setRemoteUserAddress("address")
                .setUserAgent("agent");
    }
}
