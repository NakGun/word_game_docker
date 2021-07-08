<?php

class MyDB
{
    public mysqli $connection;

    //__construct class 는 자동으로 인스턴스를 바로 생성하는대 이때 자동으로 호출되는 함수이다.
    // 소스 맨 아래 인스턴스 생성하는 곳이 있다. 사실상 그부분에서 호출된다고 생각하면 될듯함.
    public function __construct(string $host, string $dbname, string $username, string $passwd, int $port = 0)
    {
       // file_put_contents('myDebug',"'".$host."','".$dbname."','".$username."','".$passwd."','".$port."'\n", FILE_APPEND | LOCK_EX);

        try { 
            if (!$port) $port = ini_get("mysqli.default_port");
            $this->connection = new mysqli($host, $username, $passwd, $dbname, $port);
        } catch (Exception $e) {
            $this->handleError($e->getCode() . ': ' . $e->getMessage());
        }
    }

    private function handleError(string $msg, string $sql = '')
    {
        $error_message = ' ------ Mysql error: ' . $msg ."\n" . $sql;
        debug_log($error_message);
        throw new RuntimeException($error_message);
    }

    /**
     * @return mixed : 타입이 문자열,정수,실수 등 여러가지가 될 수 있음을 정의함.
     *  Returns false on failure. For successful queries which produce a result set, such as SELECT, SHOW, DESCRIBE or
     *  EXPLAIN, mysqli_query() will return a mysqli_result object. For other successful queries, mysqli_query() will
     *  return true.
     */
    public function query($sql): mixed {
        return $this->connection->query($sql);
    }


    public function parseRecord(array $record, string $type = 'insert') {
        // 입력 받은 배열에서 필드와 값을 분리시켜 각각 $fields 와 $values 로 저장.

        $fields = [];
        $values = [];
        
        //foreach
        // $employee_list = array(
        //     'Programmer' => 'Edward',
        //     'Designer' => 'Alex'
        // );
        
        // foreach($employee_list as $key => $value)
        // {
        //     echo $key." : ".$value."<br/>";
        // }
        // 결과는 key, value모두 가져올 수 있음
        // foreach($employee_list as $row) 이런경우(하나만 있는경우) value만 가져옴
        
        // 여기는 타입과 값이 키:밸류 형태와 왔을태고 그것을 각각 배열변수로 담아 분리함
        foreach ($record as $k => $v) {
            $fields[] = $k;
            $values[] = $v;
        }
        file_put_contents('myDebug',"'.parseRecord.','".$fields[0]."','".$values[0]."',\n", FILE_APPEND | LOCK_EX);

        // impode : 배열을 하나의 문자열로 만듬
        // $jbary = array( 'one', 'two', 'three', 'four' );
        // $jbstr = implode( ' / ', $jbary );
        // 결과값 : ont / two / three / four    
        if ( $type == 'insert' ) {
            $return_fields = implode(',', $fields); //id, username, email...
            // [배열명] = array_fill([시작번호], [끝번호], "[값]");
            $return_values = implode(",", array_fill(0, count($values), '?')); //?,?,? (insert query 형식)
        } else if ( $type == 'update' || $type == 'where' ) {
            $expressions = [];
            foreach( $fields as $field ) {
                $expressions[] = "$field=?"; //string=? or int=?
            }
            if($type == 'update'){
                $return_fields = implode(" , ", $expressions); //update set 컬럼구문
            }else{
                $return_fields = implode(" AND ", $expressions); //update where 컬럼구분
            }
            //$return_fields = implode(" AND ", $expressions); //name=? AND age=? (update 쿼리 및 조건절 형식)
            $return_values = $values; //nakun, 43, email..
        }
        return [ 'fields' => $return_fields, 'values' => $return_values ];
    }
    /**
     *
     * @param string $table - 레코드를 생성 할 테이블
     * @param array $record - 레코드의 필드(키)와 값을 연관 배열로 입력 받는다.
     */
    public function insert(string $table, array $record)
    {
        //console_log($record);
        // Statement 준비
        $stmt = $this->connection->stmt_init(); //쿼리를 사용하기 위한 초기화 같은 것

        // 입력 받은 테이블과 레코드 정보를 바탕으로 SQL 문장을 만든다.
        $parsed = $this->parseRecord( $record ); //입력받은 키:밸류 형식인 record를 정의한 함수를 통해 변수에 나눠담음 
        $sql = "INSERT INTO $table ( $parsed[fields] ) VALUES ( $parsed[values] )";
        $re = $stmt->prepare($sql);
        if (!$re) {
            $this->handleError($this->connection->error, $sql);
        }

        // ﻿array_keys : key:value 상태의 값을 가진 배열에서 키만 뺸다
        // array_values : key:value 상태의 값을 가진 배열에서 밸류만 뺸다
        $values = array_values($record);

        // 저장 할 값의 타입(형)을 계산 
        $types = $this->types($values); //실제 데이터를 대입하여 string, int, float 등 타입을 리턴받음

        // SQL 문장을 바탕으로 값의 타입(형)과 값을 바인드
        // 예시) $stmt->bind_param(ssi, 'nakun','nakun@naver.com',43); //앞에 ssi s는 string, i는 int
        $stmt->bind_param($types, ...$values);

        // 쿼리수행
        $re = $stmt->execute();
        if (!$re) {
            $this->handleError($this->connection->error, $sql);
        }
    }

