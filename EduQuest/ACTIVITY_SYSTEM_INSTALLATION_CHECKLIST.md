# EduQuest Activity System - Installation Checklist

## Pre-Installation
- [ ] PHP 7.4+ with PDO MySQL support
- [ ] MySQL 5.7+ database
- [ ] EduQuest already installed and running
- [ ] Teachers and students registered in system
- [ ] SSH/Terminal access or phpMyAdmin access

## Step 1: Database Setup
- [ ] Navigate to: `EDUQUEST/database/schema_teacher_activities.sql`
- [ ] Open the file and review the schema
- [ ] Execute via phpMyAdmin OR MySQL CLI:
  ```bash
  mysql -u root -p eduquest < schema_teacher_activities.sql
  ```
- [ ] Verify tables created:
  - `teacher_activities`
  - `teacher_activity_items`
  - `teacher_activity_assignments`
  - `teacher_activity_attempts`

## Step 2: Backend API Setup
All files already created, verify they exist:
- [ ] `/EDUQUEST/api/gamification/activities.php`
- [ ] `/EDUQUEST/api/gamification/activities-student.php`
- [ ] `/EDUQUEST/api/attempt/teacher_activity_attempt.php`

Test API access:
- [ ] Load in browser: `/EDUQUEST/api/gamification/activities.php?action=list`
- [ ] Should see JSON response (check browser console)

## Step 3: Teacher Dashboard Setup
Files already created, verify:
- [ ] `/EDUQUEST/teacher-dashboard/activity-builder.php` exists
- [ ] `/EDUQUEST/assets/js/activity-builder.js` exists
- [ ] Dashboard sidebar updated with "🎮 Activities" link

Verify in browser:
- [ ] Log in as teacher
- [ ] Navigate to Dashboard
- [ ] Check sidebar has "Activities" link in Academic section
- [ ] Click "Activities" - should load activity-builder.php

## Step 4: Student Dashboard Integration
Verify modifications to existing file:
- [ ] `/student-dashboard/games/activity.js` has `fetchTeacherActivities()` function
- [ ] `fetchTeacherActivities()` is called in DOMContentLoaded
- [ ] Activity attempt tracking updated for teacher activities

Test in browser:
- [ ] Log in as student
- [ ] Go to "My Quests"
- [ ] Browser console should show fetch request to `/api/gamification/activities-student.php`

## Step 5: Functional Testing

### Teacher Testing
1. [ ] Log in as teacher
2. [ ] Go to Activities
3. [ ] Click "Create Activity"
4. [ ] Fill in form:
   - Title: "Test Activity"
   - Category: Math
   - Type: sort-order
   - Add sample items
5. [ ] Click Save
6. [ ] Should see activity in list
7. [ ] Test Edit functionality
8. [ ] Test Results tab
9. [ ] Test Assign tab
10. [ ] Test Duplicate button
11. [ ] Test Delete button

### Student Testing
1. [ ] Log in as student
2. [ ] Go to My Quests
3. [ ] Should see both predetermined games AND teacher-created activity
4. [ ] Click teacher activity to play
5. [ ] Complete activity
6. [ ] Verify XP awarded
7. [ ] Check browser console for no errors

## Step 6: Browser Compatibility
- [ ] Test in Chrome
- [ ] Test in Firefox
- [ ] Test in Safari
- [ ] Test on mobile device

## Step 7: Data Verification

Check database directly:
```sql
-- Verify tables created
SHOW TABLES LIKE 'teacher_activity%';

-- Verify data after creating activity
SELECT * FROM teacher_activities;
SELECT * FROM teacher_activity_items;
SELECT * FROM teacher_activity_attempts;
```

## Step 8: Performance Testing
- [ ] Create 5+ activities
- [ ] Load activities list (should be fast)
- [ ] Assign activities to students
- [ ] Have students complete activities
- [ ] Monitor XP awards
- [ ] Check attempt records created

## Step 9: Security Testing
- [ ] Test with invalid tokens (should be rejected)
- [ ] Test cross-user access (student shouldn't see other's activities)
- [ ] Test teacher can only manage own activities
- [ ] Test SQL injection attempts (prepared statements should protect)

## Step 10: Documentation
- [ ] Read `GAMIFICATION_ACTIVITY_SYSTEM_README.md`
- [ ] Share with teachers
- [ ] Create teacher training materials if needed
- [ ] Document any customizations made

## Troubleshooting During Setup

### 404 Errors on API Calls
- [ ] Verify API file paths are correct
- [ ] Check file permissions (should be readable)
- [ ] Verify `.php` files aren't being blocked

### JSON Response Errors
- [ ] Check browser console for specific error
- [ ] Verify database connection in `config/database.php`
- [ ] Verify tables exist with correct schema
- [ ] Check MySQL error logs

### Activities Not Showing
- [ ] Clear browser cache (Ctrl+Shift+Delete)
- [ ] Clear localStorage: `localStorage.clear()`
- [ ] Reload page
- [ ] Check browser console for fetch errors

### XP Not Awarding
- [ ] Verify gamification helper loaded
- [ ] Check browser console for API errors
- [ ] Verify attempt tracking started correctly
- [ ] Check student profile XP value in database

## Rollback Instructions (if needed)

To remove the activity system:
```sql
DROP TABLE IF EXISTS teacher_activity_attempts;
DROP TABLE IF EXISTS teacher_activity_assignments;
DROP TABLE IF EXISTS teacher_activity_items;
DROP TABLE IF EXISTS teacher_activities;
```

Then:
- [ ] Delete API files
- [ ] Delete activity-builder.php
- [ ] Delete activity-builder.js
- [ ] Revert changes to dashboard.php
- [ ] Revert changes to activity.js

## Completion Checklist
- [ ] All database tables verified
- [ ] All files exist and are readable
- [ ] Teacher can create/edit/delete activities
- [ ] Student can see and play activities
- [ ] XP awards correctly
- [ ] Attempt tracking works
- [ ] No errors in browser console
- [ ] No errors in server logs
- [ ] Documentation reviewed
- [ ] Team trained on system

---

**Installation Date**: ___________  
**Completed By**: ___________  
**Verified By**: ___________  

If all boxes are checked, your Activity System is ready for production!
