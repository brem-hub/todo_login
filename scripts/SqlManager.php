<?php /** @noinspection SqlResolve */


class SqlManager
{
    //TODO: remove type for compatibility with PHP7.2
    private PDO $connection;

    /**
     * @param $hostname
     * @param $username
     * @param $password
     * @param $dbname
     * @throws Exception
     */
    function __construct($hostname, $username, $password, $dbname)
    {
        try {
            $url = "mysql:host=" . $hostname . ";dbname=" . $dbname;
            $this->connection = new PDO($url, $username, $password);

        } catch (PDOException $e) {
            throw new Exception("Could not create connection with mysql: " . $e->getMessage());
        }
    }

    /**
     * @param $username
     * @return array|false
     */
    function get_user($username)
    {

        $query = self::query_set_table_names('SELECT * from __l_users__ WHERE login = :login');

        $params = [
            ":login" => $username
        ];

        return $this->execute_sql_select($query, $params);
        /*$stmt = $this->connection->prepare($query);

        if ($stmt->execute($params))
            return array(true, $stmt->fetchAll(PDO::FETCH_ASSOC));

        return array(false, $stmt->errorInfo());*/
    }


    // Rework totally, no need to this ternary check, anyway we check type after - no reason to check it here.
    //checked
    public function get_user_wrapper($username)
    {
        $user = $this->get_user($username);
        return $user[0] ? $user[1][0] : $user[1];
    }

    //checked
    public function get_user_by_id($user_id){
        $query = self::query_set_table_names('SELECT * from __l_users__ WHERE id = :id');
        $params = [
            ":id" => $user_id
        ];

        return $this->execute_sql_select($query, $params);
    }

    /**
     * @param $team_name
     * @return array|false
     */
    private function get_team($team_name)
    {

        $query = self::query_set_table_names('SELECT * from __l_teams__ WHERE title = :title');

        $params = [
            ":title" => $team_name
        ];

        return $this->execute_sql_select($query, $params);
    }


    //checked
    public function get_team_wrapper($team_name)
    {
        $team = $this->get_team($team_name);

        return $team[0] ? $team[1][0] : $team[1];
    }


    private function get_task($task_name){
        $query = self::query_set_table_names('SELECT * from __l_tasks__ WHERE title = :title');

        $params = [
            ":title" => $task_name
        ];
        return $this->execute_sql_select($query, $params);
    }

    //checked
    function get_task_wrapper($task_title){
        $task = $this->get_task($task_title);

        return $task[0] ? $task[1][0] : $task[1];
    }

    function get_tasks($username)
    {
        $contractor = $this->get_user_wrapper($username);
        if(is_string($contractor)) return $contractor;
        if (is_null($contractor) or count($contractor) == 0) return "Contractor does not exist";


        $query = self::query_set_table_names('SELECT * from __l_cont_tasks__ WHERE contractor = :login');

        $params = [
            ":login" => $contractor['id']
        ];

        return $this->execute_sql_select($query, $params);
    }

    /**
     * @param $login
     * @param $hashed_password
     * @param $stats
     * @return array|bool
     */
    function register_user($login, $hashed_password, $stats)
    {

        if ($stats["type"] != "contractor" and $stats["type"] != "customer")
            return "Incorrect type of the user";

        $query = $query = self::query_set_table_names(
            "INSERT INTO __l_users__ (login, email, hashed_password, type) VALUES (:login, :email, :password, :type)");

        $params = [
            ":login" => $login,
            ":email" => $stats["email"],
            ":password" => $hashed_password,
            ":type" => $stats["type"]
        ];

        $stmt = $this->connection->prepare($query);
        if ($stmt->execute($params))
            return true;
        return $stmt->errorInfo();
    }

    /**
     * @param $owner_login
     * @param $task_info
     * @return array|bool|string
     */
    function create_task($owner_login, $task_info)
    {
        $owner = $this->get_user_wrapper($owner_login);
        if(is_string($owner)) return $owner;
        if (is_null($owner) or count($owner) == 0) return "Customer does not exist";

        if ($owner['type'] != "customer")
            return "Cannot create task, user is not customer";

        if (!key_exists('title', $task_info))
            return "Title could not be null";

        if (key_exists('beginDate', $task_info))
            $begin_date = (new DateTime($task_info['beginDate']))->format(self::DB_DT_FORMAT);
        else
            $begin_date = date(self::DB_DT_FORMAT);

        if (!key_exists('finishDate', $task_info))
            return "Finish date should be specified";

        try {
            $finish_date = (new DateTime($task_info['finishDate']))->format(self::DB_DT_FORMAT);
        } catch (Exception) {
            return "Finish date was specified incorrectly";
        }

        $comment = key_exists('comment', $task_info) ? $task_info['comment'] : null;
        $status = key_exists('status', $task_info) ? $task_info['status'] : 'initiated';

        $query = self::query_set_table_names('INSERT INTO __l_tasks__ (title, description, begin_date, finish_date, comment, status, owner) VALUES (:title, :desc, :begin, :finish, :comment, :status, :owner)');

        $params = [
            ":title" => $task_info['title'],
            ":desc" => $task_info['description'],
            ":begin" => $begin_date,
            ":finish" => $finish_date,
            ":comment" => $comment,
            ":status" => $status,
            ":owner" => $owner['id']
        ];

        $stmt = $this->connection->prepare($query);
        if ($stmt->execute($params))
            return true;
        return $stmt->errorInfo();
    }

