# Php login server

## Тестирование
Для того, чтобы протестировать, нужно отправить POST запрос на сервер http://bremka.beget.tech/<операция> с нужными параметрами  
Отправлять запросы можно из PhpStorm, для этого можно использовать файл beget_requests.http или любым другим приложением типа Postman.
1. Тестирование логина
```http request
POST http://bremka.beget.tech/login Content-Type: application/json
Content-Type: application/json

{
"login": "Ivan",
"password": "123"
}
```

2. Тестирование регистрации
```http request
POST http://bremka.beget.tech/register
Content-Type: application/json

{
  "login": "Ivan",
  "password": "123",
  "email": "test@yandex.ru",
  "user_type": "customer"
}
```

3. Тестирование создания нового таска
```http request
POST http://bremka.beget.tech/create_task
Content-Type: application/json

{
  "login": "Ivan",
  "task_info": {
    "contractor": "Petr",
    "task_name": "task_1",
    "description": "This is initial task",
    "date": "2021-03-19",
    "status": "in_progress"
  }
}
```

4. Тестирование создания новой команды
```http request
POST http://bremka.beget.tech/create_team
Content-Type: application/json

{
  "name": "useless_team",
  "customer": "Ivan"
}
```

5. Тестирование проверки нахождения пользователя в команде
```http request
POST http://bremka.beget.tech/check_team
Content-Type: application/json

{
  "team_name": "test_team",
  "username": "Ivan"
}
```

6. Тестирование получения задач для данного пользователя
```http request
POST http://bremka.beget.tech/get_tasks
Content-Type: application/json

{
  "username": "Petr"
}
```

7. Тестирование добавления пользователя в команду
```http request
POST http://bremka.beget.tech/add_team
Content-Type: application/json

{
  "username": "Petr",
  "team_name": "useless_team"
}
```

8. Тестирование смены статуса задачи
```http request
POST http://bremka.beget.tech/change_status
Content-Type: application/json

{
  "task_name": "task_1",
  "status": "new_status"
}
```