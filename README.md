## 1. About this program:
> the tree structure (see snapshot as example, which is used in this demo application) 
will be essentially converted into the array structure,
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
![tree_structure](https://user-images.githubusercontent.com/39091872/51833525-ce2bb100-234b-11e9-89b5-67959f8c53ed.png)

* Each element in the $nodes array stands for a store branch, and has a 'parent_id' indicating node's parent, 
which is used to organise the tree structure.
* Each element is saved into the database as a row.

## 2. How the demo works:
###### NOTE:
* for demonstration only, this application is using the basic HTTP authentication to authenticate users, 
the stronger approach can be adopted in the real word, eg. OAuth2
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
Action: delete
```
#### (5) view all store branches with all of their children
```
URI: /restful/branches
Action: index
```
#### (6) view one specific store branch with all of its children
#### (7) view one specific store branch without any children
```
URI: /restful/branches/{id}
Action: view
```
