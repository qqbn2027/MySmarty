<?php

namespace library\mysmarty;

use config\Database;
use PDO;
use PDOStatement;

/**
 * 数据库连接
 */
class Model extends Container
{
    // 默认数据库连接库名
    protected string $database = '';
    // 表名
    protected string $table = '';
    // 连接对象
    private ?PDO $dbh = null;
    // 是否允许初始化
    private bool $flush = true;
    // 查询字段
    private string $mField = '*';
    private array $mWhere = [];
    private array $mWhereArgs = [];
    private string $mSql = '';
    private string $mOrder = '';
    private string $mLimit = '';
    private string $mGroup = '';
    private string $mHaving = '';
    private array $mJoin = [];
    private array $mAllowField = [];
    private string $mErrorCode = '';
    private array $mErrorInfo = [];
    private string $mDistinct = '';
    private string $mExtra = '';
    private array $mUnion = [];
    private string $mLock = '';
    private string $mForceIndex = '';
    private string $mUsing = '';
    private bool $mBooleanMode = false;

    /**
     * 受影响的行数
     * @var int
     */
    private int $mRowCount = 0;

    /**
     * 初始化变量
     */
    public function initVar()
    {
        if ($this->flush) {
            $this->mField = '*';
            $this->mWhere = [];
            $this->mWhereArgs = [];
            $this->mOrder = '';
            $this->mLimit = '';
            $this->mGroup = '';
            $this->mHaving = '';
            $this->mJoin = [];
            $this->mAllowField = [];
            $this->mDistinct = '';
            $this->mExtra = '';
            $this->mUnion = [];
            $this->mLock = '';
            $this->mForceIndex = '';
            $this->mUsing = '';
            $this->mRowCount = 0;
            $this->mBooleanMode = false;
        }
    }

    public function __construct()
    {
        $this->_initialize();
    }

    /**
     * 初始化
     * @return void
     */
    public function _initialize()
    {
        $this->database = $this->database ?: Database::DATABASE;
        if (empty($this->table) && static::class !== self::class) {
            $class = substr(static::class, strrpos(static::class, '\\') + 1);
            $this->table = toDivideName($class);
        }
    }

    /**
     * 连接数据库
     */
    private function connect()
    {
        static $dbh;
        if (empty($dbh)) {
            $host = Database::HOST;
            $user = Database::USER;
            $password = Database::PASSWORD;
            $port = Database::PORT;
            $driverOptions = Database::OPTIONS;
            $charset = Database::CHARSET;
            if (!empty($port)) {
                $dbh = PdoConnection::connectByHost($host, $this->database, $port, $user, $password, $charset, $driverOptions);
            } else {
                $dbh = PdoConnection::connectByUnixSocket($host, $this->database, $user, $password, $charset, $driverOptions);
            }
        }
        $this->dbh = $dbh;
    }

    /**
     * 去除mysql中的转义字符
     * @param string $str
     * @return string
     */
    private function trimEscapeChar(string $str): string
    {
        return myTrim(str_ireplace('`', '', $str));
    }

    /**
     * 查询字段，需要自行处理特殊字段
     * @param string $field 要查询的字段，多个逗号分隔
     * @return static
     */
    public function field(string $field): static
    {
        if (empty($this->mField) || '*' === $this->mField) {
            $this->mField = $field;
        } else {
            $this->mField .= ',' . $field;
        }
        return $this;
    }

    /**
     * where and条件
     * @param string|array $field 字段
     * @param mixed $value 值
     * @param string $operate 操作符
     * @return static
     */
    public function where(array|string $field = '', mixed $value = null, string $operate = '='): static
    {
        return $this->whereMap($field, $value, $operate);
    }

    /**
     * where or 条件
     * @param string|array $field 字段
     * @param mixed $value 值
     * @param string $operate 操作符
     * @return static
     */
    public function whereOr(string|array $field = '', mixed $value = null, string $operate = '='): static
    {
        return $this->whereMap($field, $value, $operate, 'OR');
    }

    /**
     * where条件
     * @param string|array $field 字段或条件数组
     * @param mixed $value 查询值
     * @param string $operate 查询操作符
     * @param string $union 连接符
     * @return static
     */
    public function whereMap(array|string $field = '', mixed $value = null, string $operate = '=', string $union = 'AND'): static
    {
        $this->mWhere = array_merge($this->mWhere, $this->getWhereMap($field, $value, $operate, $union));
        return $this;
    }

