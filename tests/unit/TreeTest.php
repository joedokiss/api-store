<?php
require_once __DIR__.'/../../lib/DB.php';
require_once __DIR__.'/../../lib/Tree.php';

use PHPUnit\Framework\TestCase;

use Lib\DB;
use Lib\Tree;

class TreeTest extends TestCase
{
    protected $treeNodes;
    protected $tree;

    protected static $db;

    public static function setUpBeforeClass(){
    	$dbInstance = new DB();
		self::$db = $dbInstance->connect();
    }

    protected function setUp(){
        $this->treeNodes = [
        	['id' => 1,  'parent_id' => 0, 'store_name' => 'A', 'store_state' => 'NSW'],
			['id' => 2,  'parent_id' => 0, 'store_name' => 'B', 'store_state' => 'VIC'],
			['id' => 3,  'parent_id' => 0, 'store_name' => 'C', 'store_state' => 'WA'],

			['id' => 4,  'parent_id' => 1, 'store_name' => 'D', 'store_state' => 'QLD'],
			['id' => 5,  'parent_id' => 1, 'store_name' => 'E', 'store_state' => 'NSW'],

			['id' => 6,  'parent_id' => 2, 'store_name' => 'F', 'store_state' => 'TAS'],
			['id' => 7,  'parent_id' => 2, 'store_name' => 'G', 'store_state' => 'NSW'],
			['id' => 8,  'parent_id' => 2, 'store_name' => 'H', 'store_state' => 'QLD'],

			['id' => 9,  'parent_id' => 3, 'store_name' => 'I', 'store_state' => 'NSW'],

			['id' => 10, 'parent_id' => 9, 'store_name' => 'J', 'store_state' => 'VIC'],
			['id' => 11, 'parent_id' => 9, 'store_name' => 'K', 'store_state' => 'NSW'],

			['id' => 12, 'parent_id' => 7, 'store_name' => 'L', 'store_state' => 'WA']
        ];
    }

    /** @test */
    public function test_tree_has_twelve_nodes(){
    	$stub = $this->createMock(Tree::class);
    	$stub->method('getNodes')
    		 ->willReturn($this->treeNodes);

    	$this->assertEquals(12, count($stub->getNodes()));
    }

    /** @test */
    public function test_node_L_is_B_child(){
    	$node_B_children = [
			['id' => 6,  'parent_id' => 2, 'store_name' => 'F', 'store_state' => 'TAS'],
			['id' => 7,  'parent_id' => 2, 'store_name' => 'G', 'store_state' => 'NSW'],
			['id' => 8,  'parent_id' => 2, 'store_name' => 'H', 'store_state' => 'QLD'],
			['id' => 12, 'parent_id' => 7, 'store_name' => 'L', 'store_state' => 'WA']
        ];

    	$node_B_children_IDs = array_column($node_B_children, 'id');

    	$stub = $this->createMock(Tree::class);
    	$stub->method('findAllChildren')
    		 ->willReturn($node_B_children_IDs);

    	$nodeIndex = array_search('L', array_column($this->treeNodes, 'store_name'));

    	$this->assertContains($this->treeNodes[$nodeIndex]['id'], $stub->findAllChildren($node_B_children));
    }

    /** @test */
    
    public function test_create_node_M_as_A_child(){
    	//add a new node M under the A, so G will have two children - D,E and M
    	
    	$new_node_M = [
    		'parent_id'   => 1,
    		'store_name'  => 'M',
    		'store_state' => 'NSW'
    	];

    	$treeInstance = new Tree(self::$db);
    	$treeInstance->create($new_node_M);

    	$newTree = $treeInstance->getTree();

    	$expectedTree = [
    		//node A
    		[
    			'id' => 1, 'parent_id' => 0, 'store_name' => 'A', 'store_state' => 'NSW', 
    			'children' => [
    				[
    					'id' => 4, 'parent_id' => 1, 'store_name' => 'D', 'store_state' => 'QLD'
    				],
    				[
    					'id' => 5, 'parent_id' => 1, 'store_name' => 'E', 'store_state' => 'NSW'
    				],
    				[
    					'id' => 13, 'parent_id' => 1, 'store_name' => 'M', 'store_state' => 'NSW'
    				]
    			]
    		],
    		//node B
    		[
    			'id' => 2, 'parent_id' => 0, 'store_name' => 'B', 'store_state' => 'VIC', 
    			'children' => [
    				[
    					'id' => 6, 'parent_id' => 2, 'store_name' => 'F', 'store_state' => 'TAS'
    				],
    				[
    					'id' => 7, 'parent_id' => 2, 'store_name' => 'G', 'store_state' => 'NSW',
    					'children' => [
    						['id' => 12, 'parent_id' => 7, 'store_name' => 'L', 'store_state' => 'WA']
    					]
    				],
    				[
    					'id' => 8, 'parent_id' => 2, 'store_name' => 'H', 'store_state' => 'QLD'
    				]
    			]
    		],
    		//node C
    		[
    			'id' => 3, 'parent_id' => 0, 'store_name' => 'C', 'store_state' => 'WA', 
    			'children' => [
    				[
    					'id' => 9, 'parent_id' => 3, 'store_name' => 'I', 'store_state' => 'NSW', 
    					'children' => [
    						[
    							'id' => 10, 'parent_id' => 9, 'store_name' => 'J', 'store_state' => 'VIC'
    						],
    						[
    							'id' => 11, 'parent_id' => 9, 'store_name' => 'K', 'store_state' => 'NSW'
    						]
    					]
    				]
    			]
    		]
    	];

    	$this->assertSame($expectedTree, $newTree);
    }
	