    public function delete(string $table, array $conds): bool
    {
        //file_put_contents('myDebug','delete~~~', FILE_APPEND | LOCK_EX);
        try {
            $stmt = $this->connection->stmt_init();
            $sql = "DELETE FROM $table WHERE id = ?";
            $stmt->prepare($sql);
            $types = $this->types($conds);
            $stmt->bind_param($types, $conds['id']);
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e->__toString(), $sql);
            return false;
        }
    }

    public function update(string $table, array $record, array $conds): bool
    {
        
        try {
            
            $stmt = $this->connection->stmt_init();
            
            $update = $this->parseRecord( $record, 'update' );
            $where = $this->parseRecord( $conds, 'where' );
            $sql = "UPDATE $table SET $update[fields] WHERE $where[fields]";
            $stmt->prepare($sql);
            $re = $stmt->prepare($sql);
            if (!$re) {
                $this->handleError($this->connection->error, $sql);
            }

            // ﻿array_keys : key:value 상태의 값을 가진 배열에서 키만 뺸다
            // array_values : key:value 상태의 값을 가진 배열에서 밸류만 뺸다
            $values = array_merge( array_values($record), array_values($conds));

            $types = $this->types($values);
            $re = $stmt->bind_param($types, ...$values);

            return $stmt->execute();

            
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e->__toString(), "SQL: " . $sql);
        }

    }


    public function rows(string $table, array $conds = [], $select = '*')
    {
        try {
            
            $stmt = $this->connection->stmt_init();
            //조건절이 있을때
            if ( $conds ) {
                $parsed = $this->parseRecord( $conds, 'where' );
                $sql = "SELECT $select FROM $table WHERE $parsed[fields]";
                $stmt->prepare($sql);
                $re = $stmt->prepare($sql);
                if (!$re) {
                    $this->handleError($this->connection->error, $sql);
                }
                $values = array_values($conds);
                $types = $this->types($values);
                $re = $stmt->bind_param($types, ...$values);
            //조건절이 없을떄
            } else {
                $sql = "SELECT $select FROM $table";
                $stmt->prepare($sql);
            }

            
            $stmt->execute();

            $result = $stmt->get_result(); // get the mysqli result
            if ( $result === false ) {

                $this->handleError("SQL ERROR on row()", $sql);
                return [];
            }
            /* 조회쿼리결과리턴 */
            $rets = [];
            while ($row = $result->fetch_assoc()) {
                $rets[] = $row;
            }
            return $rets; //조회리스트 또는 조회건수를 리턴하게됨
        } catch (mysqli_sql_exception $e) {
            $this->handleError($e->__toString(), "SQL: " . $sql);
        }
    }

    
    

    public function row(string $table, array $conds = [], $select = '*')
    {
        $rows = $this->rows($table, $conds, $select);
        if ( ! $rows ) return null;
        return $rows[0];
    }

    //conds = null 인 경우 조회건수를 가져오게 됨 아니면 조회리스트
    public function column(string $table, array $conds = [], $select = '*') {
        $row = $this->row( $table, $conds, $select );
        if ( ! $row ) return null;
    
        return $row[$select];
    }

    //조회건수를 구하기 위함 같은데 어디서 호출하는거지
    //BasicTest.php에서 호출하네
    public function count(string $table, array $conds = []) {
        return $this->column($table, $conds, "COUNT(*)");
    }


    

    /**
     * @param $val
     * @return string
     */
    private function type(mixed $val): string
    {
        if ($val == '' || is_string($val)) return 's';
        if (is_float($val)) return 'd';
        if (is_int($val)) return 'i';
        return 'b';
    }

    /**
     * @param array $values
     * @return string
     */
    private function types(array $values): string
    {
        $type = '';
        foreach ($values as $val) {
            $type .= $this->type($val);
        }
        return $type;
    }
}

$mysqli = new MyDB('mariadb', 'oniyuni', 'nakun', '354900aa'); 

function db(): MyDB
{
    global $mysqli;
    return $mysqli;
}