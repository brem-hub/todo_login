<?php

use Psr\Http\Message\ResponseInterface as Response;

function check_params(...$params){
    foreach ($params as $param){
        if ($param == null)
            return false;
    }
    return true;
}

function &add_json_status(Response $response, $status){
    $response->getBody()->write(json_encode(
        array(
            'status' => $status,
        )
    ));
    return $response;
}