<?php
namespace Lib;

require_once __DIR__.'/User.php';
require_once __DIR__.'/Tree.php';
require_once __DIR__.'/DB.php';
require_once __DIR__.'/ErrorCode.php';
require_once __DIR__.'/Filter.php';

use Lib\Filter;
use Lib\DB;
use Lib\ErrorCode;
use Lib\Tree;
use Lib\User;

class Restful{
    private $_user;
    private $_tree;
    private $_db;
    
    private $_requestMethod;
    private $_resourceName;
    
    private $_id;
    
    private $_allowedResrouces      = ['branches'];
    private $_allowedRequestMethods = ['GET', 'POST', 'PUT', 'DELETE'];
    
    /**
     * define normal HTTP status codes
     */
    private $_statusCodes = [
        200 => 'OK',
        204 => 'No content',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        405 => 'Method not allowed',
        500 => 'Server internal error'
    ];
    
    public function __construct($db){
    	$this->_db = $db;
    	$this->_tree = new Tree($this->_db);
        $this->_user = new User($this->_db);
    }
    
    /**
     * start Restful
     */
    public function run(){
        try{
        	//validate the user access
        	$user = $this->_userLogin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

            //set up the request method
            $this->_setupRequestMethod();
            //set up the resource name and identifier
            $this->_setupResource();
            
            //action upon the resource
            switch ($this->_resourceName){
                case 'branches':
                	return $this->_json($this->_handleTree());
            }
        } catch (Exception $e){
            $this->_json(['error' => $e->getMessage()], $e->getCode());
        }
        
    }
    
    /**
     * get request body
     * @return: the decoded Array
     */
    private function _getBodyParams(){
        $raw = file_get_contents('php://input');

        if(empty($raw)){
            throw new \Exception('The request params are invalid', 400);
        }

        return json_decode($raw, true);
    }

    private function _handleTree()
    {
    	if (!in_array($this->_requestMethod, $this->_allowedRequestMethods)) {
    		throw new Exception('The request method is not allowed', 405);
    	}

    	switch ($this->_requestMethod){ 
            case 'POST':
            	//[done] (1) create a store branch
                return $this->_handleBranchCreate();
            case 'GET':
            	//[done] (5) view all store branches with all of their children 
                if(empty($this->_id)){
                    return $this->_tree->getTree();
                }
                //[done] (6) view one specific store branch with all of its children
            	//[done] (7) view one specific store branch without any children
                return $this->_handleBranchView(); 
            case 'PUT':
            	//[done] (2) update a store branch
            	//[done] (4) move a store branch (along with all of its children) to a different store branch
                return $this->_handleBranchUpdate();
            case 'DELETE':
            	//[done] (3) delete a store branch along with all of its children
                return $this->_handleBranchDelete();
        }
    }

    /**
     * handler - view one specific store branch (with/without) all of its children
     */
    private function _handleBranchView()
    {
    	try {
    		if (empty($this->_id)) {
        		throw new \Exception('ID is required', 400);
        	}

            $requestBody = $this->_getBodyParams();
            $withChildren = $requestBody['children'];

	    	$allowedValues = ['true', 'false'];

	    	if (empty($withChildren) || !in_array($withChildren, $allowedValues)) {
	    		throw new \Exception('Children value is invalid', 400);
	    	}

            return $this->_tree->view($this->_id, $withChildren);
            
        }catch (Exception $e){
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            if($errorCode < 100){
                if ($errorCode == ErrorCode::NODE_NOT_FOUND){
                    throw new \Exception($errorMsg, 404);
                }else {
                    throw new \Exception($errorMsg, 400);
                }
            }else {
                throw $e;
            }
        }
    }

    /**
     * [done] handler - delete a branch with all children
     */
    private function _handleBranchDelete()
    {
        try{
        	if ($this->_id == 0) {
        		throw new \Exception('Root node cannot be deleted', 400);
        	}

        	if (empty($this->_id)) {
        		throw new \Exception('ID is required', 400);
        	}

        	$result = $this->_tree->delete($this->_id);
            
            return $result['new_tree'];
        }catch (Exception $e){
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            if($errorCode < 100){
                if ($errorCode == ErrorCode::NODE_NOT_FOUND){
                    throw new \Exception($errorMsg, 404);
                }else {
                    throw new \Exception($errorMsg, 400);
                }
            }else {
                throw $e;
            }
        }
    }

