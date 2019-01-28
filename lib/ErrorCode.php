<?php
namespace Lib;

class ErrorCode
{
    const USERNAME_EXISTS = 1;
    const PASSWORD_CANNOT_EMPTY = 2;
    const USERNAME_CANNOT_EMPTY = 3;
    const USERNAME_OR_PASSWORD_INVALID = 4;

    const PARENT_ID_IS_REQUIRED = 5;
    const STORE_NAME_IS_REQUIRED = 6;
    const STORE_STATE_IS_REQUIRED = 7;
    const NODE_ID_IS_REQUIRED = 8;
    const NODE_NOT_FOUND = 9;

    const BRANCH_CREATE_FAIL = 10;
    const BRANCH_UPDATE_FAIL = 11;
    const BRANCH_DELETE_FAIL = 12;
    const STORE_NAME_IS_EXISTED = 13;   
    const ROOT_CANNOT_BE_DELETED = 14;
    const ROOT_CANNOT_BE_UPDATED = 15;

    //server internal errors including SQL execution failure
    const SERVER_INTERNAL_ERROR = 16; 
}

