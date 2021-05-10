<?php

use Psr\Http\Message\ResponseInterface as Response;


abstract class DefaultMessages{
    const E_WRONG_PARAMS = "Wrong Params";
    const E_NO_USER = "No such user exists";
    const E_WRONG_PASS = "Wrong password";
    const E_USER_EXISTS = "User already exists";
}

function check_params(...$params){
    foreach ($params as $param){
        if ($param == null)
            return false;
    }
    return true;
}

/**
 * Add status and comment for easier debugging
 * @param Response $response response to fill
 * @param bool|int $status status of the operation(0 or 1 or bool)
 * @param string $comment comment on the operation
 * @return Response
 */
function add_json_status_and_comment(Response $response, bool|int $status, string $comment){
    $response->getBody()->write(json_encode(
        array(
            'status'=> $status,
            'comment' => $comment
        )
    ));
    return $response;
}

function add_json_status_and_custom(Response $response, bool|int $status, array $data = array()){
    $response->getBody()->write(json_encode(
        array(
            'status'=> $status,
            'data' => $data
        )
    ));
    return $response;
}

function &add_json_status(Response $response, $status){
    $response->getBody()->write(json_encode(
        array(
            'status' => $status,
        )
    ));
    return $response;
}