    /**
     * [done] handler - update/move a new branch
     */
    private function _handleBranchUpdate()
    {
        try {
            $nodeId = $this->_id;

            $node = $this->_tree->viewNode($nodeId);

            $body = $this->_getBodyParams();

            $parentId   = Filter::trimNonNumeric($body['parent_id']) ?? $node['parent_id'];
            $storeName  = Filter::filterString($body['store_name']) ?? $node['store_name'];
            $storeState = Filter::filterString($body['store_state']) ?? $node['store_state'];
            
            //no update incurred
            if ($body['parent_id'] == $node['parent_id'] && $body['store_name'] == $node['store_name'] && $body['store_state'] == $node['store_state'])
            {
            	return $node;
            }
            
            return $this->_tree->update($node, ['parent_id' => $parentId, 'store_name' => $storeName, 'store_state' => $storeState]);
            
        }catch (Exception $e){
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            if($errorCode < 100){
                if ($errorCode == ErrorCode::NODE_NOT_FOUND){
                    throw new \Exception($errorMsg, 404);
                }else {
                    throw new \Exception($errorMsg, 400);
                }
            }else {
                throw $e;
            }
        }
    }

    /**
     * [done] handler - create a new branch
     */
    private function _handleBranchCreate()
    {
        try{
        	//retrieve posted parameters
	    	$body = $this->_getBodyParams();

	        if(empty($body['parent_id'])){
	            throw new \Exception('The parent ID is required', 400);
	        }
	        
	        if(empty($body['store_name'])){
	            throw new \Exception('The store name is required', 400);
	        }

	        if(empty($body['store_state'])){
	            throw new \Exception('The store state is required', 400);
	        }

            $request = [
            	'parent_id'   => Filter::trimNonNumeric($body['parent_id']),
            	'store_name'  => Filter::filterString($body['store_name']),
            	'store_state' => Filter::filterString($body['store_state'])
            ];

            $result = $this->_tree->create($request);

            return [
            	'new_store_id' => $result['new_store_id'],
            	'new_tree' => $this->_tree->getTree(),
            ];
        }catch (Exception $e){
            if (in_array($e->getCode(),
                [
                    ErrorCode::PARENT_ID_IS_REQUIRED,
                    ErrorCode::STORE_NAME_IS_REQUIRED,
                    ErrorCode::STORE_STATE_IS_REQUIRED                
                ])){
                    throw new \Exception($e->getMessage(), 400);
                }
                throw new \Exception($e->getMessage(), 500);
        }
    }
    
    /**
     * user login
     * @return: array - user entitiy excluding password
     */
    private function _userLogin($PHP_AUTH_USER,$PHP_AUTH_PW){
        
        //the error codes thrown from login function are not standard HTTP codes, and therefore, catch them
        try {
            return $this->_user->login($PHP_AUTH_USER, $PHP_AUTH_PW);
        }catch (Exception $e){
            if (in_array($e->getCode(), 
                [
                    ErrorCode::USERNAME_CANNOT_EMPTY, 
                    ErrorCode::USERNAME_OR_PASSWORD_INVALID, 
                    ErrorCode::PASSWORD_CANNOT_EMPTY
                ])){
                throw new \Exception($e->getMessage(), 400);
            }
            throw new \Exception($e->getMessage(), 500);
        }
        
    }
    
    /**
     * return results in JSON format
     */
    private function _json($array, $code = 0)
    {
        
        if($array === NULL && $code == 0){
            $code = 204;
        }
        
        if ($array !== NULL && $code == 0){
            $code = 200;
        }

        header("HTTP/1.1 ".$code.' '.$this->_statusCodes[$code]);
        header('Content-Type:application/json; charset=utf-8');
        
        if($array !== NULL){
            echo json_encode($array, JSON_UNESCAPED_UNICODE);
        }
        
        exit();
    }
    
    private function _setupRequestMethod()
    {
        $this->_requestMethod = $_SERVER['REQUEST_METHOD'];
        
        if(!in_array($this->_requestMethod, $this->_allowedRequestMethods)){
            throw new \Exception('The request method is not allowed', 405);
        }
    }
    
    /**
     * set up resource name and identifier
     */
    private function _setupResource()
    {
        
        if(!empty($_SERVER['PATH_INFO'])){
            $path = $_SERVER['PATH_INFO'];
            
            $params = explode('/', $path); 
            //$params shall have 3 elements, eg. /restful/branches/{id}
            //[1] = resource name, 
            //[2] = resource identifier (optional in some cases)
            $this->_resourceName = $params[1];
            
            if(!in_array($this->_resourceName, $this->_allowedResrouces)){
                throw new \Exception('The requested resource is not allowed', 400);
            }
        }
        
        // set up the resource identifier
        if(!empty($params[2])){
            $this->_id = Filter::trimNonNumeric($params[2]);
        }
    }
}#END class