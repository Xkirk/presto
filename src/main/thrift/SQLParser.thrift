namespace java src.thrift
namespace php src.thrift
service SQLParser{
list<string> getSelectItems(1:string sql)
list<string> getTables(1:string sql)
string limitVerify(1:string sql)
map<string,string> dateScope(1:string sql)
list<string> scope(1:string sql)
}