// Shared Utility Functions for EduQuest

// ==================== LOCAL STORAGE HELPERS ====================
const Storage = {
    get: (key, defaultValue = null) => {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error(`Error reading ${key} from localStorage:`, error);
            return defaultValue;
        }
    },
    
    set: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error(`Error saving ${key} to localStorage:`, error);
            return false;
        }
    },
    
    remove: (key) => {
        localStorage.removeItem(key);
    },
    
    clear: () => {
        localStorage.clear();
    }
};

// ==================== USER AUTHENTICATION ====================
const Auth = {
    isAuthenticated: () => {
        const token = localStorage.getItem('eq_token');
        const user  = Storage.get('eduquest_user');
        return !!(token && user && user.email);
    },
    
    getUser: () => {
        return Storage.get('eduquest_user');
    },
    
    getToken: () => {
        return localStorage.getItem('eq_token');
    },
    
    getUserRole: () => {
        const user = Auth.getUser();
        return user ? user.role : null;
    },
    
    requireAuth: (requiredRole = null) => {
        if (!Auth.isAuthenticated()) {
            window.location.href = '../../auth/login/login.html';
            return false;
        }
        
        if (requiredRole && Auth.getUserRole() !== requiredRole) {
            window.location.href = '../../auth/login/login.html?role=' + requiredRole;
            return false;
        }
        
        return true;
    },
    
    logout: async () => {
        const token = localStorage.getItem('eq_token');
        if (token) {
            await fetch('../../EDUQUEST/api/auth/logout.php', {
                method: 'POST',
                headers: { Authorization: 'Bearer ' + token },
                credentials: 'include',
            }).catch(() => {});
        }
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user',
         'student_progress', 'eduquest_remember_me'].forEach(k =>
            localStorage.removeItem(k)
        );
        window.location.href = '../../auth/login/login.html';
    }
};

// ==================== DATE & TIME FORMATTING ====================
const DateUtils = {
    formatDate: (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatTime: (date) => {
        return new Date(date).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    formatDateTime: (date) => {
        return `${DateUtils.formatDate(date)} at ${DateUtils.formatTime(date)}`;
    },
    
    getRelativeTime: (date) => {
        const now = new Date();
        const then = new Date(date);
        const seconds = Math.floor((now - then) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)} days ago`;
        return DateUtils.formatDate(date);
    },
    
    getDaysUntil: (date) => {
        const now = new Date();
        const target = new Date(date);
        const days = Math.ceil((target - now) / (1000 * 60 * 60 * 24));
        return days;
    }
};

// ==================== STRING FORMATTING ====================
const StringUtils = {
    capitalize: (str) => {
        return str.charAt(0).toUpperCase() + str.slice(1);
    },
    
    truncate: (str, length = 50) => {
        return str.length > length ? str.substring(0, length) + '...' : str;
    },
    
    slugify: (str) => {
        return str.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    
    extractInitials: (name) => {
        return name.split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
};

// ==================== NUMBER FORMATTING ====================
const NumberUtils = {
    formatNumber: (num) => {
        return num.toLocaleString();
    },
    
    formatXP: (xp) => {
        if (xp >= 1000000) return (xp / 1000000).toFixed(1) + 'M';
        if (xp >= 1000) return (xp / 1000).toFixed(1) + 'K';
        return xp.toString();
    },
    
    calculatePercentage: (current, total) => {
        return Math.round((current / total) * 100);
    },
    
    clamp: (value, min, max) => {
        return Math.min(Math.max(value, min), max);
    }
};

// ==================== VALIDATION ====================
const Validator = {
    isEmail: (email) => {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    isNotEmpty: (str) => {
        return str && str.trim().length > 0;
    },
    
    minLength: (str, length) => {
        return str && str.length >= length;
    },
    
    maxLength: (str, length) => {
        return str && str.length <= length;
    },
    
    isNumber: (value) => {
        return !isNaN(parseFloat(value)) && isFinite(value);
    }
};

// ==================== DOM HELPERS ====================
const DOM = {
    createElement: (tag, className, innerHTML) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (innerHTML) element.innerHTML = innerHTML;
        return element;
    },
    
    show: (element) => {
        if (element) element.style.display = 'block';
    },
    
    hide: (element) => {
        if (element) element.style.display = 'none';
    },
    
    toggle: (element) => {
        if (element) {
            element.style.display = element.style.display === 'none' ? 'block' : 'none';
        }
    },
    
    addClass: (element, className) => {
        if (element) element.classList.add(className);
    },
    
    removeClass: (element, className) => {
        if (element) element.classList.remove(className);
    },
    
    toggleClass: (element, className) => {
        if (element) element.classList.toggle(className);
    }
};

// ==================== ANIMATIONS ====================
const Animate = {
    fadeIn: (element, duration = 300) => {
        if (!element) return;
        element.style.opacity = '0';
        element.style.display = 'block';
        
        let start = null;
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = (timestamp - start) / duration;
            
            element.style.opacity = Math.min(progress, 1);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    },
    
    fadeOut: (element, duration = 300) => {
        if (!element) return;
        
        let start = null;
        const animate = (timestamp) => {
            if (!start) start = timestamp;
            const progress = (timestamp - start) / duration;
            
            element.style.opacity = 1 - Math.min(progress, 1);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.display = 'none';
            }
        };
        
        requestAnimationFrame(animate);
    },
    
    slideIn: (element, direction = 'down', duration = 300) => {
        if (!element) return;
        element.style.display = 'block';
        element.style.animation = `slide${direction} ${duration}ms ease`;
    }
};

// ==================== DEBOUNCE & THROTTLE ====================
const debounce = (func, wait = 300) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

const throttle = (func, limit = 300) => {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

// ==================== ARRAY HELPERS ====================
const ArrayUtils = {
    shuffle: (array) => {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    },
    
    unique: (array) => {
        return [...new Set(array)];
    },
    
    groupBy: (array, key) => {
        return array.reduce((result, item) => {
            const group = item[key];
            result[group] = result[group] || [];
            result[group].push(item);
            return result;
        }, {});
    }
};

// ==================== EXPORT ALL UTILITIES ====================
window.EduQuestUtils = {
    Storage,
    Auth,
    DateUtils,
    StringUtils,
    NumberUtils,
    Validator,
    DOM,
    Animate,
    ArrayUtils,
    debounce,
    throttle
};

// Log initialization
console.log('✅ EduQuest Utilities Loaded');
