<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../scripts/debugging.php';
require __DIR__ . '/../scripts/SqlManager.php';
require __DIR__ . '/../scripts/global_methods.php';
require __DIR__ . '/../scripts/Mailer.php';

$sql_manager = new SqlManager("localhost", "root", "root", "todo");

$app = AppFactory::create();
//TODO: Change on deploy!!!
$app->setBasePath('/todo/public');
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->post('/login', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $login = $args['userLogin'];
    $password = $args['userPassword'];

    if (!check_params($login, $password))
        return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $user = $sql_manager->get_user_wrapper($login);
    if (is_string($user)) return add_json_status_and_comment($response, 0, $user);
    if (is_null($user) or count($user) == 0) return add_json_status_and_comment($response, 0, DefaultMessages::E_NO_USER);

    $hashed_password = $user['hashed_password'];
    if (password_verify($password, $hashed_password))
        return add_json_status_and_custom($response, 1, array($user['type']));

    return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PASS);
});

$app->post('/register', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $login = $args['userLogin'];
    $password = $args['userPassword'];
    $email = $args['userEmail'];
    $user_type = $args['userType'];

    if (!check_params($login, $password, $email, $user_type))
        return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);


    $user = $sql_manager->get_user_wrapper($login);
    if (is_string($user)) return add_json_status_and_comment($response, 0, $user);
    if (!is_null($user) and count($user) != 0) return add_json_status_and_comment($response, 0, DefaultMessages::E_USER_EXISTS);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $res = $sql_manager->register_user($login, $hash, array('email' => $email, 'type' => $user_type));

    if ($res !== true)
        return add_json_status_and_comment($response, 0, $res);

    return add_json_status($response, 1);
});

$app->post('/create-task', function (Request $request, Response $response) use ($sql_manager) {
    $params = (array)$request->getParsedBody();

    $customer_login = $params['customerLogin'];
    $task_info = $params['taskInfo'];

    if (!check_params($customer_login, $task_info))
        return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->create_task($customer_login, $task_info);
    if ($res !== true)
        return add_json_status_and_comment($response, 0, $res);
    else
        return add_json_status($response, 1);
});


$app->post('/assign-task', function (Request $request, Response $response) use ($sql_manager) {
    $params = (array)$request->getParsedBody();

    $task_title = $params['taskTitle'];
    $user_login = $params['userLogin'];

    if (!check_params($task_title, $user_login))
        return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->assign_contractor_to_task($user_login, $task_title);

    if ($res !== true) return add_json_status_and_comment($response, 0, $res);

    return add_json_status($response, 1);
});


$app->post('/create-team', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $team_info = $args['teamInfo'];
    $customer = $args['customerLogin'];

    if (!check_params($team_info, $customer)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);


    $res = $sql_manager->create_team($customer, $team_info);

    if ($res !== true)
        return add_json_status_and_comment($response, 0, $res);
    else
        return add_json_status($response, 1);
});

