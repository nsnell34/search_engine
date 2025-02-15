<?php

class Database {

    private $host = "localhost";
    private $username = ""; // username
    private $password = ""; // password
    private $database = ""; // database name

    public $conn;

    public function __construct()
    {
        $this->conn = mysqli_init();

        mysqli_ssl_set($this->conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL);

        if (!mysqli_real_connect($this->conn, $this->host, $this->username, $this->password, $this->database, 3306)) {
            die("Connection failed: " . mysqli_connect_error());
        }
    }
}
