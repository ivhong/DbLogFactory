<?php
/**
 * Created by PhpStorm.
 * User: wangchanghong
 * Date: 2019/10/28
 * Time: 8:55 AM
 */

namespace ivhong\DbLogFactory;


class DbLogFactory
{
    protected $interface = null;
    protected $connect = null;
    protected $fields = null;
    protected $dbName = null;
    public function __construct(DbLogFactoryInterface $interface)
    {
        $this->interface = $interface;
        $this->connect = $interface->getConnect();
        $this->dbName = $interface->getDbName();
        $this->fields = $interface->getDbFields();
    }

    protected function initTable(){
        if( $this->connect->inTransaction() ){
            return;
        }

        $sql = $this->buildCreateTableSql();
        $this->connect->exec($sql);
        if( $delTables = $this->interface->getDelTableName() ){
            foreach($delTables as $tName){
                $sql = 'DROP TABLE IF EXISTS `'.$tName.'`';
                $this->connect->exec($sql);
            }
        }
    }

    public function getDelTableName(){
        return [];
    }

    public static function generateField($name, $type, $default, $comment){
        return compact('name', 'type', 'default', 'comment');
    }

    protected function buildCreateTableSql(){
        $fieldsSql = [
            'id int(11) not null primary key auto_increment comment\'主键id\''
        ];
        $fields = $this->fields;
        if(!$fields){
            throw new \Exception('字段不能为空');
        }

        foreach($fields as $field){
            if( in_array($field['type'], ['text']) ){
                $fieldsSql[] = '`'.$field['name'] . '` '. $field['type'].' null comment \''.$field['comment'].'\'';
            }else{
                $fieldsSql[] = '`'.$field['name'] . '` '. $field['type'].' not null default '.($this->isInt($field['type']) ? $field['default'] : $this->connect->quote($field['default'])).' comment \''.$field['comment'].'\'';
            }
        }

        $sql = "create table IF NOT EXISTS `".$this->dbName."` (
        ".implode(",".PHP_EOL, $fieldsSql)."
        ) engine=innodb,charset=utf8mb4,comment'日志'";
        return $sql;
    }

    public function addLog($data){
        $this->initTable();
        $sql = $this->buildAddSql($data);
        $this->connect->exec($sql);
    }

    protected function buildAddSql($data){
        $fields = [];
        $values = [];

        foreach($this->fields as $field){
            if(key_exists($field['name'], $data)){
                $fields[] = '`'.$field['name'].'`';
                if($this->isInt($field['type'])){
                    $values[] = $data[$field['name']];
                }else{
                    $val = $data[$field['name']];
                    if(!is_string($val)){
                        $val = var_export($val, 1);
                    }
                    $values[] = $this->connect->quote($val);
                }
            }
        }

        $sql = "INSERT INTO `".$this->dbName."`(".implode(',', $fields).") VALUES(".implode(',', $values).")";
        return $sql;
    }

    protected function isInt($sqlType){
        return in_array($this->getTypeName($sqlType), ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT']);
    }

    protected function getTypeName($sqlType){
        $length = strpos($sqlType, '(');
        if($length === false){
            return strtoupper($sqlType);
        }

        return strtoupper(trim(substr($sqlType, 0, $length)));
    }
}
