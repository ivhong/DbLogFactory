<?php
/**
 * Created by PhpStorm.
 * User: wangchanghong
 * Date: 2019/10/28
 * Time: 10:07 AM
 */

namespace ivhong\DbLogFactory;


interface DbLogFactoryInterface
{
    function getDbName(): string;
    function getDbFields(): array;
    function getConnect() : \PDO;
    function getDelTableName(): array;
}
