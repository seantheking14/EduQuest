# Activity System - API Documentation

## Base URLs
- **Teacher API**: `/EDUQUEST/api/gamification/activities.php`
- **Student API**: `/EDUQUEST/api/gamification/activities-student.php`
- **Attempt Tracking**: `/EDUQUEST/api/attempt/teacher_activity_attempt.php`

## Authentication
All requests require Bearer token in Authorization header:
```
Authorization: Bearer {token}
```

## Response Format
All endpoints return JSON:
```json
{
  "success": true/false,
  "message": "Description",
  "data": { ... },
  "statusCode": 200
}
```

---

## Teacher Activity Management API

### 1. List Activities
**Endpoint**: `GET /activities.php?action=list`

**Parameters**:
- `search` (optional): Search term for title
- `category` (optional): Filter by category (math, english, selfcare)

**Response**:
```json
{
  "success": true,
  "message": "Activities fetched.",
  "data": {
    "activities": [
      {
        "id": 1,
        "teacher_id": 5,
        "title": "Arrange Numbers",
        "category": "math",
        "icon": "🔢",
        "activity_type": "sort-order",
        "xp_reward": 50,
        "is_active": 1,
        "item_count": 6,
        "attempt_count": 12,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ]
  }
}
```

**HTTP Status**: 200

---

### 2. Get Single Activity
**Endpoint**: `GET /activities.php?action=get&id=1`

**Parameters**:
- `id` (required): Activity ID

**Response**:
```json
{
  "success": true,
  "message": "Activity fetched.",
  "data": {
    "activity": {
      "id": 1,
      "teacher_id": 5,
      "title": "Arrange Numbers",
      "description": "Sort numbers in order",
      "category": "math",
      "icon": "🔢",
      "activity_type": "sort-order",
      "instructions": "Tap from smallest to largest",
      "rounds": 6,
      "xp_reward": 50,
      "pass_percentage": 70,
      "max_attempts": 0,
      "time_limit_sec": 0,
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    },
    "items": [
      {
        "id": 1,
        "activity_id": 1,
        "item_order": 1,
        "item_data": {
          "items": [5, 2, 8, 1, 3],
          "direction": "asc"
        }
      }
    ]
  }
}
```

**HTTP Status**: 200

**Errors**:
- 400: Missing or invalid ID
- 404: Activity not found

---

### 3. Create Activity
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "create",
  "title": "Arrange Numbers",
  "description": "Sort numbers in order",
  "category": "math",
  "icon": "🔢",
  "activity_type": "sort-order",
  "instructions": "Tap from smallest to largest",
  "rounds": 6,
  "xp_reward": 50,
  "pass_percentage": 70,
  "max_attempts": 0,
  "time_limit_sec": 0,
  "items": [
    {
      "items": [5, 2, 8, 1, 3],
      "direction": "asc"
    },
    {
      "items": [12, 7, 15, 3, 9],
      "direction": "asc"
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity created successfully.",
  "data": {
    "activity_id": 1
  },
  "statusCode": 201
}
```

**HTTP Status**: 201 (Created)

**Errors**:
- 400: Missing required fields (title, category)
- 500: Database error

---

### 4. Update Activity
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "update",
  "id": 1,
  "title": "Updated Title",
  "category": "math",
  ...
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity updated successfully.",
  "data": {
    "activity_id": 1
  }
}
```

**HTTP Status**: 200

**Errors**:
- 400: Missing activity ID
- 404: Activity not found

---

### 5. Delete Activity
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "delete",
  "id": 1
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity deleted successfully."
}
```

**HTTP Status**: 200

**Errors**:
- 400: Missing activity ID
- 404: Activity not found

---

### 6. Duplicate Activity
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "duplicate",
  "id": 1
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity duplicated successfully.",
  "data": {
    "new_activity_id": 2
  },
  "statusCode": 201
}
```

**HTTP Status**: 201

**Errors**:
- 400: Missing activity ID
- 404: Activity not found

---

