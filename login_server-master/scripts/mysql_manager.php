<?php

class MySqlManager
{
    private $link;

    function __construct($hostname, $username, $pass, $dbname)
    {
        $this->link = mysqli_connect($hostname, $username, $pass, $dbname);
        if ($this->link == false) {
            print("Could not open connection " . mysqli_connect_error() . "\n");
        } else {
            print("Connection was established\n");
        }
    }

    function register_user($login, $password, $email, $type)
    {
        if (!$this->check_link()) return false;

        $query = 'INSERT INTO users (login, email, password, type) VALUES (?, ?, ?, ?)';

        $stmt = mysqli_stmt_init($this->link);

        if (!mysqli_stmt_prepare($stmt, $query)) {
            print "Failed to prepare statement\n";
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ssss',
            $login,
            $email,
            $password,
            $type);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);


        if ($result == false && $stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }

        return true;
    }

    function check_user_in_customer_team($customer, $username)
    {
        if (!$this->check_link()) return false;

        $customer_teams = $this->get_user_teams($customer);

        foreach ($customer_teams as $team) {
            if ($this->is_user_in_team($username, $team['name']))
                return true;
        }
        return false;
    }

    function get_user_teams($user)
    {
        $query = 'SELECT * FROM teams WHERE user_id = ?';

        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        mysqli_stmt_bind_param($stmt, 'i', $user['id']);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if ($res == false) return false;

        return mysqli_fetch_all($res);
    }

    function get_user($username)
    {
        if (!$this->check_link()) return false;

        $query = 'SELECT * from users WHERE login = ?';

        $stmt = mysqli_stmt_init($this->link);

        if (!mysqli_stmt_prepare($stmt, $query)) {
            print "Failed to prepare statement\n";
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);

        $results = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($results) == 0) return false;

        return mysqli_fetch_array($results);
    }

    function create_task($username, $task_info)
    {
        if (!$this->check_link()) return false;

        $query = 'INSERT INTO tasks (name, owner, contractor, description, finish_date, status) VALUES (?, ?, ?, ?, ?, ?)';

        $stmt = mysqli_stmt_init($this->link);

        if (!$stmt->prepare($query)) {
            print "Failed to prepare statement\n";
            var_dump($stmt->error);
            return false;
        }

        $owner = $this->get_user($username);
        $contractor = $this->get_user($task_info['contractor']);

        if ($owner == false) {
            print "Could not get owner from DB\n";
            return false;
        }

        if ($contractor == false) {
            print "Could not get contractor from DB\n";
            return false;
        }


        if (!$this->check_user_in_customer_team($owner, $contractor['login'])) {
            print "Contractor is not in the team of the customer";
            return false;
        }

        $stmt->bind_param('siisss',
            $task_info['task_name'],
            $owner['id'],
            $contractor['id'],
            $task_info['description'],
            $task_info['date'],
            $task_info['status']
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($result == false && $stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }

        return true;
    }

    function create_team($team_name, $customer_name)
    {
        if (!$this->check_link()) return false;

        $customer = $this->get_user($customer_name);

        if ($customer == false) {
            print "Could not get contractor";
            return false;
        }

        $next_id = $this->get_max_number() + 1;
        $query = 'INSERT INTO teams (id, name, user_id) VALUE (?, ?, ?)';

        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        mysqli_stmt_bind_param($stmt, 'isi', $next_id, $team_name, $customer['id']);
        mysqli_stmt_execute($stmt);

        if ($stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);


        if ($result == false && $stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }
        return true;
    }

    function get_max_number()
    {

        $query = "SELECT id FROM teams";


        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);


        $arr = mysqli_fetch_all($res);
        sort($arr);

        if (count($arr) == 0) return 0;

        return (int)$arr[count($arr) - 1];
    }

    function set_team($username, $team_name)
    {
        if (!$this->check_link()) return false;

        // check if team exists.
        $team = $this->get_any_team_by_name($team_name);

        if ($team == false) {
            print "Team does not exist";
            return false;
        }

        $user = $this->get_user($username);

        if ($user == false) {
            print "User does not exist";
            return false;
        }

        $query = "INSERT INTO teams (id, name, user_id) VALUES (?, ?, ?)";

        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        mysqli_stmt_bind_param($stmt, 'isi', $team['id'], $team['name'], $user['id']);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);

        if ($res == false && $stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }

        return true;
    }

    function get_any_team_by_name($team_name)
    {
        $query = "SELECT * FROM teams WHERE name = ?";

        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        mysqli_stmt_bind_param($stmt, 'i', $team_name);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if ($res == false) return false;

        return mysqli_fetch_array($res);
    }

    function is_user_in_team($username, $team_name)
    {
        if (!$this->check_link()) return false;

        $user = $this->get_user($username);
        if ($user == false) {
            print "Could not get user from DB";
            return false;
        }

        $request = "SELECT * FROM teams WHERE user_id = ? AND name = ?";

        $stmt = $this->prepare_stmt($request);
        if ($stmt == false) return false;

        mysqli_stmt_bind_param($stmt, 'is', $user['id'], $team_name);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        // I dunno, but it does not work.
        //$res = $this->select_from_db($a, "i", $user['id']);
        if ($res == false || $res->num_rows == 0) return false;

        return true;
    }

    function get_tasks($username)
    {
        if (!$this->check_link()) return false;

        $user = $this->get_user($username);

        if ($user == false) return false;

        $query = 'SELECT * FROM tasks WHERE contractor = ?';

        $stmt = mysqli_prepare($this->link, $query);

        $user_id = (int)$user['id'];
        mysqli_stmt_bind_param($stmt, 'i', $user_id);

        $stmt->execute();
        $res = mysqli_stmt_get_result($stmt);

        if ($res == false) {
            print $stmt->error;
            return false;
        }

        $tasks = array();
        $data = mysqli_fetch_all($res, MYSQLI_ASSOC);
        foreach ($data as $task) {
            $task_data = array(
                "description" => $task['description'],
                "name" => $task['name'],
                "date" => $task['finish_date'],
                "status" => $task['status'] == "in_progress" ?
                    "overdue" : $task['status']
                );
            array_push($tasks, $task_data);
        }
        return $tasks;
    }

    function change_task_type($task_name, $status)
    {
        if (!$this->check_link()) return false;

        $query = 'UPDATE tasks SET status = ? WHERE name = ?';

        $stmt = mysqli_prepare($this->link, $query);
        if ($stmt == false) {
            print $this->link->error . "\n";
            print "Could not prepare statement";
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ss', $status, $task_name);

        $stmt->execute();

        var_dump($stmt);
        if ($stmt->affected_rows != 0) return true;
        return false;
    }

    private function check_link()
    {
        if ($this->link == false) {
            print "Connection was not open";
            return false;
        }
        return true;
    }

    private function prepare_stmt($query)
    {
        $stmt = mysqli_stmt_init($this->link);

        if (!mysqli_stmt_prepare($stmt, $query)) {
            print "Failed to prepare statement\n";
            print $stmt->error . "\n";
            return false;
        }
        return $stmt;
    }

    /// These methods don't work somehow.
   /* private function select_from_db($query, $types, &$var_1, &...$_)
    {
        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        if (!$this->execute_query($stmt, $types, $var_1, $_)) return false;

        $results = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($results) == 0) return false;

        return mysqli_fetch_all($results);
    }

    private function insert_into_db($query, $types, &$var_1, &...$_)
    {
        $stmt = $this->prepare_stmt($query);
        if ($stmt == false) return false;

        if (!$this->execute_query($stmt, $types, $var_1, $_)) return false;
        $result = mysqli_stmt_get_result($stmt);

        if ($result == false && $stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }
        return true;
    }

    private function execute_query($stmt, $types, &$var1, &...$_)
    {
        mysqli_stmt_bind_param($stmt, $types, $var1, $_);
        mysqli_stmt_execute($stmt);

        if ($stmt->errno != 0) {
            var_dump($stmt->error);
            return false;
        }
        return true;
    }*/
}