    function create_team($owner_login, $team_info)
    {
        $owner = $this->get_user_wrapper($owner_login);
        if(is_string($owner)) return $owner;
        if (is_null($owner) or count($owner) == 0) return "Customer does not exist";

        if ($owner['type'] != "customer")
            return "Cannot create team, user is not customer";

        if (!key_exists('title', $team_info))
            return "Title could not be null";

        $query = self::query_set_table_names('INSERT INTO __l_teams__ (title, description, owner) VALUES (:title, :desc, :owner)');

        $params = [
            ":title" => $team_info['title'],
            ":desc" => $team_info['description'],
            ":owner" => $owner['id']
        ];

        $stmt = $this->connection->prepare($query);
        if ($stmt->execute($params))
            return true;
        return $stmt->errorInfo();
    }



    public function check_user_in_team($user_login, $team_title)
    {
        $contractor = $this->get_user_wrapper($user_login);
        if(is_string($contractor)) return $contractor;
        if (is_null($contractor) or count($contractor) == 0) return "Contractor does not exist";

        $team = $this->get_team_wrapper($team_title);
        if(is_string($team)) return $team;
        if (is_null($team) or count($team) == 0) return "Team does not exist";

        $query = self::query_set_table_names("SELECT * FROM __l_cont_teams__ WHERE contractor = :login AND team = :team");

        $params = [
            ":login" => $contractor['id'],
            ":team" => $team['id']
        ];


        $data = $this->execute_sql_select($query, $params);

        if ($data == false)
            return $data[1];
        $data = $data[1];

        return count($data) > 0;
    }

    public function check_user_has_task($contractor, $task){


        $query = self::query_set_table_names("SELECT * FROM __l_cont_tasks__ WHERE contractor = :login AND task = :task");

        $params = [
            ":login" => $contractor['id'],
            ":task" => $task['id']
        ];

        $data = $this->execute_sql_select($query, $params);

        if ($data == false)
            return $data[1];
        $data = $data[1];

        return count($data) > 0;
    }

    public function assign_contractor_to_team($user_login, $team_title)
    {
        $contractor = $this->get_user_wrapper($user_login);
        if(is_string($contractor)) return $contractor;
        if (is_null($contractor) or count($contractor) == 0) return "Contractor does not exist";

        $team = $this->get_team_wrapper($team_title);
        if(is_string($team)) return $team;
        if (is_null($team) or count($team) == 0) return "Team does not exist";


        $teams = $this->check_user_in_team($user_login, $team_title);
        if (is_bool($teams) and $teams)
            return "Contractor is already in the team";


        $query = self::query_set_table_names("INSERT INTO __l_cont_teams__ (contractor, team) VALUES(:login, :team)");

        $params = [
            ":login" => $contractor['id'],
            ":team" => $team['id']
        ];

        $stmt = $this->connection->prepare($query);

        if (!$stmt->execute($params))
            return $stmt->errorInfo();

        return true;
    }

    public function check_user_in_customer_team($user, $customer_id){
        $teams = $this->get_customer_teams_by_id($customer_id);
        if ($teams[0] == false)
            return false;

        foreach ($teams[1] as $team){
            if ($this->check_user_in_team($user['login'], $team['title']) === true)
                return true;
        }
        return false;
    }

    public function assign_contractor_to_task($user_login, $task_title)
    {
        $contractor = $this->get_user_wrapper($user_login);
        if(is_string($contractor)) return $contractor;
        if (is_null($contractor) or count($contractor) == 0) return "Contractor does not exist";

        $task = $this->get_task_wrapper($task_title);
        if(is_string($task)) return $task;
        if(is_null($task) or count($task) == 0) return "Task does not exist";


        $flag = $this->check_user_has_task($contractor, $task);
        if (is_bool($flag) and $flag)
            return "Contractor already has this task";

        if (!$this->check_user_in_customer_team($contractor, $task['owner']))
            return "Contractor is not in the team of the customer";

        $query = self::query_set_table_names("INSERT INTO __l_cont_tasks__ (contractor, task) VALUES(:login, :task)");

        $params = [
            ":login" => $contractor['id'],
            ":task" => $task['id']
        ];

        $stmt = $this->connection->prepare($query);

        if (!$stmt->execute($params))
            return $stmt->errorInfo();

        return true;

    }

