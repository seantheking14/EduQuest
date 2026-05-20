# EduQuest Gamification Activity System - Setup & Usage Guide

## Overview
This guide explains the complete gamification activity system that allows teachers to create custom learning activities that are automatically integrated with the student dashboard's "My Quests" section.

## System Architecture

### Three-Tier Structure
1. **Teacher Dashboard** - Create and manage activities
2. **Database** - Store activities and track attempts
3. **Student Dashboard** - Display and play activities

## Database Setup

### 1. Run the Schema Migration
Execute the SQL file to create the necessary tables:
```bash
mysql -u root -p eduquest < EDUQUEST/database/schema_teacher_activities.sql
```

Or manually execute the SQL commands in `schema_teacher_activities.sql` through phpMyAdmin/MySQL Workbench.

### Tables Created
- `teacher_activities` - Master table for custom activities
- `teacher_activity_items` - Flexible JSON-based activity rounds/questions
- `teacher_activity_assignments` - Track which students get which activities
- `teacher_activity_attempts` - Student attempt history and results

## Teacher Dashboard - Activity Management

### Access the Activity Builder
1. Log in as a teacher
2. Navigate to Dashboard → Academic → Activities
3. Click "Create Activity" button

### Create a New Activity

#### Step 1: Activity Details
- **Title**: Name of the activity (e.g., "Arrange Numbers Ascending")
- **Category**: Choose from:
  - 🔢 Math
  - 📖 English
  - 🌱 Self Care
- **Icon**: Single emoji representing the activity (default: 🎮)
- **Description**: Brief description of what students will do

#### Step 2: Activity Configuration
- **Activity Type**: Select the interaction pattern:
  - `sort-order`: Arrange items in ascending or descending order
  - `classify`: Categorize items into groups
  - `compare`: Compare two values with operators (<, =, >)
  - `choose`: Multiple choice questions
  - `build-word`: Build words from letters
  - `custom`: Flexible JSON structure

- **Number of Rounds**: How many questions/items (default: 6)
- **Instructions**: Text shown to students during gameplay

#### Step 3: Gamification Settings
- **XP Reward**: Points awarded upon completion (default: 50)
- **Pass Percentage**: Score needed to "pass" the activity (default: 70%)
- **Max Attempts**: Limit attempts (0 = unlimited)
- **Time Limit**: Seconds per round (0 = no limit)

#### Step 4: Add Activity Items
Click "+ Add Item" to create each question/round. Items are stored as JSON:

**Example - Multiple Choice Question:**
```json
{
  "question": "What is 2 + 2?",
  "emoji": "🔢",
  "options": ["3", "4", "5", "6"],
  "answer": 1
}
```

**Example - Sort Order:**
```json
{
  "items": [5, 2, 8, 1, 3],
  "direction": "asc"
}
```

**Example - Classification:**
```json
{
  "item": "🐕",
  "label": "Dog",
  "categories": ["Pet", "Farm", "Zoo"],
  "answer": "Pet"
}
```

### Manage Activities

#### Edit Activity
1. Find activity in list
2. Click "✏️ Edit" button
3. Modify details and items
4. Click "💾 Save Activity"

#### Delete Activity
1. Click "🗑️ Delete" next to activity
2. Confirm deletion
3. Activity removed and all related data cascades delete

#### Duplicate Activity
1. Edit existing activity
2. Click "Duplicate" tab
3. Click "🔄 Duplicate Now"
4. Creates copy with " (Copy)" suffix

#### View Results
1. Edit activity to open details
2. Click "Results" tab
3. See:
   - Total attempts count
   - Number of students who passed
   - Average score percentage
   - Detailed attempt history per student

#### Assign to Students
1. Edit activity to open details
2. Click "Assign" tab
3. Select course or individual students
4. Students assigned will see activity in their "My Quests"

## Student Dashboard Integration

### Where Activities Appear
- Activities show in **"My Quests"** section
- Organized by category (Math, English, Self Care)
- Teacher activities mix seamlessly with predetermined games

### Playing a Teacher Activity
1. Navigate to My Quests
2. Select category tab
3. Click on activity card
4. See preview with:
   - Activity icon and title
   - Number of rounds
   - Activity type
5. Click "▶️ Let's Play!" to start
6. Complete all rounds
7. See results and XP earned

### Attempt Tracking
- Each attempt recorded with:
  - Score and percentage
  - Time spent
  - Answers provided
  - XP earned
  - Pass/fail status
- Students can retry activities (if allowed by teacher)
- Best score and attempt history maintained

## API Reference

### Teacher APIs

#### List Activities
```
GET /api/gamification/activities.php?action=list&search=...&category=math
```

#### Get Single Activity
```
GET /api/gamification/activities.php?action=get&id=1
```

#### Create Activity
```
POST /api/gamification/activities.php
{
  "action": "create",
  "title": "Activity Name",
  "category": "math",
  "activity_type": "sort-order",
  "items": [...]
}
```

