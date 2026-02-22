<?php
require_once __DIR__ . '/config.php';

if (!function_exists('get_stmt_result')) {
    function get_stmt_result($stmt) {
        $stmt->store_result();
        $meta = $stmt->result_metadata();
        
        if (!$meta) return false;
        
        $fields = [];
        $row = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
        
        call_user_func_array([$stmt, 'bind_result'], $fields);
        
        $results = [];
        while ($stmt->fetch()) {
            $c = [];
            foreach ($row as $key => $val) {
                $c[$key] = $val;
            }
            $results[] = $c;
        }
        $stmt->free_result();
        
        return new class($results) {
            private $data;
            private $index = 0;
            public $num_rows;
            
            public function __construct($data) {
                $this->data = $data;
                $this->num_rows = count($data);
            }
            
            public function fetch_assoc() {
                if ($this->index < $this->num_rows) {
                    return $this->data[$this->index++];
                }
                return null;
            }
            
            public function fetch_all($mode = MYSQLI_NUM) {
                if ($mode == MYSQLI_ASSOC) return $this->data;
                return array_map('array_values', $this->data);
            }
            
            public function fetch_array($mode = MYSQLI_BOTH) {
                if ($this->index < $this->num_rows) {
                    $assoc = $this->data[$this->index++];
                    if ($mode == MYSQLI_ASSOC) return $assoc;
                    if ($mode == MYSQLI_NUM) return array_values($assoc);
                    $num = array_values($assoc);
                    return array_merge($assoc, $num);
                }
                return null;
            }
            
            public function fetch_object() {
                if ($this->index < $this->num_rows) {
                    return (object)$this->data[$this->index++];
                }
                return null;
            }
        };
    }
}