### 7. Toggle Activity Status
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "toggle",
  "id": 1,
  "is_active": 0
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity toggled successfully.",
  "data": {
    "is_active": false
  }
}
```

**HTTP Status**: 200

---

### 8. Get Activity Results
**Endpoint**: `GET /activities.php?action=results&id=1`

**Parameters**:
- `id` (required): Activity ID

**Response**:
```json
{
  "success": true,
  "message": "Results fetched.",
  "data": {
    "total_attempts": 12,
    "passed_count": 8,
    "average_score": 82.5,
    "attempts": [
      {
        "id": 1,
        "student_id": 10,
        "first_name": "John",
        "last_name": "Doe",
        "attempt_number": 1,
        "score": 85,
        "percentage": 85.0,
        "passed": 1,
        "time_spent_sec": 120,
        "xp_earned": 50,
        "completed_at": "2024-01-15 11:30:00"
      }
    ]
  }
}
```

**HTTP Status**: 200

---

### 9. Assign Activity
**Endpoint**: `POST /activities.php`

**Body**:
```json
{
  "action": "assign",
  "activity_id": 1,
  "course_id": 5,
  "student_ids": [10, 11, 12]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Activity assigned successfully.",
  "data": {
    "assigned_count": 3
  }
}
```

**HTTP Status**: 200

---

## Student API

### Get Assigned Activities
**Endpoint**: `GET /activities-student.php`

**No parameters required**

**Response**:
```json
{
  "success": true,
  "message": "Activities loaded.",
  "data": {
    "teacher_activities": [
      {
        "id": 1,
        "title": "Arrange Numbers",
        "description": "Sort numbers",
        "icon": "🔢",
        "category": "math",
        "activity_type": "sort-order",
        "instructions": "Tap smallest to largest",
        "rounds": 6,
        "xp_reward": 50,
        "pass_percentage": 70,
        "max_attempts": 0,
        "time_limit_sec": 0,
        "is_active": 1,
        "attempt_count": 3,
        "passed_count": 2,
        "best_score": 90.5,
        "is_custom": true,
        "rounds": [
          {
            "items": [5, 2, 8, 1, 3],
            "direction": "asc"
          }
        ]
      }
    ]
  }
}
```

**HTTP Status**: 200

**Errors**:
- 404: Student profile not found

---

## Attempt Tracking API

### Start Activity Attempt
**Endpoint**: `POST /teacher_activity_attempt.php`

**Body**:
```json
{
  "action": "start",
  "activity_id": 1
}
```

**Response**:
```json
{
  "success": true,
  "message": "Attempt started.",
  "data": {
    "attempt_id": 42
  },
  "statusCode": 201
}
```

**HTTP Status**: 201

---

### Complete Activity Attempt
**Endpoint**: `POST /teacher_activity_attempt.php`

**Body**:
```json
{
  "action": "complete",
  "attempt_id": 42,
  "score": 85,
  "max_score": 100,
  "time_spent_sec": 120,
  "xp_earned": 50
}
```

**Response**:
```json
{
  "success": true,
  "message": "Attempt completed.",
  "data": {
    "attempt_id": 42,
    "percentage": 85.0,
    "passed": true,
    "xp_earned": 50
  }
}
```

**HTTP Status**: 200

---

### Abandon Activity Attempt
**Endpoint**: `POST /teacher_activity_attempt.php`

**Body**:
```json
{
  "action": "abandon",
  "attempt_id": 42
}
```

**Response**:
```json
{
  "success": true,
  "message": "Attempt abandoned."
}
```

**HTTP Status**: 200

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 204 | No Content - OPTIONS request |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - User doesn't have permission |
| 404 | Not Found - Resource doesn't exist |
| 500 | Internal Server Error - Database or server error |

---

## Error Handling

All error responses follow this format:
```json
{
  "success": false,
  "message": "Description of what went wrong",
  "statusCode": 400
}
```

Example:
```json
{
  "success": false,
  "message": "Activity not found or access denied.",
  "statusCode": 404
}
```

---

## Rate Limiting
Currently no rate limiting implemented. Consider adding for production use.

---

## CORS Policy
- **Allow Origin**: * (all origins)
- **Allow Methods**: GET, POST, OPTIONS
- **Allow Headers**: Content-Type, Authorization

---

## Request Examples

### Using Fetch API
```javascript
// List activities
const response = await fetch('/EDUQUEST/api/gamification/activities.php?action=list', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('eq_token')
  }
});
const data = await response.json();
```

### Using cURL
```bash
curl -X GET \
  'http://localhost/EDUQUEST/api/gamification/activities.php?action=list' \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

---

## Database Relationships

```
teacher_activities
├── teacher_id → teachers.id
├── teacher_activity_items
│   └── activity_id → teacher_activities.id
├── teacher_activity_assignments
│   ├── activity_id → teacher_activities.id
│   ├── teacher_id → teachers.id
│   ├── course_id → courses.id
│   └── student_id → students.id
└── teacher_activity_attempts
    ├── activity_id → teacher_activities.id
    └── student_id → students.id
```

---

## Changelog

### Version 1.0 (Initial Release)
- Basic CRUD operations for activities
- Student activity fetching
- Attempt tracking
- Results analytics
- Activity assignment
- Duplicate functionality
