## Project Management API

This is a Project Management API built with Laravel. It allows users to register, log in, and manage projects and tasks. The API supports features such as:

- **Project Creation**: Create and manage multiple projects.
- **Task Assignment**: Assign tasks to team members within a project.
- **Status Updates**: Update the status of tasks (Pending, In Progress, Completed, etc.).
- **Team Management**: Manage team members assigned to projects.
- **Recurring Task Feature**: Support for creating recurring tasks with customizable recurrence patterns (daily, weekly, monthly, yearly).
- **Authentication**: Secured with authentication via Laravel Passport.
- **Notifications**: Team members receive notifications upon task assignment or task updates.

### Recurring Task Feature
The recurring task feature allows tasks to be automatically created based on a user-defined schedule. It includes customizable recurrence types (daily, weekly, monthly, or yearly) and intervals (e.g., every 2 weeks). Recurring tasks are generated automatically based on the recurrence interval, helping ensure ongoing work is scheduled without manual input.

### Notifications Feature
Notifications are sent to relevant users (task creator and assigned member) upon task assignment or updates. This helps team members stay informed of task changes in real time. Notifications can be fetched via API, and users can mark them as read, ensuring a smooth workflow.



---

## Prerequisites

Before setting up the project, make sure you have the following installed:

- **PHP**
- **Composer** (PHP dependency manager)
- **Laravel**
- **PostgreSQL** (Database)
- **Postman** (For API testing)

## Installation Instructions

### Step 1: Install Dependencies

Run the following command to install all the required dependencies:

```bash
composer install
```

### Step 2: Set Up Environment Variables

1. Create a copy of `.env.example` and rename it to `.env`:

```bash
cp .env.example .env
```

2. Modify the necessary fields in the `.env` file to set up your database connection and application URL:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=postgres_db
DB_USERNAME=postgres_username
DB_PASSWORD=postgres_password
```

### Step 3: Run Database Migrations

Run the following command to migrate the database:

```bash
php artisan migrate
```

### Step 4: Install Laravel Passport

Run the following command to install Passport, which is used for API authentication:

```bash
php artisan passport:install
```

- When asked `Would you like to run all pending database migrations? (yes/no)`, answer `yes`.
- When asked to generate `personal access` and `password grant` clients, answer `yes`.

### Step 5: Start the Server

Start the Laravel development server:

```bash
php artisan serve
```

You can now access the API at `http://localhost:8000`.

---

## API Endpoints

Here are the main endpoints available in the Project Management API:

### 1. User Registration

**Endpoint**: `POST /api/register`  
**Description**: Registers a new user using phone number and password.

**Request**:
```json
{
  "name": "John Doe",
  "phone_number": "1234567890",
  "password": "password123"
}
```

**Response**:
```json
{
  "token": "your_auth_token",
  "user": {
    "id": 1,
    "name": "John Doe",
    "phone_number": "1234567890"
  }
}
```

---

### 2. User Login

**Endpoint**: `POST /api/login`  
**Description**: Logs in a user and returns an access token.

**Request**:
```json
{
  "phone_number": "1234567890",
  "password": "password123"
}
```

**Response**:
```json
{
  "token": "your_auth_token",
  "user": {
    "id": 1,
    "name": "John Doe",
    "phone_number": "1234567890"
  }
}
```

---

### 3. Create a Project

**Endpoint**: `POST /api/projects`  
**Description**: Creates a new project (requires authentication).

**Request**:
```json
{
  "title": "New Project",
  "description": "This is a new project.",
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "status": "Active"
}
```

**Response**:
```json
{
  "id": 1,
  "title": "New Project",
  "description": "This is a new project.",
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "status": "Active"
}
```

---

### 4. Update a Project

**Endpoint**: `PUT /api/projects/{id}`  
**Description**: Updates an existing project (requires authentication).

**Request**:
```json
{
  "title": "Updated Project",
  "description": "This is an updated project.",
  "start_date": "2024-02-01",
  "end_date": "2024-11-30",
  "status": "Deferred"
}
```

