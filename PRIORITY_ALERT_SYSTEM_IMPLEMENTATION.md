# Priority Alert System Implementation

## Overview
A complete priority project alert system that automatically detects new priority projects with images and displays them in a full-screen modal with slideshow and sound alerts.

## Features Implemented

### 🚨 **Real-time Priority Detection**
- **Automatic monitoring**: Checks for new priority projects every 10 seconds
- **Smart detection**: Only shows projects with status = 'PRIORITY' that haven't been alerted yet
- **Database tracking**: Uses `priority_alerts` table to prevent duplicate alerts

### 📸 **Image Slideshow System**
- **Dynamic image loading**: Loads project images from `project_images` table
- **Auto slideshow**: Changes images every 5 seconds
- **Image counter**: Shows current image position (e.g., "2 / 5")
- **Countdown timer**: Shows "Next in 5s" countdown
- **Fallback handling**: Shows "No images available" when no images exist

### 🔊 **Sound Alert System**
- **Primary audio**: Loads `priority-alert.mp3` from `static/sounds/`
- **Fallback beep**: Uses Web Audio API to create beep sound if file not found
- **Error handling**: Gracefully handles audio playback failures

### 🎨 **Full-Screen Modal Design**
- **Attention-grabbing**: Full-screen overlay with dark background
- **TDT theme**: Orange border and accents matching dashboard design
- **Responsive layout**: Adapts to mobile/tablet screens
- **Animations**: Fade-in and slide-up effects with pulsing title

### 📊 **Complete Project Details**
- **Project information**: Name, contractor, value, contact details
- **Location data**: Region, province, address
- **Material breakdown**: Sheet pile amounts, DRBs values
- **Publication date**: When the project was published

## API Endpoint

### **GET /api/v1/priority-alerts**
Returns new priority projects that need to be alerted.

**Response Structure:**
```json
{
  "alert": {
    "project": {
      "id": 123,
      "name": "Priority Construction Project",
      "contractor_name": "ABC Construction Corp",
      "contact_person": "Juan Dela Cruz",
      "contact_number": "+63 917 123 4567",
      "project_value": 15000000,
      "status": "PRIORITY",
      "region": "NCR",
      "city_province": "Manila",
      "address": "Quezon City",
      "publication_date": "2024-02-15",
      "sheet_pile_amount": 5000000,
      "drbs_value": 3000000,
      "created_at": "2024-02-15 10:30:00",
      "updated_at": "2024-02-15 10:30:00"
    },
    "images": [
      {
        "id": 1,
        "file_path": "uploads/projects/123/image1.jpg",
        "created_at": "2024-02-15 10:35:00"
      },
      {
        "id": 2,
        "file_path": "uploads/projects/123/image2.jpg",
        "created_at": "2024-02-15 10:36:00"
      }
    ],
    "timestamp": "2024-02-15 14:30:00"
  }
}
```

**No Alert Response:**
```json
{
  "alert": null
}
```

## Database Tables Used

### **priority_alerts**
```sql
CREATE TABLE `priority_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `alerted_at` datetime NOT NULL DEFAULT current_timestamp()
)
```

### **project_images**
```sql
CREATE TABLE `project_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
)
```

## File Structure

```
DATAS/
├── api/
│   ├── priority-alerts.php          # Priority alerts API endpoint
│   └── router.php                   # Updated with new route
├── static/
│   └── sounds/
│       ├── priority-alert.mp3       # Sound file (user must add)
│       └── priority-alert-placeholder.txt
└── pages/
    └── reports.php                  # Updated dashboard with alert system
```

## How It Works

### 1. **Priority Project Detection**
```javascript
// Checks every 10 seconds for new priority projects
AppState.intervals.priorityCheck = setInterval(() => {
    this.checkForAlerts();
}, 10000);
```

### 2. **Alert Display Flow**
1. **API Check**: Dashboard calls `/api/v1/priority-alerts`
2. **Sound Alert**: Plays `priority-alert.mp3` or fallback beep
3. **Modal Display**: Shows full-screen modal with project details
4. **Image Slideshow**: If images exist, starts 5-second slideshow
5. **Database Marking**: API marks project as alerted in `priority_alerts` table

### 3. **Image Slideshow Logic**
```javascript
// Changes image every 5 seconds
setInterval(() => {
    this.imageSlideshow.timeRemaining--;
    if (this.imageSlideshow.timeRemaining <= 0) {
        this.nextImage();
        this.imageSlideshow.timeRemaining = 5;
    }
}, 1000);
```

## Setup Instructions

### 1. **Add Sound File**
- Place `priority-alert.mp3` in `static/sounds/` directory
- Recommended: Clear, attention-grabbing alert sound (2-5 seconds)

### 2. **Test the System**
To test the priority alert system:

1. **Create a priority project:**
   ```sql
   INSERT INTO projects (
       contractor_name, project_name, project_value, status, region
   ) VALUES (
       'Test Contractor Corp', 
       'URGENT: Bridge Construction Project', 
       25000000, 
       'PRIORITY', 
       'NCR'
   );
   ```

2. **Add test images (optional):**
   ```sql
   INSERT INTO project_images (project_id, file_path) VALUES 
   (LAST_INSERT_ID(), 'uploads/test/image1.jpg'),
   (LAST_INSERT_ID(), 'uploads/test/image2.jpg');
   ```

3. **Wait up to 10 seconds** - The alert should appear automatically

### 3. **Verify Setup**
- Check browser console for logs: `🚨 Priority Alert: {...}`
- Ensure no JavaScript errors in console
- Test with and without images
- Test sound playback (may require user interaction first)

## Security Features

- ✅ **Authentication required**: Only authenticated users can access alerts
- ✅ **SQL injection protection**: All queries use prepared statements  
- ✅ **XSS protection**: All user data is properly escaped
- ✅ **File path validation**: Image paths are validated before display
- ✅ **Rate limiting**: Alert checks limited to every 10 seconds

## Browser Compatibility

- ✅ **Modern browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- ✅ **Mobile responsive**: Works on tablets and phones
- ✅ **Audio fallback**: Web Audio API beep if MP3 fails
- ⚠️ **Audio autoplay**: May require user interaction in some browsers

## Troubleshooting

### **No sound playing:**
1. Check if `priority-alert.mp3` exists in `static/sounds/`
2. Verify browser allows audio autoplay
3. Check browser console for audio errors
4. Test with user interaction (click something first)

### **Images not loading:**
1. Verify image file paths in database
2. Check file permissions on upload directories
3. Ensure images are accessible via web browser

### **Alerts not appearing:**
1. Check browser console for JavaScript errors
2. Verify API endpoint is accessible: `/api/v1/priority-alerts`
3. Check database has projects with status = 'PRIORITY'
4. Verify user is authenticated and has proper permissions

### **Modal not responsive:**
1. Check CSS media queries are loading
2. Test on different screen sizes
3. Verify no CSS conflicts with existing styles

The priority alert system is now fully integrated and ready to use!