<?php
namespace Lib;

require_once __DIR__.'/ErrorCode.php';

use Lib\ErrorCode;
use \PDO;

class Tree
{
	protected $root = 0;
	protected $nodes;
	protected $tree;

	private $_db;

	public function __construct($db)
	{
		$this->_db = $db;
		$this->setNodes();
		$this->tree = $this->buildTree($this->nodes);
	}

	/**
	 * reset nodes and rebuild the tree
	 */
	public function refreshNodesAndTree()
	{
		$this->setNodes();
		$this->tree = $this->buildTree($this->nodes);
	}

	/**
	 * set up nodes array
	 */
	public function setNodes()
	{
		$query = "SELECT * FROM `branches`";

        $stmt = $this->_db->prepare($query);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->nodes = $data;
	}

	public function getNodes()
	{
		return $this->nodes;
	}

	public function buildTree(array $nodes)
	{
	    $result = [];

	    $nodes = array_column($nodes, null, 'id');

	    foreach ($nodes as $node) {
	        if (isset($nodes[$node['parent_id']])) {
	            $nodes[$node['parent_id']]['children'][] = &$nodes[$node['id']];
	       
	        } else {
	            $result[] = &$nodes[$node['id']];
	        }
	    }

	    return $result;
	}

	/**
	 * build a sub tree from a certain node as root
	 * @param  [array] $nodes   [nodes to build the sub tree]
	 * @param  [int] $parentId  [new root]
	 * @return [array]          [a sub tree]
	 */
	public function buildSubTree(array $nodes, int $parentId)
	{
		$tree = null;
		foreach($nodes as $key => $node)
		{
		  if($node['parent_id'] == $parentId)
		  {        
		  	$node['children'] = $this->buildSubTree($nodes, $node['id']);
		  	$tree[] = $node;
		  	unset($nodes[$key]);
		  }
		}

		return $tree;
	}

	//create a new branch
	public function create(array $request)
	{
		if(empty($request['parent_id'])){
            throw new \Exception("Parent ID is required", ErrorCode::PARENT_ID_IS_REQUIRED);
        }
        
        if(empty($request['store_name'])){
            throw new \Exception("Store name is required", ErrorCode::STORE_NAME_IS_REQUIRED);
        }

        if(empty($request['store_state'])){
            throw new \Exception("Store state is required", ErrorCode::STORE_STATE_IS_REQUIRED);
        }

        //store name shall be unique
        $searchStoreName = array_search($request['store_name'], array_column($this->nodes, 'store_name'));
        if ($searchStoreName !== false) {
        	throw new \Exception("Store name is existed", ErrorCode::STORE_NAME_IS_EXISTED);
        }
        
        $query = "INSERT INTO `branches` (`parent_id`,`store_name`,`store_state`) VALUES (:parent_id,:store_name,:store_state)";      
        
        $stmt = $this->_db->prepare($query);
        
        $stmt->bindParam(':parent_id',$request['parent_id']);
        $stmt->bindParam(':store_name',$request['store_name']);
        $stmt->bindParam(':store_state',$request['store_state']);
        
        if(!$stmt->execute()){
            throw new \Exception('New branch creation failed', ErrorCode::BRANCH_CREATE_FAIL);
        }

        $lastInsertId = $this->_db->lastInsertId();

        //refresh nodes and build the tree
        $this->refreshNodesAndTree();
        
        return [
            'new_store_id' 		  => $lastInsertId,
            'new_store_parent_id' => $request['parent_id'],
            'new_store_name'      => $request['store_name'],
            'new_store_state' 	  => $request['store_state'],
        ];
	}

	//update a branch
	public function update(array $node, array $request)
	{
		if ($node['id'] == $this->root) {
			throw new \Exception('Root node cannot be updated', ErrorCode::ROOT_CANNOT_BE_UPDATED);
		}

        $query = "UPDATE `branches` 
        			SET `parent_id` = :parent_id, `store_name` = :store_name, `store_state` = :store_state 
        			WHERE `id` = :node_id";
        
        $stmt = $this->_db->prepare($query);

        $stmt->bindParam(':parent_id',  $request['parent_id']);
        $stmt->bindParam(':store_name', $request['store_name']);
        $stmt->bindParam(':store_state',$request['store_state']);
        $stmt->bindParam(':node_id',    $node['id']);

        if(!$stmt->execute()){
            throw new \Exception('Branch update failed', ErrorCode::BRANCH_UPDATE_FAIL);
        }

        $this->refreshNodesAndTree();
        
        return [
            'node_id'     => $node['id'],
            'parent_id'   => $request['parent_id'],
            'store_name'  => $request['store_name'],
            'store_state' => $request['store_state'],
            'new_tree'	  => $this->tree,
        ];
	}

