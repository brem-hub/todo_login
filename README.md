# Php login server

## Testing
API доступен по адресу url/[action].

## Contents
1. [Login](#login)
3. [Register](#register)
4. [Create new task](#create-new-task)
5. [Assign task](#assign-task)
6. [Create new team](#create-new-team)
7. [Check if user is in the team](#check-if-user-is-in-the-team)
8. [Get task](#get-task)
9. [Get all tasks for contractor](#get-all-tasks-for-contractor)
10. [Assign team for contractor](#assign-team-for-contractor)
11. [Change status of the task](#change-status-of-the-task)
12. [Delete the contractor from the team](#delete-the-contractor-from-the-team)
13. [Get contractors of the customer](#get-contractors-of-the-customer)

### Setup
All requests return __json__ answer

### Login
#### Request
```http request
POST http://<url>/login
Content-Type: application/json

{
"userLogin": "login",
"userPassword": "password"
}
``` 
#### Return
```json
{
  "status": 1,
  "data": "type of the user(customer/contractor)"
}
or
{
  "status": 0,
  "comment": "error message"
}
``` 

### Register
#### Request
```http request
POST http://<url>/register
Content-Type: application/json

{
  "userLogin": "lgoin",
  "userPassword": "password",
  "userEmail": "email@example.com",
  "userType": "customer/contractor"
}
```
#### Return
```json
{
  "status": 1
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Create new task
#### Request
Date format: `Y-m-d H:i:s`
```http request
POST http://<url>/create-task
Content-Type: application/json

{
  "customerLogin": "login",
  "taskInfo": {
    "title": "task title",
    "finishDate": "2021-03-19",
    [optional] "description": "task description",
    [optional] "beginDate": "2021-03-10",
    [optional] "status": "in progress"
  }
}
```
#### Return
```json
{
  "status": 1
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Assign task
#### Request
```http request
POST http://<url>/assign-task
Content-Type: application/json

{
  "taskTitle": "task title",
  "userLogin": "contractor to assign task to"
}
```
#### Return
```json
{
  "status": 1
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Create new team
#### Request
```http request
POST http://<url>/create-team
Content-Type: application/json

{
  "customerLogin": "login",
  "teamInfo":{
      "title": "title of the team",
      [optional] "description": "team description"
  }
}
```
#### Return
```json
{
  "status": 1
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Check if user is in the team
#### Request
```http request
POST http://<url>/check-team
Content-Type: application/json

{
  "userLogin": "login",
  "teamTitle": "team title"
}
```
#### Return
```json
{
  "status": 1,
  "data": 1 / 0
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Get task
#### Request
```http request
POST http://<url>/get-task
Content-Type: application/json

{
  "taskTitle": "task title"
}
```
#### Return
```json
{
  "status": 1,
  "data": array
}
or
{
  "status": 0,
  "comment": "message"
}
```

### Get all tasks for contractor
#### Request
```http request
POST http://<url>/get-tasks
Content-Type: application/json

{
  "userLogin": "contractor login"
}
```
#### Return
```json
{
  "status": 1,
  "data": array
}
```

### Assign team for contractor
#### Request
```http request
POST http://<url>/assign-team
Content-Type: application/json

{
  "userLogin": "contractor login",
  "teamTitle": "team title"
}
```
#### Return
```json
{
  "status": 1
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Change status of the task
#### Request
```http request
POST http://<url>/change-status
Content-Type: application/json

{
  "taskTitle": "task title",
  "newStatus": "new status"
}
```
#### Return
```json
{
  "status": 1,
  "data": 1 or 0
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Delete the contractor from the team
#### Request
```http request
POST http://<url>/delete-cont-team
Content-Type: application/json

{
  "userLogin": "contractor login",
  "teamTitle": "team title"
}
```
#### Return
```json
{
  "status": 1,
  "data": 1 or 0
}
or
{
  "status": 0,
  "comment": "error message"
}
```

### Get contractors of the customer
#### Request
```http request
POST http://<url>/contractors-by-customer
Content-Type: application/json

{
  "userLogin": "contractor login",
  "teamTitle": "team title"
}
```
#### Return
```json
{
  "status": 1,
  "data": array
}
or
{
  "status": 0,
  "comment": "error message"
}
```