**Response**:
```json
{
  "id": 1,
  "title": "Updated Project",
  "description": "This is an updated project.",
  "start_date": "2024-02-01",
  "end_date": "2024-11-30",
  "status": "Deferred"
}
```

---

### 5. Delete a Project

**Endpoint**: `DELETE /api/projects/{id}`  
**Description**: Soft deletes a project and its tasks (requires authentication).

**Response**:
```json
{
  "message": "Project deleted successfully"
}
```

---

### 6. List Projects (with Filtering and Sorting)

**Endpoint**: `GET /api/projects`  
**Description**: Fetches projects the user owns or is part of, with optional filtering and sorting (requires authentication).

**Query Parameters**:
- `title`: Filter by project title.
- `status`: Filter by project status (`Active`, `Deferred`, `Completed`).
- `start_date`: Filter by projects starting on or after a given date.
- `end_date`: Filter by projects ending on or before a given date.
- `sort_by`: Sort by a specific column (e.g., `title`, `created_at`).
- `sort_order`: Sort order (`asc` or `desc`).

**Example Request**:
```
GET /api/projects?status=Active&sort_by=title&sort_order=asc
```

**Response**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "New Project",
      "description": "This is a new project.",
      "start_date": "2024-01-01",
      "end_date": "2024-12-31",
      "status": "Active"
    }
  ]
}
```

---

### 7. Create a Task (with Recurrence Support)

**Endpoint**: `POST /api/projects/{projectId}/tasks`  
**Description**: Creates a task within a project with support for recurring tasks (requires authentication).

**Request**:
```json
{
  "title": "New Task",
  "description": "This is a new task.",
  "start_date": "2024-01-01",
  "end_date": "2024-01-15",
  "priority": "High",
  "status": "Pending",
  "assigned_to": 2,
  "is_recurring": true,
  "recurrence_type": "daily", 
  "recurrence_interval": 3,
  "recurrence_end_date": "2024-01-30"
}
```

- **is_recurring**: Boolean, required. Indicates if the task is recurring.
- **recurrence_type**: Enum, required if `is_recurring` is `true`. Accepted values: `daily`, `weekly`, `monthly`, `yearly`.
- **recurrence_interval**: Integer, optional. Specifies how often the recurrence happens (e.g., every 3 days).
- **recurrence_end_date**: Date, optional. Specifies the end of the recurrence period.

**Response**:
```json
{
  "id": 1,
  "title": "New Task",
  "description": "This is a new task.",
  "start_date": "2024-01-01",
  "end_date": "2024-01-15",
  "priority": "High",
  "status": "Pending",
  "assigned_to": 2,
  "is_recurring": true,
  "recurrence_type": "daily",
  "recurrence_interval": 3,
  "recurrence_end_date": "2024-01-30"
}
```

---

### 8. Update a Task (with Recurrence Support)

**Endpoint**: `PUT /api/tasks/{taskId}`  
**Description**: Updates an existing task with support for updating recurring tasks (requires authentication).

**Request**:
```json
{
  "title": "Updated Task",
  "description": "This is an updated task.",
  "start_date": "2024-01-05",
  "end_date": "2024-01-20",
  "priority": "Medium",
  "status": "In Progress",
  "is_recurring": true,
  "recurrence_type": "weekly",
  "recurrence_interval": 2,
  "recurrence_end_date": "2024-03-01"
}
```

- **start_date** and **end_date**: You can update the dates. If recurring, the system checks the recurrence settings.
- **is_recurring**: Boolean, optional. If set to `false`, all recurrence fields will be nullified.
- **recurrence_type**, **recurrence_interval**, **recurrence_end_date**: Optional fields for updating the recurrence settings.

**Response**:
```json
{
  "id": 1,
  "title": "Updated Task",
  "description": "This is an updated task.",
  "start_date": "2024-01-05",
  "end_date": "2024-01-20",
  "priority": "Medium",
  "status": "In Progress",
  "is_recurring": true,
  "recurrence_type": "weekly",
  "recurrence_interval": 2,
  "recurrence_end_date": "2024-03-01"
}
```

---

### 9. Update Task Status

**Endpoint**: `PATCH /api/tasks/{taskId}/status`  
**Description**: Updates the status of a task (only assigned user can use this endpoint).

**Request**:
```json
{
  "status": "In Progress"
}
```

**Response**:
```json
{
  "id": 1,
  "status": "In Progress"
}
```

---

### Recurring Task Command

**Command**: `php artisan tasks:handle-recurring`  
**Description**: This command processes all tasks that have a recurrence set (`is_recurring` is true) and generates the next recurrence task based on the task’s `recurrence_type`, `recurrence_interval`, and other recurrence settings.

---

### Additional Setup

- **Recurring Tasks**: The command to handle recurring tasks needs to be scheduled as a cron job in your `app/Console/Kernel.php` file to run periodically and check for due tasks.
  
  Example:
  ```php
  protected function schedule(Schedule $schedule)
  {
      $schedule->command('tasks:handle-recurring')->daily();
  }
  ```

This will ensure that recurring tasks are generated and processed on the appropriate schedule (daily, weekly, etc.).

---

### 10. Add a Team Member to a Project

**Endpoint**: `POST /api/projects/{projectId}/team`  
**Description**: Adds a new team member to the project (only the project owner can do this).

**Request**:
```json
{
  "user_id": 3
}
```

**Response**:
```json
{
  "message": "Team member added successfully.",
  "team_member": {
    "project_id": 1,
    "user_id": 3
  }
}
```

---
### 11. Fetch Unread Notifications

**Endpoint**: `GET /api/notifications/unread`  
**Description**: Fetches all unread notifications for the authenticated user.

**Response**:
```json
{
  "data": [
    {
      "id": "1a2b3c4d5e6f",
      "type": "App\\Notifications\\TaskUpdatedNotification",
      "notifiable_type": "App\\Models\\User",
      "notifiable_id": 1,
      "data": {
        "message": "The task has been updated.",
        "task": {
          "id": 8,
          "title": "New Task",
          "description": "Task description"
        }
      },
      "read_at": null,
      "created_at": "2024-09-22T18:00:00.000000Z",
      "updated_at": "2024-09-22T18:00:00.000000Z"
    }
  ]
}
```

---

### 12. Mark a Notification as Read

**Endpoint**: `PATCH /api/notifications/{notificationId}/read`  
**Description**: Marks a specific notification as read for the authenticated user.

**Response**:
```json
{
  "message": "Notification marked as read."
}
```

---

### 13. Fetch All Notifications (Read and Unread)

**Endpoint**: `GET /api/notifications`  
**Description**: Fetches all notifications (read and unread) for the authenticated user.

**Response**:
```json
{
  "data": [
    {
      "id": "1a2b3c4d5e6f",
      "type": "App\\Notifications\\TaskUpdatedNotification",
      "notifiable_type": "App\\Models\\User",
      "notifiable_id": 1,
      "data": {
        "message": "The task has been updated.",
        "task": {
          "id": 8,
          "title": "New Task",
          "description": "Task description"
        }
      },
      "read_at": null,
      "created_at": "2024-09-22T18:00:00.000000Z",
      "updated_at": "2024-09-22T18:00:00.000000Z"
    },
    {
      "id": "2f4b6d8c9e7f",
      "type": "App\\Notifications\\TaskUpdatedNotification",
      "notifiable_type": "App\\Models\\User",
      "notifiable_id": 1,
      "data": {
        "message": "The task has been completed.",
        "task": {
          "id": 9,
          "title": "Another Task",
          "description": "Task description"
        }
      },
      "read_at": "2024-09-22T19:00:00.000000Z",
      "created_at": "2024-09-22T17:00:00.000000Z",
      "updated_at": "2024-09-22T19:00:00.000000Z"
    }
  ]
}
```

---

## Testing the API

You can use **Postman**

 or any other API client to test the endpoints. Ensure that you include the `Authorization: Bearer {token}` header for the routes that require authentication.