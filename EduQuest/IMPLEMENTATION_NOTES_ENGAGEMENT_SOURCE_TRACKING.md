# Quiz and Activity Engagement Source Tracking Implementation

## Overview
This implementation adds source tracking to behavioral engagement logs to distinguish between quiz and activity engagement. Previously, all engagement logs were aggregated together, making it impossible to see which engagement metrics came from quizzes vs activities.

## Changes Made

### 1. Database Schema Update
**File:** `EDUQUEST/database/schema_super_admin.sql`
- Added `source ENUM('quiz','activity','other') DEFAULT 'other' NOT NULL` column to `behavioral_logs` table
- Added index on source column for filtering performance
- Migration script provided: `migrate_add_source_to_behavioral_logs.sql`

### 2. API Backend Updates

#### Log Behavior Function
**File:** `EDUQUEST/api/log_behavior.php`
- Updated `log_behavior()` function signature to accept optional `$source` parameter (default 'other')
- Added source validation (must be 'quiz', 'activity', or 'other')
- Updated SQL INSERT to include source column

#### Engagement Logging Calls
Updated all three engagement logging endpoints to pass source:
- **`EDUQUEST/api/learning/quiz.php`** - passes `'quiz'` as source
- **`EDUQUEST/api/attempt/game_complete.php`** - passes `'activity'` as source
- **`EDUQUEST/api/attempt/teacher_activity_attempt.php`** - passes `'activity'` as source

#### Engagement Summary APIs
**Files:** 
- `EDUQUEST/api/teacher_logs_fetch.php`
- `EDUQUEST/api/super_admin_logs_fetch.php`

Changes:
- Modified engagement summary queries to group by source
- Each engagement indicator now returns nested objects: `summary[indicator_key][source] = {...}`
- Added source column to log query SELECT clauses
- Aggregation queries filter by source when computing averages, min, max
- Response includes source in formatted logs

### 3. Frontend Updates

#### Teacher Dashboard Behavioral Logs
**File:** `EDUQUEST/teacher-dashboard/behavioral-logs.php`
- Added "Source" column to logs table (header and rendering)
- Updated `renderEngagementSummary()` to display quiz and activity engagement separately
- Each engagement indicator now shows stats for both sources with visual distinction
- Source column displays as badge with color: Quiz (blue #3b82f6), Activity (green #10b981), Other (gray #6b7280)

#### Super Admin Dashboard
**File:** `super_admin/dashboard.php`
- Added "Source" column to behavioral logs table
- Updated `renderEngagementSummary()` to display engagement breakdown by source
- Enhanced visualization with colored source badges
- Engagement summary now shows separate stats for quiz vs activity engagement

## Engagement Structure (API Response)

### Before:
```json
{
  "engagement_summary": {
    "task_completion_rate": {
      "avg": 75.5,
      "max_value": 100,
      "max_student": "John Doe",
      "min_value": 45,
      "min_student": "Jane Smith"
    }
  }
}
```

### After:
```json
{
  "engagement_summary": {
    "task_completion_rate": {
      "quiz": {
        "avg": 78.2,
        "max_value": 100,
        "max_student": "John Doe",
        "min_value": 50,
        "min_student": "Jane Smith"
      },
      "activity": {
        "avg": 72.8,
        "max_value": 95,
        "max_student": "Alice Brown",
        "min_value": 40,
        "min_student": "Bob Wilson"
      }
    }
  }
}
```

## Engagement Indicators Tracked by Source

### Quiz Source:
- task_completion_rate (percentage score)
- time_on_task (time spent)
- response_rate (answers provided / total questions)
- exp_accumulation_rate (XP earned)
- module_attempt_frequency (attempt number)

### Activity Source:
- task_completion_rate (percentage score)
- time_on_task (time spent)
- exp_accumulation_rate (XP earned)

## Migration Steps for Existing Installations

1. Run the migration SQL script to add source column to existing tables:
   ```sql
   -- From EDUQUEST/database/migrate_add_source_to_behavioral_logs.sql
   ALTER TABLE behavioral_logs 
   ADD COLUMN source ENUM('quiz','activity','other') DEFAULT 'other' NOT NULL 
   AFTER indicator_value;
   ```

2. All NEW engagement logs will include source information automatically
3. Existing logs will default to 'other' source
4. Frontend displays will show engagement by source with 'other' source included

## Frontend Display Features

### Teacher Dashboard
- Engagement summary grid shows separate cards for Quiz and Activity for each indicator
- Behavioral logs table shows source column with color-coded badges
- Filter logs by engagement type and view which source contributed each metric

### Super Admin Dashboard  
- System-wide engagement summary broken down by source
- Can see which type (Quiz vs Activity) has higher engagement metrics
- Logs table shows source for each entry for transparency

## Backward Compatibility

- All changes are backward compatible
- Existing code that calls `log_behavior()` without source parameter will use default 'other'
- Frontend gracefully handles both old (unsourced) and new (sourced) engagement data
- Database migration is additive (new column) with safe defaults

## Files Modified
1. `EDUQUEST/database/schema_super_admin.sql` - Schema update
2. `EDUQUEST/api/log_behavior.php` - Updated function
3. `EDUQUEST/api/learning/quiz.php` - Quiz logging
4. `EDUQUEST/api/attempt/game_complete.php` - Activity logging
5. `EDUQUEST/api/attempt/teacher_activity_attempt.php` - Activity logging
6. `EDUQUEST/api/teacher_logs_fetch.php` - Teacher API
7. `EDUQUEST/api/super_admin_logs_fetch.php` - Super admin API
8. `EDUQUEST/teacher-dashboard/behavioral-logs.php` - Teacher UI
9. `super_admin/dashboard.php` - Super admin UI

## Files Created
1. `EDUQUEST/database/migrate_add_source_to_behavioral_logs.sql` - Migration script

## Testing Recommendations
1. Take a quiz and verify engagement logs appear with source='quiz'
2. Complete an activity and verify engagement logs appear with source='activity'
3. View teacher dashboard - should see quiz and activity engagement separately
4. View super admin dashboard - should see system-wide breakdown by source
5. Filter logs by engagement type and verify source column displays correctly