	//delete a branch along with all children
	public function delete(int $nodeId)
	{
		if ($nodeId == $this->root) {
			throw new \Exception('Root node cannot be deleted', ErrorCode::ROOT_CANNOT_BE_DELETED);
		}

		$node = $this->viewNode($nodeId);

		//node index in nodes array
		$nodeIndex = $this->findNodeIndex($nodeId);
		
		//node in raw array (other than generated tree)
		$targetNode = $this->nodes[$nodeIndex];

		$nodesWithoutChildren = $this->findNodeWithoutChildren($this->tree);

		if (in_array($targetNode['id'], $nodesWithoutChildren)) {
			//branch has NO children
			$query = "DELETE FROM `branches` WHERE `id` = :node_id";
			$stmt = $this->_db->prepare($query);
        	$stmt->bindParam(':node_id',$nodeId);
		}else{
			//branch has children
			$nodeIDs = [];

			array_push($nodeIDs, $nodeId);

			$subTree = $this->viewSubTreeWithChildren($nodeId);
			$childrenIDs = $this->findAllChildren($subTree['children']);

			foreach ($childrenIDs as $childID) {
				array_push($nodeIDs, $childID);
			}

			$IDs = implode(",", $nodeIDs);
			$query = "DELETE FROM `branches` WHERE `id` IN (".$IDs.")";
			$stmt = $this->_db->prepare($query);

			unset($nodeIDs);
		}

        if($stmt->execute() === false){
            throw new \Exception('Branch deletion failed', ErrorCode::BRANCH_DELETE_FAIL);
        }

		$this->refreshNodesAndTree();

		return [
			'new_tree' => $this->tree
		];
	}

	//find and view a node
	public function viewNode(int $nodeId)
	{
		if(empty($nodeId)){
            throw new ErrorException('Node ID is required', ErrorCode::NODE_ID_IS_REQUIRED);
        }
        
        $query = "SELECT * FROM `branches` WHERE `id` = :id";
        
        $stmt = $this->_db->prepare($query);
        $stmt->bindParam(':id',$nodeId);
        $stmt->execute();
        
        $node = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($node)){
            throw new \Exception('Node is not existed', ErrorCode::NODE_NOT_FOUND);
        }
        
        return $node;
	}

	public function view(int $nodeId, string $withChildren)
	{
		$node = $this->viewNode($nodeId);

		if ($withChildren == 'true') {
			$subTree = $this->viewSubTreeWithChildren($nodeId);

			return ['sub_tree' => $subTree];
		}

		$nodesWithoutChildren = $this->findNodeWithoutChildren($this->tree);

		//the provided node ID has no children
		if (in_array($nodeId, $nodesWithoutChildren)) {
			return ['node_has_no_children' => $node];
		}

		return ['nodes_without_children' => $nodesWithoutChildren]; 
	}

	/**
	 * view one specific store branch with all of its children
	 * @param  [int]  $nodeId       [specific node ID]
	 * @param  boolean $withChildren [with/without node's children]
	 */
	public function viewSubTreeWithChildren(int $nodeId)
	{
		$subTree = [];

		$subTreeNodes = $this->buildSubTree($this->nodes, $nodeId);

		$key = $this->findNodeIndex($nodeId);

		$rootNode = $this->nodes[$key];

		$rootNode['children'] = $subTreeNodes; 

		return $rootNode;
	}

	public function getTree()
	{
		return $this->tree;
	}

	public function findNodeIndex(int $nodeId)
	{
		return array_search($nodeId, array_column($this->nodes, 'id'));
	}

	public function findNodeWithoutChildren($tree)
	{
		static $data;
	    if (!is_array ($tree)) {
	        return $data;
	    }

	    foreach ($tree as $key => $val ) {
	        if (isset($val['children']) && is_array ($val['children'])) {
	            $this->findNodeWithoutChildren($val['children']);
	        } else {
	        	//collect nodes without children
	            $data[]=$val['id'];
	        }
	    }
	    return $data;
	}

	public function findAllChildren(array $children)
	{
		static $data;

	    foreach ($children as $key => $val ) {

	    	$data[]=$val['id'];

	        if (isset($val['children']) && !empty($val['children']) && is_array ($val['children'])) {
	            $this->findAllChildren($val['children']);
	        }
	    }

	    return $data;
	}
}