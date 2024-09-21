# Project Management API

This is a Project Management API built with Laravel. It allows users to register, log in, and manage projects and tasks. The API supports features such as project creation, task assignment, status updates, and team management. This API is secured with authentication via Laravel Passport.

## Features

- User Registration and Authentication (via phone number and password)
- Project Management (create, update, delete, and list projects)
- Task Management (create, update, delete tasks, and update task status)
- Team Management (add team members to a project)
- Filtering and sorting projects by various parameters

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

### 7. Create a Task

**Endpoint**: `POST /api/projects/{projectId}/tasks`  
**Description**: Creates a task within a project (requires authentication).

**Request**:
```json
{
  "title": "New Task",
  "description": "This is a new task.",
  "start_date": "2024-01-01",
  "end_date": "2024-01-15",
  "priority": "High",
  "status": "Pending",
  "assigned_to": 2
}
```

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
  "assigned_to": 2
}
```

---

### 8. Update a Task

**Endpoint**: `PUT /api/tasks/{taskId}`  
**Description**: Updates an existing task (requires authentication).

**Request**:
```json
{
  "title": "Updated Task",
  "description": "This is an updated task.",
  "start_date": "2024-01-05",
  "end_date": "2024-01-20",
  "priority": "Medium",
  "status": "In Progress"
}
```

**Response**:
```json
{
  "id": 1,
  "title": "Updated Task",
  "description": "This is an updated task.",
  "start_date": "2024-01-05",
  "end_date": "2024-01-20",
  "priority": "Medium",
  "status": "In Progress"
}
```

---

### 9. Update Task Status

**Endpoint**: `PATCH /api/tasks/{taskId}/status`  
**Description**: Updates the status of a task (only the assigned member can do this).

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

## Testing the API

You can use **Postman**

 or any other API client to test the endpoints. Ensure that you include the `Authorization: Bearer {token}` header for the routes that require authentication.