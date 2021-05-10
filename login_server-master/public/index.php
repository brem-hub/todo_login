<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../scripts/debugging.php';
require __DIR__ . '/../scripts/mysql_manager.php';
require __DIR__ . '/../scripts/global_methods.php';

$sql_manager = new MySqlManager("localhost", "root", "", "workdb");

$app = AppFactory::create();
$app->setBasePath('/php_login_server');
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();


$app->post('/login', function (Request $request, Response $response) use($sql_manager){

    $args = (array)$request->getParsedBody();

    $login = $args['login'];
    $password = $args['password'];

    if (!check_params($login, $password)) {
        return add_json_status($response, 0);
    }

    $user = $sql_manager->get_user($login);

    if ($user == false){
        print "Could not extract user from db";
        return add_json_status($response, 0);
    }

    $hash = $user['password'];

    if (password_verify($password, $hash)){
        return add_json_status($response, 1);
    }

    print "Password is incorrect";
    return add_json_status($response, 0);
});

$app->post('/register', function (Request $request, Response $response) use($sql_manager){

    $args = (array)$request->getParsedBody();

    $login = $args['login'];
    $password = $args['password'];
    $email = $args['email'];
    $user_type = $args['user_type'];

    if (!check_params($login, $password, $email, $user_type)) {
        print "Could not parse params";
        return add_json_status($response, 0);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if($sql_manager->register_user($login, $hash, $email, $user_type) == false){
        print "Could not register user";
        return add_json_status($response, 0);
    }
    print "Successful";
    return add_json_status($response, 1);
});

$app->post('/create_task', function (Request $request, Response $response) use($sql_manager) {
    $params = (array)$request->getParsedBody();

    $contractor_login = $params['login'];
    $task_info = $params['task_info'];

    if (!check_params($contractor_login, $task_info))
        return add_json_status($response, 0);

    if ($sql_manager->create_task($contractor_login, $task_info) == false)
        $response = add_json_status($response, 0);
    else
        $response = add_json_status($response, 1);
    return $response;
});

$app->post('/create_team', function (Request $request, Response $response) use($sql_manager){

    $args = (array)$request->getParsedBody();

    $name = $args['name'];
    $customer = $args['customer'];

    if(!check_params($name, $customer)) return add_json_status($response, 0);

    if (!$sql_manager->create_team($name, $customer)) return add_json_status($response, 0);

    return add_json_status($response,1);
});

$app->post('/check_team', function (Request $request, Response $response) use($sql_manager){
   $args = (array)$request->getParsedBody();

   $username = $args['username'];
   $team = $args['team_name'];

   if (!check_params($username, $team)) return add_json_status($response, 0);

   return add_json_status(
       $response,
       $sql_manager->is_user_in_team($username, $team)? 1 : 0
   );

});

$app->post('/get_tasks', function (Request $request, Response $response) use($sql_manager){

    $args = (array)$request->getParsedBody();

    $username = $args['username'];
    if (!check_params($username)) return add_json_status($response, 0);

    $tasks = $sql_manager->get_tasks($username);
    if ($tasks == false) return add_json_status($response, 0);

    $response->getBody()->write(json_encode(array_values($tasks)));
    return $response;
});

$app->post('/add_team', function (Request $request, Response $response) use ($sql_manager){

    $args = (array)$request->getParsedBody();

    $username = $args['username'];
    $team_name = $args['team_name'];

    if (!check_params($username, $team_name)) return add_json_status($response, 0);

    if (!$sql_manager->set_team($username, $team_name))
        return add_json_status($response, 0);
    return add_json_status($response, 1);
});

$app->post('/change_status', function (Request $request, Response $response) use($sql_manager){

    $args = (array)$request->getParsedBody();

    $task_name = $args['task_name'];
    $status = $args['status'];

    if(!check_params($task_name, $status)) return add_json_status($response, 0);

    if (!$sql_manager->change_task_type($task_name, $status))
        return add_json_status($response, 0);
    return add_json_status($response, 1);
});

try{
    $app->run();
}catch (Exception $e){
    print "Exception occurred: " . $e->getMessage();
}
