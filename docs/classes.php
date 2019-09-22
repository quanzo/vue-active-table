<?php
namespace test;

class RequestResponse{
    protected $arValidMethods = [];
    protected $id = null;
    protected $params = [];
    protected $method;
    protected $isRequest = false;
    
    public function __construct($arValidMethods) {
        $this->arValidMethods = $arValidMethods;
        if (!empty($_POST['method']) && in_array($_POST['method'], $this->arValidMethods)) {
            $this->isRequest = true;
            $this->method = $_POST['method'];
            if (!empty($_POST['id'])) {
                $this->id = intval($_POST['id']);
            }
            if (!empty($_POST['params'])) {
                $this->params = $_POST['params'];
            }
        }
    }

    public function __get($name) {
        if ($this->isRequest()) {
            $arValidParams = ['params', 'id', 'method'];
            if (in_array($name, $arValidParams)) {
                return $this->$name;
            }
        }
        return null;        
    }

    public function isRequest() {
        return $this->isRequest;
    }

    public function sendResult($data) {
        echo json_encode([
            'result' => $data,
            'id' => $this->id           
        ]);
    } // end sendResponse

    public function sendError($errCode, $errMessage = '') {
        echo json_encode([
            'error' => [
                'code' => $errCode,
                'message' => $errMessage
            ],
            'id' => $this->id
        ]);
    } // end sendResponse
} // end RequestResponse




class DbJson implements \ArrayAccess, \Countable,  \JsonSerializable {
    protected $filename;
    protected $arData = [];
    protected $arColumns = [];
    protected $ifChange = false;

    public function __construct($filename, $arColumns) {
        $this->arData = json_decode(file_get_contents($filename));
        $this->arColumns = $arColumns;
        $this->filename = $filename;
    }

    public function __destruct() {
        if ($this->ifChange) {
            file_put_contents($this->filename, json_encode($this->arData));
        }
    }

    public function count() {
        return sizeof($this->arData);
    }

    public function offsetExists ($offset) {
        return !empty($this->arData[$offset]);
    }

    public function offsetGet ($offset) {
        if (!empty($this->arData[$offset])) {
            return $this->arData[$offset];
        }
        return null;
    }

    public function offsetSet ($offset, $value) {
        $this->arData[$offset] = $value;
        $this->ifChange = true;

    }

    public function offsetUnset ($offset) {
        unset($this->arData[$offset]);
        $this->ifChange = true;
    }

    public function setCell($row, $col, $val) {
        if ($this->offsetExists($row)) {
            $this->arData[$row][$col] = $val;
            return true;
        }
        return false;
    }

    public function jsonSerialize() {
        return [
            'data' => $this->arData,
            'columns' => $this->arColumns
        ];
    }

    public function getCell($row, $col) {
        if ($this->offsetExists($row)) {
            return $this->arData[$row][$col];
            return true;
        }
        return null;
    }

    public function getColumns() {
        return $this->arColumns;
    }

    protected function validateColumnName($col) {
        if (!empty($this->arColumns[$col])) {
            return true;
        }
        return false;
    }
} // end DbJson
