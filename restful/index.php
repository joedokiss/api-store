<?php
require_once __DIR__.'/../vendor/autoload.php';

use Lib\DB;
use Lib\Restful;

$dbInstance = new DB();
$dbConn = $dbInstance->connect();

//initiate the Restful object
$restful = new Restful($dbConn);

//start the Restful
$restful->run();

/**
 * (1) create a store branch
 * (2) update a store branch
 * (3) delete a store branch along with all of its children
 * (4) move a store branch (along with all of its children) to a different store branch
 * (5) view all store branches with all of their children
 * (6) view one specific store branch with all of its children
 * (7) view one specific store branch without any children
 */