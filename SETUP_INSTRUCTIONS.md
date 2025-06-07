# CampusConnect Backend Integration Setup Guide

## Files Created/Updated

### New PHP Files:
1. **`pages/announcement.php`** - Student announcements page with backend integration
2. **`pages/chat.php`** - Student chat support with department representative
3. **`pages/manage-account.php`** - Student account management (profile & password)
4. **`database/helpers.php`** - Database helper functions for common operations
5. **`database/sample_data.sql`** - Sample data for testing

### Updated Files:
1. **`auth_check.php`** - Enhanced authentication helper functions

## Setup Instructions

### 1. Database Setup
```bash
# Import the schema and sample data
mysql -u root -p campusconnectdb < database/schema.sql
mysql -u root -p campusconnectdb < database/sample_data.sql
```

### 2. File Structure
Make sure your file structure matches:
```
Campus-Connect/
├── assets/css/
├── database/
│   ├── db.php
│   ├── schema.sql
│   ├── helpers.php
│   └── sample_data.sql
├── pages/
│   ├── announcement.php
│   ├── chat.php
│   ├── manage-account.php
│   ├── student_dashboard.php
│   └── [other files...]
├── auth_check.php
└── [other files...]
```

### 3. Test Accounts
Use these accounts to test the system:

**Students:**
- Email: `student1@campus.edu` | Password: `password` | Dept: Computer Science
- Email: `student2@campus.edu` | Password: `password` | Dept: Information Technology
- Email: `student3@campus.edu` | Password: `password` | Dept: Engineering
- Email: `student4@campus.edu` | Password: `password` | Dept: Business Administration

**Officers (Department Representatives):**
- Email: `kyle.anderson@campus.edu` | Password: `password` | Dept: Computer Science
- Email: `maria.santos@campus.edu` | Password: `password` | Dept: Information Technology
- Email: `john.rivera@campus.edu` | Password: `password` | Dept: Engineering
- Email: `ana.reyes@campus.edu` | Password: `password` | Dept: Business Administration

## Features Implemented

### 1. Announcements System
- **Display announcements** for student's department + general announcements
- **Real-time updates** (auto-refresh every 30 seconds)
- **Proper formatting** with date, time, author, and content
- **Responsive design** matching existing frontend

### 2. Chat Support System
- **Department representative detection** - automatically finds the rep for student's department
- **Real-time messaging** between student and their department representative
- **Message history** with proper sender identification and timestamps
- **Auto-refresh** every 10 seconds (only when user isn't typing)
- **Error handling** when no representative is found

### 3. Account Management
- **Profile updates** (name and email)
- **Password changes** with current password verification
- **Email uniqueness** validation
- **Success/error messaging**
- **Display current account information**

## Key Features

### Security
- ✅ **Authentication required** for all pages
- ✅ **Role-based access** (student-only pages)
- ✅ **SQL injection prevention** (prepared statements)
- ✅ **XSS protection** (htmlspecialchars)
- ✅ **Password hashing** (PHP password_hash)

### User Experience
- ✅ **Auto-refresh** for real-time updates
- ✅ **Responsive design** consistent with existing frontend
- ✅ **Error handling** with user-friendly messages
- ✅ **Form validation** on both client and server side

### Database Integration
- ✅ **Efficient queries** with proper joins
- ✅ **Helper functions** for common operations
- ✅ **Proper data relationships** following the ERD

## Testing Steps

1. **Login as a student** using the test accounts
2. **Check announcements** - should see department-specific and general announcements
3. **Test chat system** - send messages to department representative
4. **Update profile** - change name/email and verify updates
5. **Change password** - test password change functionality

## Next Steps for Full Integration

1. **Officer/Admin panels** - Create corresponding pages for officers to:
   - Post announcements
   - Reply to student chats
   - Manage their profiles

2. **Real-time chat** - Implement WebSocket or Server-Sent Events for instant messaging

3. **Notifications** - Add notification system for new messages/announcements

4. **File uploads** - Allow attachment of files to announcements

5. **Advanced features** - Add read receipts, message search, announcement categories, etc.

## Notes

- All pages include proper session management
- Database connections are handled efficiently
- Error messages are user-friendly and secure
- The system is ready for production with proper styling already in place