<?php
class DatabaseConnection {
    private static $conn;

    public static function getConnection(){
        if (self::$conn == null){
            try {
                $host = "fixpointpostgre-fixpoint.j.aivencloud.com";
                $dbname = "fixpoint";
                $port = "21000";
                $username = "avnadmin";
                $password = "AVNS_Gw_yCFEFctWOK3mViTJ";

                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

                self::$conn = new PDO($dsn, $username, $password, $options);

            } catch(PDOException $e){
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
?>