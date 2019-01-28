<?php
namespace Lib;

require_once __DIR__.'/ErrorCode.php';
require_once __DIR__.'/Config.php';

use Lib\ErrorCode;
use Lib\Config;
use \PDO;

class User{
    
    private $_db;
    
    public function __construct($db){
        $this->_db = $db;
    }
    
    public function login (string $username, string $password){
        if(empty($username)){
            throw new Exception('Username is required', ErrorCode::USERNAME_CANNOT_EMPTY);
        }
        
        if(empty($password)){
            throw new Exception('Password is required', ErrorCode::PASSWORD_CANNOT_EMPTY);
        }
        
        $query = "SELECT * FROM `user` WHERE `username` = :username AND `password` = :password";
        
        $md5_password = $this->_md5($password);
        
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':username',$username);
        $stmt->bindParam(':password',$md5_password);
        
        if(!$stmt->execute()){
            throw new Exception('Server internal error', ErrorCode::SERVER_INTERNAL_ERROR);
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($user)){
            throw new Exception('Username or Password is invalid',ErrorCode::USERNAME_OR_PASSWORD_INVALID);
        }
        
        unset($user['password']);
        
        return $user;
    }
    
    private function _md5(string $string, $key = Config::MD5_EXTRA_KEY){
        return md5($string.$key);
    }
    
    private function _isUsernameExisted(string $username){
                
        $query = "SELECT * FROM `user` WHERE `username` = :username";
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':username',$username);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return !empty($result);
    }
}