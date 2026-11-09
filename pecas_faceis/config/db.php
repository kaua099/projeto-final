<?php
class Database
{
private static $conn = null;
public static function getConnection()
{
if (self::$conn === null) {
try {
self::$conn = new PDO(
    "mysql:host=sql112.infinityfree.com;dbname=if0_43312239_pecas_faceis;charset=utf8mb4",
    "if0_43312239",
    "350790AB",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
} catch (PDOException $e) {
die("Erro ao conectar ao banco de dados.");
}
}
return self::$conn;
}
}
