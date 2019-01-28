## 1. About this program:
> the program is built by using native PHP other than any third party library

> the tree structure (see snapshot 1 as example, which is used in this demo application) 
will be essentially saved as the array structure (eg. array $nodes),
and the tree will be built as (see snapshot 2 as example)
```
$nodes = [
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
```
###### snapshot 1
![tree_structure](https://user-images.githubusercontent.com/39091872/51833525-ce2bb100-234b-11e9-89b5-67959f8c53ed.png)

###### snapshot 2
![2019-01-29_9-04-35](https://user-images.githubusercontent.com/39091872/51869564-090e0300-23a5-11e9-83a7-aa2f4b9d1c0b.png)

* Each element in the $nodes array stands for a store branch, and has a 'parent_id' indicating node's parent, 
which is used to organise the tree structure.
* Each element in the $nodes array is saved into the database as a row.
* after the tree is built, each element will have a key "children" to store the children nodes (if any).

## 2. About codes:
* the core scripts are "/lib/Restful.php" and "/lib/Tree.php".
* the "restful.php" is the main entrance to respond the API call, which will take care of the API process (eg. determine the HTTP method and set up the requested resource, and so forth) and call the corresponding functionalities.
* the "Tree.php" is contiaining all logics in relation to the tree structure, the are two properties $this->nodes (save the nodes array in snapshot 1 from the database) and $this->tree (save the generated tree in shanpshot 2 based on $this->nodes).

## 3. How the demo works:
###### NOTE:
* for demonstration only, this application is using the basic HTTP authentication to authenticate users, 
the better solution can be adopted in the real word, eg. OAuth2
* the demo only supports "logon" without "register" feature.
* the authentication will be performed prior to run through the API.
* the API is tested via Postman app.
* the SQL statements (tables - "users" and "branches") can be found in 
[SQL repository](https://github.com/joedokiss/api-store/tree/master/sql)
* testing username/password: admin

###### Steps:
* move to the folder '/api-store' after clone the repository
* run the command in CLI: php -S localhost:8000
* call the following desired API via Postman (or other testing tools)

#### (1) create a store branch
```
URI: /restful/branches
Method: POST
Action: create
```
eg. pass the body parameters like
```
{
  "parent_id":1,
  "store_name":"M",
  "store_state":"VIC"
}
```
#### (2) update a store branch
#### (4) move a store branch (along with all of its children) to a different store branch
###### NOTE:
combine both (2) and (4) in one place, because as long as the node's "parent_id" is changed, 
all of its children will be moved, which can be considered as part of "update", 
in case the "parent_id" remains the same, the node will not be moved, the other information can be updated instead.

```
URI: /restful/branches/{id}
Method: PUT
Action: update
```
eg. this sample will move the node A and all its children under C 
```
/restful/branches/1
{
 "parent_id":3
}
```
eg. this sample will update node A's details
```
/restful/branches/1
{
 "store_name":"AA",
 "store_state":"QLD"
}
```

#### (3) delete a store branch along with all of its children
```
URI: /restful/branches/{id}
Method: DELETE
Action: delete
```
eg. this sample will delete node B and all its children (if any)
```
/restful/branches/3
```
#### (5) view all store branches with all of their children
```
URI: /restful/branches
Method: GET
```
eg. 
```
/restful/branches
```
#### (6) view one specific store branch with all of its children
#### (7) view one specific store branch without any children
```
URI: /restful/branches/{id}
Method: GET
Action: view
```
eg. it will outline the subtree (node B as root including all its children, if any) 
```
/restful/branches/2
{
  "children":"true"
}
```

> NOTE: with/without children can be controlled by passing parameter "children" ("true" is with children, "false" is "without children")

eg. this example tends to view store (node A) and assuming it has no children, in which case, the API will respond an array having all available nodes without the children 
```
/restful/branches/1
{
  "children":"false"
}
```
## 4. Notes to unit test:
the last tree test cases are dependent because they were testing the real actions other than leveraging the mocking any more, you may consider to test those one by one (roll back the database to original status with raw data every time) other than at once, but the following test sequence will work anyway.
```
* test_create_node_M_as_A_child
* test_move_A_branch_as_C_child
* test_delete_C_branch_and_children
```