$app->post('/check-team', function (Request $request, Response $response) use ($sql_manager) {
    $args = (array)$request->getParsedBody();
    var_dump("teast");

    $username = $args['userLogin'];
    $team = $args['teamTitle'];

    if (!check_params($username, $team)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->check_user_in_team($username, $team);
    if (is_bool($res))
        return add_json_status_and_custom($response, 1, array($res ? 1 : 0));
    else
        return add_json_status_and_comment($response, 0, $res);

});

$app->post('/get-task', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $task_title = $args['taskTitle'];
    if (!check_params($task_title)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $task = $sql_manager->get_task_wrapper($task_title);

    if (is_string($task)) return add_json_status_and_comment($response, 0, $task);
    if (is_null($task) or count($task) == 0) return add_json_status_and_comment($response, 0, "Task does not exist");

    return add_json_status_and_custom($response, 1, $task);
});

$app->post('/get-contractor-teams', function (Request $request, Response $response) use ($sql_manager){
    $args = (array)$request->getParsedBody();

    $username = $args['userLogin'];
    if (!check_params($username)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $teams = $sql_manager->get_teams_for_user($username);
    if ($teams[0] == false) return add_json_status_and_comment($response, 0, $teams[1]);

    if (count($teams[1]) == 0) return add_json_status_and_comment($response, 0, "Tasks not found");
    return add_json_status_and_custom($response, 1, $teams[1]);
});

$app->post('/get-tasks', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $username = $args['userLogin'];
    if (!check_params($username)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $tasks = $sql_manager->get_tasks($username);
    if ($tasks[0] == false) return add_json_status_and_comment($response, 0, $tasks[1]);

    if (count($tasks[1]) == 0) return add_json_status_and_comment($response, 0, "Tasks not found");

    return add_json_status_and_custom($response, 1, $tasks[1]);
});

$app->post('/assign-team', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $username = $args['userLogin'];
    $team_name = $args['teamTitle'];

    if (!check_params($username, $team_name)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->assign_contractor_to_team($username, $team_name);

    if ($res !== true) return add_json_status_and_comment($response, 0, $res);

    return add_json_status($response, 1);
});

$app->post('/change-status', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $task_name = $args['taskTitle'];
    $status = $args['newStatus'];

    if (!check_params($task_name, $status)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->change_task_type($task_name, $status);
    if (is_string($res)) return add_json_status_and_comment($response, 0, $res);

    return add_json_status_and_custom($response, 1, array((int)$res));
});

$app->post('/delete-cont-team', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $user_login = $args['userLogin'];
    $team_title = $args['teamTitle'];

    if (!check_params($user_login, $team_title)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->delete_contractor_from_team($user_login, $team_title);
    if (is_string($res)) return add_json_status_and_comment($response, 0, $res);
    return add_json_status_and_custom($response, 1, array((int)$res));
});

$app->post('/contractors-by-customer', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $user_login = $args['userLogin'];

    if (!check_params($user_login)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $res = $sql_manager->get_contractors_for_customer($user_login);
    if ($res[0] == false) return add_json_status_and_comment($response, 0, $res[1]);

    return add_json_status_and_custom($response, 1, $res[1]);
});

$app->post('/recover-password', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $user_login = $args['userLogin'];

    if (!check_params($user_login)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);

    $new_password = randomPassword(8);

    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $customer = $sql_manager->get_user_wrapper($user_login);
    if (is_string($customer)) return add_json_status_and_comment($response, 0, $customer);
    if (is_null($customer) or count($customer) == 0) return add_json_status_and_comment($response, 0, "User does not exist");

    $res = $sql_manager->set_new_password($user_login, $hash);

    if ($res) {
        $mailer = new Mailer();
        $res = $mailer->send_recovery_mail($customer['email'], $new_password);
    }

    return add_json_status($response, (int)$res);
});

$app->post('/change-password', function (Request $request, Response $response) use ($sql_manager) {

    $args = (array)$request->getParsedBody();

    $user_login = $args['userLogin'];
    $new_password = $args['newPassword'];

    if (!check_params($user_login)) return add_json_status_and_comment($response, 0, DefaultMessages::E_WRONG_PARAMS);


    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $customer = $sql_manager->get_user_wrapper($user_login);
    if (is_string($customer)) return add_json_status_and_comment($response, 0, $customer);
    if (is_null($customer) or count($customer) == 0) return add_json_status_and_comment($response, 0, "User does not exist");

    $res = $sql_manager->set_new_password($user_login, $hash);
    if (is_bool($res))
        return add_json_status($response, (int)$res);
    return add_json_status_and_comment($response, 0, $res);
});


// Initiate application
try {
    $app->run();
} catch (Exception $e) {
    print "Exception occurred: " . $e->getMessage() . " " . $e->getFile() . ":" . $e->getLine() . "\n";
    print $e->getTraceAsString();
}