    /** @test */
    //NOTE:
    //the exptected result in this test case will be dependent on the updated tree in above test case "test_create_node_M_as_A_child"
    public function test_move_A_branch_as_C_child(){
    	//move node A and children under node C, and C will have 6 children in total (two direct children I and A)
    	$sourceNodeId = 1;

    	$targetNode = [
    		'parent_id' => 3,
    		'store_name' => 'A',
    		'store_state' => 'NSW'
    	];

    	$treeInstance = new Tree(self::$db);
    	$sourceNode = $treeInstance->viewNode($sourceNodeId);

    	$result = $treeInstance->update($sourceNode, $targetNode);

    	$newTree = $result['new_tree'];

    	$expectedTree = [
    		//node B
    		[
    			'id' => 2, 'parent_id' => 0, 'store_name' => 'B', 'store_state' => 'VIC', 
    			'children' => [
    				[
    					'id' => 6, 'parent_id' => 2, 'store_name' => 'F', 'store_state' => 'TAS'
    				],
    				[
    					'id' => 7, 'parent_id' => 2, 'store_name' => 'G', 'store_state' => 'NSW',
    					'children' => [
    						[
    							'id' => 12, 'parent_id' => 7, 'store_name' => 'L', 'store_state' => 'WA'
    						]
    					]
    				],
    				[
    					'id' => 8, 'parent_id' => 2, 'store_name' => 'H', 'store_state' => 'QLD'
    				]
    			]
    		],
    		//node C
    		[
    			'id' => 3, 'parent_id' => 0, 'store_name' => 'C', 'store_state' => 'WA', 
    			'children' => [
    				[
    					'id' => 1, 'parent_id' => 3, 'store_name' => 'A', 'store_state' => 'NSW', 
    					'children' => [
    						[
    							'id' => 4, 'parent_id' => 1, 'store_name' => 'D', 'store_state' => 'QLD'
    						],
    						[
    							'id' => 5, 'parent_id' => 1, 'store_name' => 'E', 'store_state' => 'NSW'
    						],
    						[
	    						'id' => 13, 'parent_id' => 1, 'store_name' => 'M', 'store_state' => 'NSW'
	    					]
	    				]
    				],
    				[
    					'id' => 9, 'parent_id' => 3, 'store_name' => 'I', 'store_state' => 'NSW', 
    					'children' => [
    						[
    							'id' => 10, 'parent_id' => 9, 'store_name' => 'J', 'store_state' => 'VIC'
    						],
    						[
    							'id' => 11, 'parent_id' => 9, 'store_name' => 'K', 'store_state' => 'NSW'
    						]
    					]
    				]
    			]
    		]
    	];

    	$this->assertSame($expectedTree, $newTree);
    }

    /** @test */
    //NOTE:
    //the exptected result in this test case will be dependent on the updated tree in above test case "test_move_A_branch_as_C_child"
    public function test_delete_C_branch_and_children(){
    	//delete B and its children (F, G, L, H), the root will only have two direct children A and C
    	$nodeId = 3;

    	$treeInstance = new Tree(self::$db);

    	$tree = $treeInstance->delete($nodeId);

    	$newTree = $tree['new_tree'];

    	$expectedTree = [
    		[
    			'id' => 2, 'parent_id' => 0, 'store_name' => 'B', 'store_state' => 'VIC', 
    			'children' => [
    				[
    					'id' => 6, 'parent_id' => 2, 'store_name' => 'F', 'store_state' => 'TAS'
    				],
    				[
    					'id' => 7, 'parent_id' => 2, 'store_name' => 'G', 'store_state' => 'NSW',
    					'children' => [
    						[
    							'id' => 12, 'parent_id' => 7, 'store_name' => 'L', 'store_state' => 'WA'
    						]
    					]
    				],
    				[
    					'id' => 8, 'parent_id' => 2, 'store_name' => 'H', 'store_state' => 'QLD'
    				]
    			]
    		],
    	];

    	$this->assertSame($expectedTree, $newTree);
    }
}