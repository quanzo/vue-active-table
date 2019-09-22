<?php
use \test\DbJson;
use \test\RequestResponse;


$cfg = include 'params.php';
include 'classes.php';
$rr = new RequestResponse(['load', 'change', 'newRow']);
//---
file_put_contents('post.txt', print_r($_REQUEST, true), FILE_APPEND);
//---
if ($rr->isRequest()) {

    $dbf = new \PDO('sqlite:' . __DIR__ . '/testdb.sq3');    
    //$dbf = new DbJson($cfg['dbf'], $cfg['columns']);

    switch ($rr->method) {
        case 'load': {
            // загрузка данных
            $arSqlParams = [];            
            if (!empty($rr->params['perpage']) && $rr->params['perpage'] > 0) {
                $arSqlParams['limit'] = intval($rr->params['perpage']);
                if (!empty($rr->params['page']) && $rr->params['page'] > 0) {
                    $arSqlParams['offset'] = (intval($rr->params['page'])-1) * $arSqlParams['limit'];
                } else {
                    $arSqlParams['offset'] = 0;
                }                
            }
            $where = '';
            if (!empty($rr->params['filter']) && is_array($rr->params['filter'])) {                
                foreach ($rr->params['filter'] as $col => $val) {
                    if ($where) {
                        $where .= ' AND ';
                    }
                    $where .= '(`'.$col.'` ';
                    if (is_array($val)) {
                        $where .= 'IN (';
                        $counter = 0;
                        foreach ($val as $val2) {                            
                            $where .= ($counter > 0 ? ',': "").$dbf->quote($val2);
                        }
                        $where .= ')';
                    } else {
                        $where .= ' == '.$dbf->quote($val);
                    }
                    $where .= ')';
                }
            }

            $sql = 'SELECT '.implode(',', array_keys($cfg['columns'])).' FROM '.$cfg['tablename'].($where ? ' WHERE '.$where : '').($arSqlParams['limit'] ? ' LIMIT '.$arSqlParams['offset'].', '.$arSqlParams['limit'] : '');
            $statement = $dbf->query($sql);
            
            file_put_contents('post.txt', print_r($sql, true), FILE_APPEND);
            file_put_contents('post.txt', print_r($where, true), FILE_APPEND);


            $arResult = [
                'data' => $statement->fetchAll(),
                'columns' => $cfg['columns'],                
            ];
            if (!empty($cfg['tableKey'])) {
                $arResult['key'] = $cfg['tableKey'];
            }

            $rr->sendResult($arResult);
            break;
        }
        case 'change': {
            //$rr->sendError(1, "Error change");
            file_put_contents('post.txt', print_r($rr->params, true), FILE_APPEND);

            $ifError = false;
            if (empty($rr->params['event'])) {
                $rr->sendError(1, "Not found event in request");
                $ifError = true;
            }
            if (!$ifError && !empty($rr->params['blockedRows']) && !empty($rr->params['blockedRows'][$rr->params['event']['row']])) {
                $rr->sendError(2, "This row is blocked");
            }
            if (!$ifError) {
                $sql = 'UPDATE '.$cfg['tablename'].' SET '.$dbf->quote($rr->params['event']['col']).'='.$dbf->quote($rr->params['event']['result']).' WHERE '.$cfg['tableKey'].'=='.$dbf->quote($rr->params['event']['data'][$cfg['tableKey']]);
                $stm = $dbf->query($sql);
                $rr->sendResult([]);
            }
            break;
        }
        case 'newRow': {
            //$rr->sendError(4, "Not evaluate");
            $arNewRow = $rr->params['event']['data'];
            if (!empty($cfg['tableKey'])) {
                unset($arNewRow[$cfg['tableKey']]);
            }
            
            $fields = '';
            $values = '';
            foreach ($arNewRow as $name => $val) {
                $fields .= ($fields ? ', ' : '').$name;
                $values .= ($values ? ', ' : '') . $dbf->quote($val);
            }
            $sql = 'INSERT INTO ' . $cfg['tablename'] . ' ('.$fields.') VALUES ('.$values.')';
            $stm = $dbf->query($sql);
            $insertId = $dbf->lastInsertId($cfg['tableKey']);
            if ($insertId !== false) {
                $sql = 'SELECT * FROM ' . $cfg['tablename'] . ' WHERE '.$cfg['tableKey'].' == '.$dbf->quote($insertId);
                $statement = $dbf->query($sql);
                $arInsertedRow = $statement->fetch(PDO::FETCH_ASSOC);
                if ($arInsertedRow) {
                    $rr->sendResult([
                        'row_data' => $arInsertedRow
                    ]);
                }

            } else {
                $rr->sendError(5, "Not insert row");
            }
            break;
        }

    } // end switch


}
/*
[
    'data' => [],
    'result' => true,
    'error' => ''

]


*/