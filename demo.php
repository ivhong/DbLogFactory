<?php
require __DIR__.'/vendor/autoload.php';

use ivhong\DblogFactory\DblogFactory;
use ivhong\DbLogFactory\DbLogFactoryInterface;



ApiLog::insertApiLog($_SERVER['REQUEST_URI'], '', '',var_export($_GET, 1), $post,$response,$useTime);

/**
 *接口访问日志
 */
class ApiLog
{
    /**
     * 写入日志
     * @param string $url 访问的url
     * @param string $controller 控制器
     * @param string $action 行为
     * @param string $get get参数
     * @param string $post post 参数
     * @param string $response 返回结果
     * @param string $useTime 执行时间
     */
    public static function insertApiLog($url, $controller, $action, $get, $post, $response, $useTime)
    {
        try {
            $logs = [
                'url' => $url,
                'controller' => $controller,
                'action' => $action,
                'get' => json_encode($get, JSON_UNESCAPED_UNICODE),
                'post' => is_string($post) ? $post : json_encode($post, JSON_UNESCAPED_UNICODE),
                'response' => is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE),
                'useTime' => $useTime,
                'createTime' => date('Y-m-d H:i:s'),
            ];
            static::getDbLog()->addLog($logs);
        } catch (\Exception $e) {
            $p = [
                'msg' => $e->getMessage(),
                'args' => func_get_args(),
            ];
            var_dump($p);
        }
    }

    /**
     * 获取工厂具柄
     * @return DbLogFactory
     */
    protected static function getDbLog(): DbLogFactory
    {
        static $dbLog = null;
        if (!$dbLog) {
            $dbLog = new DbLogFactory(new Class()
                implements DbLogFactoryInterface
            {
                //表基础名（前缀）
                protected $table_name = 'api_log';

                /**
                 * 表名
                 * @return string
                 */
                function getDbName(): string
                {
                    return $this->table_name. '_' . date('Ymd');
                }

                /**
                 * 删除的表名，日志表只保留7天
                 * @return array
                 */
                function getDelTableName(): array
                {
                    return [$this->table_name . '_' . date('Ymd', strtotime('-7 days'))];
                }

                /**
                 * 数据库表字段
                 * @return array
                 */
                function getDbFields(): array
                {
                    $fields = [
                        DbLogFactory::generateField('url', 'varchar(500)', '', '访问路径'),
                        DbLogFactory::generateField('controller', 'varchar(255)', '', '地址'),
                        DbLogFactory::generateField('action', 'varchar(50)', '', '调用方式'),
                        DbLogFactory::generateField('get', 'text', '', '入参GET'),
                        DbLogFactory::generateField('post', 'longtext', '', '入参POST'),
                        DbLogFactory::generateField('response', 'longtext', '', '返回结果'),
                        DbLogFactory::generateField('useTime', 'float(5,2)', 0.00, '执行时间'),
                        DbLogFactory::generateField('createTime', 'TIMESTAMP', '0000-00-00 00:00:00', '访问时间'),
                    ];

                    return $fields;
                }

                /**
                 * 获得PDO具柄
                 * @return PDO
                 */
                function getConnect(): \PDO
                {
                    $dbms='mysql';     //数据库类型
                    $host='localhost'; //数据库主机名
                    $dbName='test';    //使用的数据库
                    $user='root';      //数据库连接用户名
                    $pass='';          //对应的密码
                    
                    $dsn="$dbms:host=$host;dbname=$dbName;charset=utf8mb4";
                    return new PDO($dsn, $user, $pass);
                }
            });
        }
        return $dbLog;
    }
}