    public function change_task_type($task_title, $status)
    {
        $task = $this->get_task($task_title);

        if ($task[0] == false) return $task[1];
        if (count($task[1]) == 0) return "Task does not exist";
        $task = $task[1][0];

        $query = self::query_set_table_names('UPDATE __l_tasks__ SET status = :status WHERE title = :title');

        $params = [
            ":status" => $status,
            ":title" => $task_title
        ];

        $stmt = $this->connection->prepare($query);

        if (!$stmt->execute($params))
            return $stmt->errorInfo();

        if ($stmt->rowCount() != 0) return true;
        return false;
    }


    public function delete_contractor_from_team($user_login, $team_title)
    {
        $contractor = $this->get_user_wrapper($user_login);
        if(is_string($contractor)) return $contractor;
        if (is_null($contractor) or count($contractor) == 0) return "Contractor does not exist";


        $team = $this->get_team_wrapper($team_title);
        if(is_string($team)) return $team;
        if (is_null($team) or count($contractor) == 0) return "Team does not exist";

        $query = self::query_set_table_names("DELETE FROM __l_cont_teams__ WHERE contractor= :login AND team= :team");

        $params = [
            ":login" => $contractor['id'],
            ":team" => $team['id']
        ];

        $stmt = $this->connection->prepare($query);

        if (!$stmt->execute($params))
            return $stmt->errorInfo();

        if ($stmt->rowCount() != 0) return true;
        return false;
    }



    public function get_customer_teams($customer_login){
        $customer = $this->get_user_wrapper($customer_login);
        if(is_string($customer)) return array(false, $customer);
        if (is_null($customer) or count($customer) == 0) return array(false, "Customer does not exist");

        $query = self::query_set_table_names('SELECT id FROM __l_teams__ WHERE owner= :login');
        $params = [
            ":login" => $customer['id'],
        ];

        return $this->execute_sql_select($query, $params);
    }

    private function get_customer_teams_by_id($customer_id){
        $customer = $this->get_user_by_id($customer_id);
        if(is_string($customer)) return array(false, $customer);
        if (is_null($customer) or count($customer) == 0) return array(false, "Customer does not exist");

        $query = self::query_set_table_names('SELECT * FROM __l_teams__ WHERE owner= :login');
        $params = [
            ":login" => $customer[1][0]['id'],
        ];

        return $this->execute_sql_select($query, $params);
    }


    private function get_contractors_for_team($team){

        // Team MUST BE

        $query = self::query_set_table_names('SELECT contractor FROM __l_cont_teams__ WHERE team = :title');
        $params = [
            ":title" => $team['id']
        ];

        $res = $this->execute_sql_select($query, $params);
        if ($res[0] == false) return array();

        $users = array();
        foreach ($res[1] as $cont){
            //TODO: bad zone
            $users[] = $this->get_user_by_id($cont['contractor'])[1][0];
        }

        return $users;
    }

    public function get_contractors_for_customer($customer_login)
    {
        // get teams for customer
        // get contractors for each team
        // remove repeated

        $teams = $this->get_customer_teams($customer_login);

        if ($teams[0] == false) return array(false, $teams[1]);
        $teams = $teams[1];
        $contractors_arr = array();
        foreach ($teams as $team){
            $contractors = $this->get_contractors_for_team($team);
            foreach ($contractors as $contractor){
                if (!in_array($contractor, $contractors_arr))
                    $contractors_arr[] = $contractor;
            }
        }

        return array(true, $contractors_arr);
    }


    /**
     * @param $query
     * @param $params
     * @return array
     */
    public function execute_sql_select($query, $params){

        $stmt = $this->connection->prepare($query);

        if (!$stmt->execute($params))
            return array(false, $stmt->errorInfo());

        return array(true, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @param string $query
     * @return string
     */
    static function query_set_table_names($query)
    {
        $query = str_replace("__l_users__", self::LOGIN_USERS, $query);
        $query = str_replace("__l_tasks__", self::LOGIN_TASKS, $query);
        $query = str_replace("__l_teams__", self::LOGIN_TEAMS, $query);
        $query = str_replace("__l_cont_tasks__", self::LOGIN_CONTR_TASKS, $query);
        $query = str_replace("__l_cont_teams__", self::LOGIN_CONTR_TEAM, $query);
        return $query;
    }

    const LOGIN_USERS = "login_users";
    const LOGIN_TASKS = "login_tasks";
    const LOGIN_TEAMS = "login_teams";
    const LOGIN_CONTR_TASKS = "login_contractor_task";
    const LOGIN_CONTR_TEAM = "login_contractor_team";
    const DB_DT_FORMAT = "Y-m-d H:i:s";


}