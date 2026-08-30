# Report Student Information Display Guide

## Overview

Reports now properly display student information including student ID and full name.

## Database Structure

### Students Table

- `id` - Primary key (internal identifier)
- `student_id_number` - Unique student ID (visible to users)
- `first_name` - Student's first name
- `middle_name` - Student's middle name (nullable)
- `last_name` - Student's last name

### Reports Table

- `id` - Report ID
- `student_id` - Foreign key referencing `students.id`
- Other report fields...

## API Response Structure

When fetching reports via API, each report includes:

```json
{
    "id": 1,
    "student_id": 5,
    "title": "Lost Laptop",
    "description": "Black Dell laptop...",
    "type": "lost",
    "status": "approved",
    "student": {
        "id": 5,
        "student_id_number": "2024-12345",
        "first_name": "Juan",
        "middle_name": "Carlos",
        "last_name": "Dela Cruz",
        "full_name": "Juan Carlos Dela Cruz",
        "email": "juan@example.com",
        "course": "Computer Science",
        "department": "College of Engineering"
    },
    "student_full_name": "Juan Carlos Dela Cruz",
    "images": [
        {
            "id": 1,
            "file_path": "report-images/xxx.jpg",
            "original_name": "laptop.jpg",
            "is_primary": true
        }
    ],
    "created_at": "2026-08-30T10:00:00Z",
    "updated_at": "2026-08-30T10:00:00Z"
}
```

## Available Endpoints

### Get All Reports

```
GET /api/reports
```

Returns paginated list of reports with student info and images.

### Get My Reports

```
GET /api/reports/mine
```

Returns authenticated user's reports with student info.

### Get Single Report

```
GET /api/reports/{id}
```

Returns a specific report with full student information and images.

### Create Report (with images)

```
POST /api/reports
Content-Type: multipart/form-data

Parameters:
- type: "lost" | "found" (required)
- title: string (required)
- description: string (required)
- category_id: integer (optional)
- location: string (required)
- date_occurred: date (required)
- images: file[] (required, minimum 1)
- reward_amount: decimal (optional)
- reward_type: "reward" | "fine" (optional)
```

## Student Information Display

### In API Responses

Each report includes THREE ways to access student information:

1. **student_id** (integer) - The foreign key reference

    ```
    "student_id": 5
    ```

2. **student** (object) - Full student object with all details

    ```
    "student": {
      "id": 5,
      "student_id_number": "2024-12345",
      "first_name": "Juan",
      "middle_name": "Carlos",
      "last_name": "Dela Cruz",
      "full_name": "Juan Carlos Dela Cruz"
    }
    ```

3. **student_full_name** (string) - Computed attribute with full name
    ```
    "student_full_name": "Juan Carlos Dela Cruz"
    ```

## Model Relationships

### Student Model

- Has attribute: `full_name` (computed from first_name, middle_name, last_name)
- Appends `full_name` to all responses

### Report Model

- Belongs to: `Student`
- Has accessor: `student_full_name` (uses Student's full_name)
- Appends `student_full_name` to all responses

## Implementation Details

All ReportController methods now include the student relationship:

- `index()` - Loads: student, images
- `mine()` - Loads: student, rewards, images
- `show()` - Loads: student, rewards, images
- `store()` - Loads: student, images (after creation)
- `uploadImages()` - Returns just images
- `deleteImage()` - Returns success message

## Example Usage

### Frontend JavaScript (Vue/React)

```javascript
// Fetch reports
const response = await fetch("/api/reports");
const { data } = await response.json();

// Display student info
data.forEach((report) => {
    console.log(`Report by: ${report.student_full_name}`);
    console.log(`Student ID: ${report.student.student_id_number}`);
    console.log(`Course: ${report.student.course}`);
    console.log(`Full Object:`, report.student);
});
```

## Notes

- The `student` relationship is always loaded for easy access to all student details
- The `full_name` attribute automatically handles nullable middle_name
- All student information is protected by authorization checks
- Reports only show to:
    - The report creator (their own reports)
    - Admins (through admin endpoints)
    - Public approved/verified reports (lost/found status)