    /**
     * 组成where条件
     * @param string|array $field 字段或条件数组
     * @param mixed $value 查询值
     * @param string $operate 查询操作符
     * @param string $union 连接符
     * @return array
     */
    private function getWhereMap(array|string $field = '', mixed $value = null, string $operate = '=', string $union = 'AND'): array
    {
        $mWhere = [];
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                if (is_string($k) && is_array($v)) {
                    $mWhere[] = match (count($v)) {
                        1 => [
                            $k,
                            $v[0],
                            $operate,
                            $union
                        ],
                        2 => [
                            $k,
                            $v[0],
                            $v[1],
                            $union
                        ],
                        default => [
                            $k,
                            $v[0],
                            $v[1],
                            strtoupper($v[2])
                        ],
                    };
                } else {
                    if (is_string($k)) {
                        $mWhere[] = [
                            $k,
                            $v,
                            $operate,
                            $union
                        ];
                    } else {
                        if (is_string($v)) {
                            $mWhere = array_merge($mWhere, $this->getWhereMap($v));
                        } else {
                            $mWhere[] = [
                                $k,
                                $v,
                                $operate,
                                $union
                            ];
                        }
                    }
                }
            }
        } else {
            $mWhere[] = [
                $field,
                $value,
                $operate,
                $union
            ];
        }
        return $mWhere;
    }

    /**
     * 排序
     * @param string $field 排序字段
     * @param string $order 排序规则，desc 降序，asc 升序
     * @return static
     */
    public function order(string $field, string $order = ''): static
    {
        if (empty($order)) {
            $this->mOrder = ' ORDER BY ' . $field;
        } else {
            $this->mOrder = ' ORDER BY ' . $field . ' ' . $order;
        }
        return $this;
    }

    /**
     * 限制
     * @param int $offset 偏移量，从0开始
     * @param int $size 大小
     * @return static
     */
    public function limit(int $offset, int $size = 0): static
    {
        if ($offset < 0) {
            $offset = 0;
        }
        if (empty($size)) {
            $this->mLimit = ' LIMIT ' . $offset;
        } else {
            $this->mLimit = ' LIMIT ' . $offset . ',' . $size;
        }
        return $this;
    }

    /**
     * 分页查询
     * @param int $page 第几页，从1开始
     * @param int $size 分页大小
     * @return static
     */
    public function page(int $page, int $size = 10): static
    {
        $offset = ($page - 1) * $size;
        return $this->limit($offset, $size);
    }

    /**
     * 分组
     * @param string $field 字段
     * @return static
     */
    public function group(string $field): static
    {
        $this->mGroup = ' GROUP BY ' . $field;
        return $this;
    }

    /**
     * 分组
     * @param string $condition 条件
     * @return static
     */
    public function having(string $condition): static
    {
        $this->mHaving = ' HAVING ' . $condition;
        return $this;
    }

    /**
     * join查询
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @param string $type 连接类型（left join，right join,inner join）
     * @return static
     */
    public function join(string $table, string $condition, string $type = 'left join'): static
    {
        $this->mJoin[] = [
            $table,
            $condition,
            $type
        ];
        return $this;
    }

    /**
     * 左连接
     *
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function leftJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition);
    }

    /**
     * 右连接
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function rightJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'right join');
    }

    /**
     * 内连接
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function innerJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'inner join');
    }

    /**
     * 去重查询
     * @param bool $distinct
     * @return static
     */
    public function distinct(bool $distinct): static
    {
        if ($distinct) {
            $this->mDistinct = ' DISTINCT ';
        } else {
            $this->mDistinct = '';
        }
        return $this;
    }

    /**
     * 查询
     * @return array
     */
    public function select(): array
    {
        return $this->query($this->dealSelectSql(), $this->mWhereArgs);
    }

    /**
     * 处理查询sql语句
     * @return string
     */
    private function dealSelectSql(): string
    {
        $where = $this->dealWhere();
        $join = $this->dealJoin();
        $union = $this->dealUnion();
        $table = $this->getFormatTable();
        $sql = 'SELECT%DISTINCT%%EXTRA% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%';
        return str_replace(
            [
                '%TABLE%',
                '%DISTINCT%',
                '%EXTRA%',
                '%FIELD%',
                '%JOIN%',
                '%WHERE%',
                '%GROUP%',
                '%HAVING%',
                '%ORDER%',
                '%LIMIT%',
                '%UNION%',
                '%LOCK%',
                '%FORCE%'
            ],
            [
                $table,
                $this->mDistinct,
                $this->mExtra,
                $this->mField,
                $join,
                $where,
                $this->mGroup,
                $this->mHaving,
                $this->mOrder,
                $this->mLimit,
                $union,
                $this->mLock,
                $this->mForceIndex,
            ],
            $sql
        );
    }

    /**
     * 处理join条件
     * @return string
     */
    private function dealJoin(): string
    {
        $join = '';
        if (!empty($this->mJoin)) {
            foreach ($this->mJoin as $v) {
                $join .= ' ' . $v[2] . ' ' . $v[0] . ' on ' . $v[1];
            }
        }
        return $join;
    }

    /**
     * 获取sql语句
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->mSql;
    }

    /**
     * 处理where条件
     */
    private function dealWhere(): string
    {
        $where = $this->getWhereSql($this->mWhere);
        if (empty($where)) {
            return '';
        }
        return ' where ' . $where;
    }

    /**
     * 将数组格式的查询条件转为where查询条件字符串
     * @param array $data
     * @return string
     */
    public function getWhereSql(array $data): string
    {
        $where = '';
        foreach ($data as $v) {
            if (is_int($v[0])) {
                if (!empty($v[1])) {
                    $map = $this->getWhereMap($v[1]);
                    // 取第一个为连接符
                    $v[3] = $map[0][3] ?? 'AND';
                    $whereTmp = '(' . $this->getWhereSql($map) . ')';
                } else {
                    continue;
                }
            } else {
                $whereTmp = $this->buildWhere($v[0], $v[1] ?? null, $v[2] ?? '=');
            }
            if (empty($where)) {
                $where = $whereTmp;
            } else {
                $where .= ' ' . $v[3] . ' ' . $whereTmp;
            }
        }
        return $where;
    }

    /**
     * 根据相关信息组成where查询条件
     * @param string $field 查询字段
     * @param mixed $value 查询字段的值
     * @param string $operate 查询操作符
     * @return string
     */
    private function buildWhere(string $field, mixed $value, string $operate): string
    {
        if (is_null($value)) {
            return $field;
        }
        switch (strtolower($operate)) {
            case 'in':
                $value = $this->stringToArr($value);
                $sql = $field . ' IN (';
                $sql .= str_repeat('?,', count($value));
                $sql = rtrim($sql, ',') . ')';
                $this->mWhereArgs = array_merge($this->mWhereArgs, $value);
                break;
            case 'not in':
                $value = $this->stringToArr($value);
                $sql = $field . ' NOT IN (';
                $sql .= str_repeat('?,', count($value));
                $sql = rtrim($sql, ',') . ')';
                $this->mWhereArgs = array_merge($this->mWhereArgs, $value);
                break;
            case 'between':
                $value = $this->stringToArr($value);
                $sql = $field . ' BETWEEN ? AND ?';
                $this->mWhereArgs[] = $value[0] ?? '';
                $this->mWhereArgs[] = $value[1] ?? '';
                break;
            case 'not between':
                $value = $this->stringToArr($value);
                $sql = $field . ' NOT BETWEEN ? AND ?';
                $this->mWhereArgs[] = $value[0] ?? '';
                $this->mWhereArgs[] = $value[1] ?? '';
                break;
            case 'not null':
                $sql = $field . ' IS NOT NULL';
                break;
            case 'null':
                $sql = $field . ' IS NULL';
                break;
            case 'exists':
                $sql = 'EXISTS (' . $value . ')';
                break;
            case 'not exists':
                $sql = 'NOT EXISTS (' . $value . ')';
                break;
            case 'find_in_set':
                $sql = 'FIND_IN_SET (?,' . $field . ')';
                if (is_array($value)) {
                    $this->mWhereArgs = array_merge($this->mWhereArgs, $value);
                } else {
                    $this->mWhereArgs[] = $value;
                }
                break;
            case 'match':
                $sql = 'MATCH (' . $field . ') AGAINST (? IN ' . ($this->mBooleanMode ? 'BOOLEAN MODE' : 'NATURAL LANGUAGE MODE') . ')';
                $this->mWhereArgs[] = $value;
                break;
            case '':
                // 空操作符
                $sql = $field;
                if (is_array($value)) {
                    $this->mWhereArgs = array_merge($this->mWhereArgs, $value);
                } else {
                    $this->mWhereArgs[] = $value;
                }
                break;
            default:
                $sql = $field . ' ' . $operate . ' ?';
                if (is_array($value)) {
                    $this->mWhereArgs = array_merge($this->mWhereArgs, $value);
                } else {
                    $this->mWhereArgs[] = $value;
                }
        }
        return $sql;
    }

    /**
     * 将一个未知数据转为数组，只用来将 , 分隔的字符串转数组
     * @param mixed $str
     * @return array
     */
    private function stringToArr(mixed $str): array
    {
        if (!is_array($str)) {
            return explode(',', $str);
        }
        return $str;
    }

    /**
     * 格式化字段
     * @param string $field 待查询的字段
     * @return string
     */
    public function formatField(string $field): string
    {
        if ($field === '*' || empty($field)) {
            return '*';
        }
        if (str_contains($field, ',')) {
            $fieldArr = explode(',', $field);
        } else {
            $fieldArr = [$field];
        }
        $result = [];
        foreach ($fieldArr as $v) {
            $v = $this->trimEscapeChar($v);
            if (str_contains($v, '.')) {
                $tmp = explode('.', $v);
                if ('*' === $tmp[1]) {
                    $f = '`' . $tmp[0] . '`.*';
                } else {
                    $f = '`' . $tmp[0] . '`.`' . $tmp[1] . '`';
                }
            } else {
                $f = '`' . $v . '`';
            }
            $result[] = $f;
        }
        return implode(',', $result);
    }

    /**
     * 统计数据记录总数
     * @param string $field 字段
     * @return int
     */
    public function count(string $field = '*'): int
    {
        $num = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'count(' . $field . ') my_smarty_num';
        $isGroup = !empty($this->mGroup);
        $result = $this->select();
        if ($isGroup) {
            $num = count($result);
        } else {
            foreach ($result as $item) {
                $num += $item['my_smarty_num'];
            }
        }
        return (int)$num;
    }

    /**
     * 查找最大值
     * @param string $field 字段
     * @return int|float
     */
    public function max(string $field): int|float
    {
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'max(' . $field . ') my_smarty_num';
        $result = $this->select();
        return $result[0]['my_smarty_num'] ?: 0;
    }

    /**
     * 查找最小值
     * @param string $field 字段
     * @return int|float
     */
    public function min(string $field): float|int
    {
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'min(' . $field . ') my_smarty_num';
        $result = $this->select();
        return $result[0]['my_smarty_num'] ?: 0;
    }

    /**
     * 查找平均值
     * @param string $field 字段
     * @return int|float
     */
    public function avg(string $field): float|int
    {
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'avg(' . $field . ') my_smarty_num';
        $result = $this->select();
        return $result[0]['my_smarty_num'] ?: 0;
    }

    /**
     * 查找总和
     * @param string $field 字段
     * @return int|float
     */
    public function sum(string $field): float|int
    {
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'sum(' . $field . ') my_smarty_num';
        $result = $this->select();
        return $result[0]['my_smarty_num'] ?: 0;
    }

    /**
     * 查找一条数据
     * @return array
     */
    public function find(): array
    {
        $result = $this->limit(1)->select();
        return $result[0] ?? [];
    }

    /**
     * 空查询
     * @param string $field 字段
     * @return static
     */
    public function null(string $field): static
    {
        $this->where($field . ' is null');
        return $this;
    }

    /**
     * 非空查询
     * @param string $field 字段
     * @return static
     */
    public function notNull(string $field): static
    {
        $this->where($field . ' is not null');
        return $this;
    }

    /**
     * 原生查询
     * @param string $sql 原生sql语句
     * @param array $mWhereArgs 绑定的参数
     * @return array
     */
    public function query(string $sql, array $mWhereArgs = []): array
    {
        $data = [];
        $result = $this->preExec($sql, $mWhereArgs);
        if ($result) {
            while (true) {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    break;
                }
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * @param string $sql 原生sql语句
     * @param array $mWhereArgs 绑定的参数
     * @return int|bool
     */
    public function execute(string $sql, array $mWhereArgs = []): int|bool
    {
        $isInsert = false;
        if (0 === stripos($sql, 'insert')) {
            $isInsert = true;
        }
        $result = $this->preExec($sql, $mWhereArgs);
        if (false === $result) {
            return false;
        }
        $num = $result->rowCount();
        $this->mRowCount = $num;
        if ($isInsert && !empty($this->dbh->lastInsertId())) {
            $num = $this->dbh->lastInsertId();
        }
        return (int)$num;
    }

    /**
     * 统一执行sql语句前的执行
     * @param string $sql
     * @param array $mWhereArgs
     * @return PDOStatement|bool
     */
    private function preExec(string $sql, array $mWhereArgs = []): bool|PDOStatement
    {
        $this->connect();
        $sql = myTrim($sql);
        $result = $this->dbh->prepare($sql);
        $res = $result->execute($mWhereArgs);
        $this->initVar();
        $this->mSql = $sql . ' 绑定的参数：' . json_encode($mWhereArgs, JSON_UNESCAPED_UNICODE);
        $this->mErrorCode = $result->errorCode();
        $this->mErrorInfo = $result->errorInfo();
        if (!$res) {
            return false;
        }
        return $result;
    }

    /**
     * 相等查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function eq(string $field, string $value): static
    {
        $this->where($field, $value);
        return $this;
    }

    /**
     * 不相等查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function neq(string $field, string $value): static
    {
        $this->where($field, $value, '!=');
        return $this;
    }

    /**
     * 大于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function gt(string $field, string $value): static
    {
        $this->where($field, $value, '>');
        return $this;
    }

    /**
     * 大于或等于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function egt(string $field, string $value): static
    {
        $this->where($field, $value, '>=');
        return $this;
    }

    /**
     * 小于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function lt(string $field, string $value): static
    {
        $this->where($field, $value, '<');
        return $this;
    }

    /**
     * 小于或等于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function elt(string $field, string $value): static
    {
        $this->where($field, $value, '<=');
        return $this;
    }

    /**
     * 相似查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function like(string $field, string $value): static
    {
        $this->where($field, $value, 'like');
        return $this;
    }

    /**
     * 不相似查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function notLike(string $field, string $value): static
    {
        $this->where($field, $value, 'not like');
        return $this;
    }

    /**
     * 区间查询
     * @param string $field 字段
     * @param string $startValue 开始值
     * @param string $endValue 结束值
     * @return static
     */
    public function between(string $field, string $startValue, string $endValue): static
    {
        $this->where($field, [$startValue, $endValue], 'between');
        return $this;
    }

    /**
     * 不在区间查询
     * @param string $field 字段
     * @param string $startValue 开始值
     * @param string $endValue 结束值
     * @return static
     */
    public function notBetween(string $field, string $startValue, string $endValue): static
    {
        $this->where($field, [$startValue, $endValue], 'not between');
        return $this;
    }

    /**
     * in查询
     * @param string $field 字段
     * @param string|array $value 值，逗号分割，或数组
     * @return static
     */
    public function in(string $field, array|string $value): static
    {
        $this->where($field, $value, 'in');
        return $this;
    }

    /**
     * not in查询
     * @param string $field 字段
     * @param string|array $value 值，逗号分割，或数组
     * @return static
     */
    public function notIn(string $field, array|string $value): static
    {
        $this->where($field, $value, 'not in');
        return $this;
    }

    /**
     * find_in_set查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function findInSet(string $field, string $value): static
    {
        $this->where($field, $value, 'find_in_set');
        return $this;
    }

    /**
     * 过滤字段
     * @param string|array|bool $field 过滤字段
     * @return static
     */
    public function allowField(array|string|bool $field): static
    {
        if (is_array($field)) {
            $this->mAllowField = $field;
        } else if ($field === true) {
            // 仅允许数据库中已有的字段填充（不会去掉主键id）
            $obj = clone $this;
            $this->mAllowField = $obj->getTableInfo();
        } else if (is_string($field)) {
            $this->mAllowField = explode(',', $field);
        }
        return $this;
    }

    /**
     * 添加数据
     * @param array $data
     * @param bool $isReplace 是否替换
     * @return int|bool
     */
    public function add(array $data, bool $isReplace = false): int|bool
    {
        $keyArr = $this->getFields($data);
        if (empty($keyArr)) {
            return 0;
        }
        $this->formatFields($keyArr);
        $valueArr = array_values($data);
        $pArr = array_fill(0, count($valueArr), '?');
        $insertSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) VALUES (%DATA%)';
        $sql = str_replace(
            [
                '%INSERT%',
                '%TABLE%',
                '%EXTRA%',
                '%FIELD%',
                '%DATA%'
            ],
            [
                $isReplace ? 'REPLACE' : 'INSERT',
                $this->getFormatTable(),
                $this->mExtra,
                implode(',', $keyArr),
                implode(',', $pArr)
            ],
            $insertSql
        );
        return $this->execute($sql, $valueArr);
    }

    /**
     * 从添加的数据中获取到允许添加的数据库字段
     * @param array $data
     * @return array
     */
    private function getFields(array &$data): array
    {
        if (!empty($this->mAllowField)) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $this->mAllowField, true)) {
                    unset($data[$k]);
                }
            }
        }
        if (empty($data)) {
            return [];
        }
        return array_keys($data);
    }

    /**
     * 格式化表名
     * @param string $table
     * @return string
     */
    private function formatTable(string $table): string
    {
        $table = $this->trimEscapeChar($table);
        if (str_contains($table, '.')) {
            $tmp = explode('.', $table);
            $table = '`' . $tmp[0] . '`.`' . $tmp[1] . '`';
        } else {
            $table = '`' . $table . '`';
        }
        return $table;
    }

    /**
     * 格式化字段数组
     * @param array|string $fields 字段数组
     */
    private function formatFields(array|string &$fields): void
    {
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $v = $this->formatField($v);
                $fields[$k] = $v;
            }
        } else {
            $fields = $this->formatField($fields);
        }
    }

    /**
     * 添加或更新数据
     * @param array $data
     * @return int
     */
    public function replace(array $data): int
    {
        return $this->add($data, true);
    }

    /**
     * 更新语句
     * @param array $data
     * @param bool $isBindArgs 是否使用绑定参数
     * @return int|bool
     */
    public function update(array $data, bool $isBindArgs = true): int|bool
    {
        $keyArr = $this->getFields($data);
        if (empty($keyArr)) {
            return false;
        }
        $this->formatFields($keyArr);
        $valueArr = $isBindArgs ? array_values($data) : [];
        $keyStr = '';
        foreach ($keyArr as $v) {
            $tmp = '?';
            if (!$isBindArgs) {
                $tmp = $data[$v] ?? $data[str_ireplace('`', '', $v)];
            }
            if (empty($keyStr)) {
                $keyStr = $v . ' = ' . $tmp;
            } else {
                $keyStr .= ',' . $v . ' = ' . $tmp;
            }
        }
        $updateSql = 'UPDATE%EXTRA% %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%';
        $sql = str_replace(
            [
                '%TABLE%',
                '%EXTRA%',
                '%SET%',
                '%JOIN%',
                '%WHERE%',
                '%ORDER%',
                '%LIMIT%',
                '%LOCK%'
            ],
            [
                $this->getFormatTable(),
                $this->mExtra,
                $keyStr,
                $this->dealJoin(),
                $this->dealWhere(),
                $this->mOrder,
                $this->mLimit,
                $this->mLock
            ],
            $updateSql);
        return $this->execute($sql, array_merge($valueArr, $this->mWhereArgs));
    }

    /**
     * 查找表中的主键字段
     * @return string|bool
     */
    private function getPkName(): bool|string
    {
        $obj = clone $this;
        $data = $obj->getTableInfo($this->table, '');
        foreach ($data as $v) {
            if ($v['Key'] === 'PRI') {
                return $v['Field'];
            }
        }
        return false;
    }

    /**
     * 删除数据
     * @param int|bool|array $id 主键id
     * @return int|bool
     */
    public function delete(int|bool|array $id = false): int|bool
    {
        if (false !== $id) {
            $pkName = $this->getPkName();
            if (empty($pkName)) {
                return 0;
            }
            if (is_array($id) || str_contains($id, ',')) {
                $this->in($pkName, $id);
            } else {
                $this->eq($pkName, $id);
            }
        }
        $deleteSql = 'DELETE%EXTRA% FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%';
        $sql = str_replace(
            [
                '%TABLE%',
                '%EXTRA%',
                '%USING%',
                '%JOIN%',
                '%WHERE%',
                '%ORDER%',
                '%LIMIT%',
                '%LOCK%'
            ],
            [
                $this->getFormatTable(),
                $this->mExtra,
                $this->mUsing,
                $this->dealJoin(),
                $this->dealWhere(),
                $this->mOrder,
                $this->mLimit,
                $this->mLock
            ],
            $deleteSql
        );
        return $this->execute($sql, $this->mWhereArgs);
    }

    /**
     * 开启事务
     */
    public function startTrans(): void
    {
        $this->connect();
        if (!$this->dbh->inTransaction()) {
            $this->dbh->beginTransaction();
        }
    }

    /**
     * 提交事务
     */
    public function commit(): void
    {
        $this->connect();
        if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
        }
    }

    /**
     * 回滚事务
     */
    public function rollback(): void
    {
        $this->connect();
        if ($this->dbh->inTransaction()) {
            $this->dbh->rollBack();
        }
    }

    /**
     * 更新字段值
     * @param string $field 字段
     * @param string $value 值
     * @return bool|int
     */
    public function setField(string $field, string $value): bool|int
    {
        return $this->update([
            $field => $value
        ]);
    }

    /**
     * 自增
     * @param string $field 自增字段
     * @param int|float $num 增值
     * @return  bool|int
     */
    public function setInc(string $field, int|float $num = 1): bool|int
    {
        return $this->update([
            $field => $field . '+' . $num
        ], false);
    }

    /**
     * 自减
     * @param string $field 自减字段
     * @param int|float $num 减值
     * @return  bool|int
     */
    public function setDec(string $field, int|float $num = 1): bool|int
    {
        return $this->update([
            $field => $field . '-' . $num
        ], false);
    }

    /**
     * 添加多条数据
     * @param array $datas 多条数据
     * @param bool $isReplace 是否为替换语句
     * @return int|bool
     */
    public function addAll(array $datas, bool $isReplace = false): int|bool
    {
        if (empty($datas)) {
            return 0;
        }
        if (count($datas) === count($datas, true)) {
            return 0;
        }
        $keyArr = $this->getFields($datas[0]);
        if (empty($keyArr)) {
            return 0;
        }
        $valueArr = [];
        $str = '';
        foreach ($datas as $data) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $keyArr, true)) {
                    unset($data[$k]);
                }
            }
            $curData = array_values($data);
            $valueArr = array_merge($valueArr, $curData);
            $pArr = array_fill(0, count($curData), '?');
            if (empty($str)) {
                $str = '(' . implode(',', $pArr) . ')';
            } else {
                $str .= ',(' . implode(',', $pArr) . ')';
            }
        }
        $this->formatFields($keyArr);
        $insertAllSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) VALUES %DATA%';
        $sql = str_replace(
            [
                '%INSERT%',
                '%TABLE%',
                '%EXTRA%',
                '%FIELD%',
                '%DATA%'
            ],
            [
                $isReplace ? 'REPLACE' : 'INSERT',
                $this->getFormatTable(),
                $this->mExtra,
                implode(',', $keyArr),
                $str
            ],
            $insertAllSql
        );
        return $this->execute($sql, $valueArr);
    }

    /**
     * 关联查找
     * @param string $tableName 关联表名,其它库名，请使用 . 连接
     * @param string $foreignKey 关联表外键
     * @param string $primaryKey 本表主键
     * @param string $field 关联表需要查询的字段
     * @return static
     */
    public function with(string $tableName, string $foreignKey, string $primaryKey, string $field = ''): static
    {
        if (!empty($field)) {
            if ($this->mField === '*') {
                $this->mField = $this->table . '.' . '*';
            }
            $this->mField .= ',' . $field;
        }
        return $this->leftJoin($tableName, $this->table . '.' . $primaryKey . '=' . $tableName . '.' . $foreignKey);
    }

    /**
     * 获取分页数据
     * @param int $size 每页显示多少条
     * @param int|bool $limitTotalPage 限制总页，false则不限制
     * @param int|bool $limitPage 分页显示个数，false 不获取
     * @param string $varPage
     * @return array
     */
    public function paginate(int $size = 10, bool|int $limitTotalPage = false, int|bool $limitPage = 5, string $varPage = 'page'): array
    {
        $obj = clone $this;
        $count = $this->count();
        return $obj->paginateByCount($count, $size, $limitTotalPage, $limitPage, $varPage);
    }

    /**
     * 根据总数获取分页数据
     * @param int $count 数据总数
     * @param int $size 每页显示多少条
     * @param int|bool $limitTotalPage 限制总页，false则不限制
     * @param int|bool $limitPage 分页显示个数，false 不获取
     * @param string $varPage
     * @return array
     */
    public function paginateByCount(int $count, int $size = 10, int|bool $limitTotalPage = false, int|bool $limitPage = 5, string $varPage = 'page'): array
    {
        $result = Page::getInstance()->paginate($count, $size, $limitTotalPage, $limitPage, $varPage);
        $result['data'] = $this->page($result['curPage'], $result['size'])->select();
        return $result;
    }

    /**
     * 清空表数据
     * @param string $table
     * @return int|bool
     */
    public function truncate(string $table = ''): int|bool
    {
        $table = $table ?: $this->table;
        if (!empty($table)) {
            return $this->execute('TRUNCATE ' . $this->formatTable($table) . ';');
        }
        return false;
    }

    /**
     * 设置数据库
     * @param string $database 数据库名
     * @return static
     */
    public function setDatabase(string $database): static
    {
        $this->database = $database;
        return $this;
    }

    /**
     * 设置表
     * @param string $table 表名
     * @return static
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 获取错误码
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->mErrorCode;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorInfo(): string
    {
        $info = $this->mErrorInfo;
        if (empty($info)) {
            return '';
        }
        return '错误码：' . $info[0] . '，驱动错误码：' . $info[1] . '，错误信息：' . $info[2];
    }

    /**
     * 刷新权限
     * @return int|bool
     */
    public function flushPrivileges(): int|bool
    {
        return $this->execute('FLUSH PRIVILEGES');
    }

    /**
     * 获取当前执行数据库表名(格式化后)
     * @return string
     */
    public function getFormatTable(): string
    {
        $table = $this->trimEscapeChar($this->table);
        if (!str_contains($table, '.')) {
            $database = $this->trimEscapeChar($this->database);
            $table = '`' . $database . '`.`' . $table . '`';
        } else {
            $tmp = explode('.', $table);
            $table = '`' . $tmp[0] . '`.`' . $tmp[1] . '`';
        }
        return $table;
    }

    /**
     * 查询额外参数分析
     * @param string $extra
     * @return static
     */
    public function extra(string $extra): static
    {
        if (preg_match('/^[\w]+$/i', $extra)) {
            $this->mExtra = ' ' . strtoupper($extra);
        } else {
            $this->mExtra = '';
        }
        return $this;
    }

    /**
     * union查询
     * @param string|array $unionSql 多个或一个union语句
     * @param int $type 类型：0 union，1 union all，2 union distinct
     * @return static
     */
    public function union(string|array $unionSql, int $type = 0): static
    {
        if (is_string($unionSql)) {
            $unionSql = [$unionSql];
        }
        foreach ($unionSql as $sql) {
            $sql = match ($type) {
                0 => 'UNION ' . $sql,
                1 => 'UNION ALL ' . $sql,
                2 => 'UNION DISTINCT ' . $sql,
                default => $sql
            };
            $this->mUnion[] = $sql;
        }
        return $this;
    }

    /**
     * 解析 UNION 语句
     * @return string
     */
    private function dealUnion(): string
    {
        if (empty($this->mUnion)) {
            return '';
        }
        return ' ' . implode(' ', $this->mUnion);
    }

    /**
     * 设置锁机制
     * @param string|bool $lock
     * @return static
     */
    public function lock(string|bool $lock): static
    {
        if (is_bool($lock)) {
            $this->mLock = $lock ? 'FOR UPDATE' : '';
        } else if (is_string($lock) && !empty($lock)) {
            $this->mLock = myTrim($lock);
        } else {
            $this->mLock = '';
        }
        return $this;
    }

    /**
     * 强制使用的索引
     * @param string|array $index
     * @return static
     */
    public function forceIndex(string|array $index): static
    {
        if (empty($index)) {
            $this->mForceIndex = '';
        } else {
            if (is_array($index)) {
                $index = implode(',', $index);
            }
            $this->mForceIndex = sprintf(" FORCE INDEX ( %s ) ", $index);
        }
        return $this;
    }

    /**
     * 切换数据库
     * @param string $database 数据库名
     * @return static
     */
    public function changeDatabase(string $database): static
    {
        $this->execute('use `' . $database . '`');
        $this->database = $database;
        return $this;
    }

    /**
     * using
     * @param string $using
     * @return static
     */
    public function using(string $using): static
    {
        if (!empty($using)) {
            $this->mUsing = ' USING ' . $using;
        } else {
            $this->mUsing = '';
        }
        return $this;
    }

    /**
     * 是否允许初始化
     * @param bool $flush
     * @return static
     */
    public function setFlush(bool $flush): static
    {
        $this->flush = $flush;
        return $this;
    }

    /**
     * 获取当前操作的表
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 获取当前设置的数据库
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * 获取数据表的字段信息
     * @param string $table 表
     * @param string $field 信息字段
     * @return array
     */
    public function getTableInfo(string $table = '', string $field = 'Field'): array
    {
        if (empty($table)) {
            $table = $this->getFormatTable();
        }
        // 仅允许数据库中已有的字段填充（不会去掉主键id）
        $data = $this->query('desc ' . $table);
        if (empty($field)) {
            return $data;
        }
        return array_column($data, $field);
    }

    /**
     * 原生sql语句查询
     * @param string $whereSql 查询条件
     * @param array $bindings 绑定参数
     * @param string $union 查询连接符
     * @return static
     */
    public function whereRaw(string $whereSql, array $bindings = [], string $union = 'AND'): static
    {
        return $this->whereMap([
            $whereSql => [$bindings, '', $union]
        ]);
    }

    /**
     * 查询指定数据的某个字段的值
     * @param string $field 查询字段
     * @param string|null $defValue 默认值
     * @return string|null
     */
    public function value(string $field, mixed $defValue = null): string|null
    {
        $data = $this->field($field)->find();
        return $data[$field] ?? $defValue;
    }

    /**
     * 返回最后插入行的ID
     * @return bool|string
     */
    public function getLastInsertId(): bool|string
    {
        if (is_null($this->dbh)) {
            return false;
        }
        return $this->dbh->lastInsertId();
    }

    /**
     * 返回受影响的行数
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->mRowCount;
    }

    /**
     * 全文搜索
     * @param string $field 搜索的字段，多个英文逗号分隔
     * @param string $search 待搜索的值
     * @param bool $booleanMode 是否为 BOOLEAN MODE，否则为 NATURAL LANGUAGE MODE
     * @return static
     */
    public function match(string $field, string $search, bool $booleanMode = false): static
    {
        $this->mBooleanMode = $booleanMode;
        $this->where($field, $search, 'match');
        return $this;
    }

    /**
     * 全文搜索，可按相关度数值排序
     * @param string $field 搜索的字段，多个英文逗号分隔
     * @param string $search 待搜索的值
     * @param string $asField 相关度字段名称
     * @param bool $booleanMode 是否为 BOOLEAN MODE，否则为 NATURAL LANGUAGE MODE
     * @return $this
     */
    public function matchField(string $field, string $search, string $asField = 'relativity', bool $booleanMode = false): static
    {
        $this->field('MATCH (' . $field . ') AGAINST (? IN ' . ($booleanMode ? 'BOOLEAN MODE' : 'NATURAL LANGUAGE MODE') . ') AS ' . $asField);
        $this->mWhereArgs[] = $search;
        return $this;
    }
}