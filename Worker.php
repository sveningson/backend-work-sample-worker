<?php 

class Worker{

    private static $host = '127.0.0.1';
    private static $db_name = 'mydb';
    private static $username = 'admin';
    private static $password = 'password';
    private static $table_name = 'Jobs';
    private static $conn;

    private function getConnection() {
        $conn = new mysqli(self::$host, self::$username, self::$password);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 

        $conn->select_db(self::$db_name);
        
        self::$conn = $conn;
 
        return $conn;
    }

    private function query($sql, $conn=null) {
        if(!$conn){
            $conn = self::$conn;
        }

        $result = $conn->query($sql);

        if($result === FALSE){
            return $result;
        }

        if($result === TRUE){
            return $result;
        }

        if($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }

    }

    private function nextJob() {
        $sql = "SELECT id, url FROM " . self::$table_name . " WHERE status='NEW' ORDER BY id ASC LIMIT 1 FOR UPDATE";
        $job = self::query($sql);
        if($job !== FALSE){
            $sql = "UPDATE " . self::$table_name . " SET status='PROCESSING' WHERE id=" . $job['id'];
            self::query($sql);
        }

        return $job;
    
    }

    private function storeCode($code, $id, $status='DONE') {
        // ADD Error to status
        $sql = "UPDATE " . self::$table_name . " SET status='" . $status . "', http_code=" . $code . " WHERE id=" . $id;
        self::query($sql);
    }

    private function getHttpResponseCode(string $url): int{
        
            $headers = get_headers($url);
            return substr($headers[0], 9, 3);
        
        
    }

    private function work($job) {
        $code = self::getHttpResponseCode($job['url']);
        if($code === FALSE){
            self::storeCode('null', $job['id'], 'ERROR');
        }else{
            self::storeCode($code, $job['id']);
        }
        
        if($nextJob = self::nextJob()){
            self::work($nextJob);
        }
    }

    public function run(){
        $conn = self::getConnection();
        self::work(self::nextJob());
    }


}