#### Update Activity
```
POST /api/gamification/activities.php
{
  "action": "update",
  "id": 1,
  "title": "Updated Name",
  ...
}
```

#### Delete Activity
```
POST /api/gamification/activities.php
{
  "action": "delete",
  "id": 1
}
```

#### Duplicate Activity
```
POST /api/gamification/activities.php
{
  "action": "duplicate",
  "id": 1
}
```

#### Get Results
```
GET /api/gamification/activities.php?action=results&id=1
```

### Student APIs

#### Get Assigned Activities
```
GET /api/gamification/activities-student.php
```
Returns list of teacher-created activities assigned to student, formatted as BANK entries.

#### Track Attempt
```
POST /api/attempt/teacher_activity_attempt.php
{
  "action": "start|complete|abandon",
  "activity_id": 1,
  "attempt_id": 123,
  "score": 80,
  "max_score": 100,
  "time_spent_sec": 120,
  "xp_earned": 50
}
```

## Activity Item JSON Formats

### Sort Order
```json
{
  "items": [5, 2, 8, 1, 3],
  "direction": "asc"
}
```

### Classify
```json
{
  "emoji": "🐕",
  "label": "Dog",
  "answer": "pet"
}
```

### Compare
```json
{
  "left": 5,
  "right": 3,
  "answer": ">"
}
```

### Choose (Multiple Choice)
```json
{
  "question": "What is the color?",
  "emoji": "☀️",
  "options": ["Red", "Blue", "Yellow", "Green"],
  "answer": 2
}
```

### Build Word
```json
{
  "word": "CAT",
  "hint": "A pet animal",
  "emoji": "🐱",
  "extras": ["D", "E"]
}
```

### Custom
```json
{
  "custom_field_1": "value1",
  "custom_field_2": "value2"
}
```

## Data Storage

### Activity Data Structure
- **ID**: Unique identifier in teacher_activities table
- **Teacher ID**: Links to creator teacher
- **Category**: math, english, or selfcare
- **Title**: Activity name
- **Description**: Long-form details
- **Items**: JSON array stored in teacher_activity_items table
- **Attempts**: Tracked in teacher_activity_attempts table

### Student Attempt Data
- Student ID
- Activity ID
- Attempt number
- Score (raw points)
- Max score possible
- Percentage score
- Pass/fail status
- Time spent in seconds
- XP earned
- Student answers (JSON snapshot)
- Started and completed timestamps

## Troubleshooting

### Activities Not Showing for Student
1. Verify assignment created in Assign tab
2. Check activity is active (not toggled off)
3. Confirm student is in assigned course/group
4. Check browser cache (clear localStorage)

### Attempt Not Recording
1. Ensure API endpoint is accessible
2. Check Authorization token is valid
3. Verify attempt started successfully before completing
4. Check network tab in browser dev tools

### Custom Activity Format Not Working
1. Validate JSON syntax
2. Ensure all required fields for activity type are present
3. Check against examples in Activity Item JSON Formats section

## Best Practices

### Effective Activity Design
1. **Clear Instructions**: Write instructions students understand
2. **Appropriate Difficulty**: Match student ADHD profiles
3. **Reasonable Rounds**: 6-10 rounds typically good for focus
4. **XP Balance**: Award meaningful XP relative to difficulty
5. **Category Alignment**: Place activities in correct subject category

### Assignment Strategy
1. **Start Small**: Assign to 1-2 students first to test
2. **Progressive Difficulty**: Create activities in progression
3. **Regular Updates**: Refresh activities for engagement
4. **Track Results**: Review attempt data to improve design

### Student Engagement
1. Activities appear mixed with games (not separate)
2. XP counts toward overall progress
3. Attempts maintain history for motivation
4. Integration with achievement system

## Feature Highlights

✅ **Flexible Design** - Any question type can be added via JSON  
✅ **Teacher Control** - Full CRUD and assignment control  
✅ **Progress Tracking** - Detailed analytics per activity  
✅ **XP Integration** - Custom activities award XP like quizzes  
✅ **Student Transparency** - Clear progress and results shown  
✅ **Predetermined Preservation** - All default games still available  
✅ **Scalability** - Unlimited custom activities per teacher  
✅ **Accessibility** - Emoji-rich, gamification-focused interface  

## Support & Maintenance

### Regular Maintenance
- Monitor activity attempt data for anomalies
- Clean up unused activities periodically
- Review XP distribution across activities
- Gather student feedback on difficulty

### Extending the System
- Add new activity types by updating renderActivityUI() in activity.js
- Extend JSON schema for new question structures
- Create activity templates for common patterns
- Add pre-built activity packs

## Summary

The Gamification Activity System empowers teachers to create personalized, gamified learning experiences while maintaining compatibility with the existing predetermined game library. Students experience a unified "My Quests" interface where teacher-created and default activities appear seamlessly together.

