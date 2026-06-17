/* ============================================================
   PHILIPPINE DATETIME FORMATTER
   ============================================================
   Formats dates/times to Philippine timezone (UTC+8 / Asia/Manila)
   ============================================================ */

const PhilippineDateTime = {
    // Philippine timezone identifier
    timezone: 'Asia/Manila',
    
    /**
     * Format date to Philippine locale
     * @param {Date|string} date - Date object or ISO string
     * @param {Object} options - Intl.DateTimeFormat options
     * @returns {string} Formatted date string
     */
    format(date, options = {}) {
        if (!date) return '—';
        
        const dateObj = date instanceof Date ? date : new Date(date);
        
        // Check if date is valid
        if (isNaN(dateObj.getTime())) return '—';
        
        const defaultOptions = {
            timeZone: this.timezone,
            ...options
        };
        
        try {
            return new Intl.DateTimeFormat('en-PH', defaultOptions).format(dateObj);
        } catch (e) {
            console.error('Date formatting error:', e);
            return dateObj.toLocaleString('en-PH');
        }
    },
    
    /**
     * Format as full date and time (e.g., "6/17/2026, 3:12:33 PM")
     */
    formatDateTime(date) {
        return this.format(date, {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    },
    
    /**
     * Format as short date and time (e.g., "6/17/2026, 3:12 PM")
     */
    formatDateTimeShort(date) {
        return this.format(date, {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    },
    
    /**
     * Format as date only (e.g., "June 17, 2026")
     */
    formatDate(date) {
        return this.format(date, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },
    
    /**
     * Format as short date (e.g., "Jun 17, 2026")
     */
    formatDateShort(date) {
        return this.format(date, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    /**
     * Format as numeric date (e.g., "6/17/2026")
     */
    formatDateNumeric(date) {
        return this.format(date, {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric'
        });
    },
    
    /**
     * Format as time only (e.g., "3:12:33 PM")
     */
    formatTime(date) {
        return this.format(date, {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    },
    
    /**
     * Format as time without seconds (e.g., "3:12 PM")
     */
    formatTimeShort(date) {
        return this.format(date, {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    },
    
    /**
     * Format as relative time (e.g., "2 hours ago", "in 3 days")
     */
    formatRelative(date) {
        if (!date) return '—';
        
        const dateObj = date instanceof Date ? date : new Date(date);
        if (isNaN(dateObj.getTime())) return '—';
        
        const now = new Date();
        const diffMs = dateObj - now;
        const diffSec = Math.floor(Math.abs(diffMs) / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        const diffMonth = Math.floor(diffDay / 30);
        const diffYear = Math.floor(diffDay / 365);
        
        const isPast = diffMs < 0;
        const suffix = isPast ? 'ago' : 'from now';
        
        if (diffSec < 60) return `${diffSec} second${diffSec !== 1 ? 's' : ''} ${suffix}`;
        if (diffMin < 60) return `${diffMin} minute${diffMin !== 1 ? 's' : ''} ${suffix}`;
        if (diffHour < 24) return `${diffHour} hour${diffHour !== 1 ? 's' : ''} ${suffix}`;
        if (diffDay < 30) return `${diffDay} day${diffDay !== 1 ? 's' : ''} ${suffix}`;
        if (diffMonth < 12) return `${diffMonth} month${diffMonth !== 1 ? 's' : ''} ${suffix}`;
        return `${diffYear} year${diffYear !== 1 ? 's' : ''} ${suffix}`;
    },
    
    /**
     * Format for Activity Logs (e.g., "6/17/2026, 3:12:33 AM")
     */
    formatActivityLog(date) {
        return this.formatDateTime(date);
    },
    
    /**
     * Get current Philippine time
     */
    now() {
        return new Date();
    },
    
    /**
     * Get current Philippine time as formatted string
     */
    nowFormatted(options = {}) {
        return this.format(this.now(), options);
    },
    
    /**
     * Get current time for display (HH:MM:SS AM/PM)
     */
    currentTime() {
        return this.formatTime(this.now());
    },
    
    /**
     * Get current time for display (HH:MM AM/PM)
     */
    currentTimeShort() {
        return this.formatTimeShort(this.now());
    },
    
    /**
     * Parse ISO string to Date object in Philippine timezone
     */
    parse(dateString) {
        if (!dateString) return null;
        const date = new Date(dateString);
        return isNaN(date.getTime()) ? null : date;
    },
    
    /**
     * Check if date is today (Philippine timezone)
     */
    isToday(date) {
        if (!date) return false;
        const dateObj = date instanceof Date ? date : new Date(date);
        const today = new Date();
        
        return dateObj.getFullYear() === today.getFullYear() &&
               dateObj.getMonth() === today.getMonth() &&
               dateObj.getDate() === today.getDate();
    },
    
    /**
     * Format for table displays with Philippine timezone
     */
    formatTableDate(date) {
        if (!date) return '—';
        
        if (this.isToday(date)) {
            return 'Today, ' + this.formatTimeShort(date);
        }
        
        return this.formatDateTimeShort(date);
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PhilippineDateTime;
}

// Make available globally
window.PhilippineDateTime = PhilippineDateTime;
