<?php
namespace Lib;

use \PDO;
 
class DB 
{
	protected $db = [
		'host' => 'localhost',
		'dbname' => 'myapi',
		'username' => 'root',
		'password' => ''
	];

    public function connect()
    {
    	try{
    		$conn = new PDO("mysql:host={$this->db['host']};dbname={$this->db['dbname']}", $this->db['username'], $this->db['password']);
    		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    		return $conn;

    	}catch (PDOException $exception){
    		exit($exception->getMessage());
    	}
    }//connect
}