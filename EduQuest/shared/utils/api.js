// API Helper Functions for EduQuest
// This file provides a foundation for backend API integration

const API_BASE_URL = 'http://localhost:3000/api'; // Change to your API URL

// ==================== API CLIENT ====================
class APIClient {
    constructor(baseURL = API_BASE_URL) {
        this.baseURL = baseURL;
        this.token = null;
    }

    // Set authentication token
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    // Get authentication token
    getToken() {
        if (!this.token) {
            this.token = localStorage.getItem('auth_token');
        }
        return this.token;
    }

    // Clear authentication token
    clearToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    // Generic request method
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const token = this.getToken();

        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...(token && { 'Authorization': `Bearer ${token}` }),
                ...options.headers,
            },
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // GET request
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    // POST request
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    // PUT request
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    // DELETE request
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    // Upload file
    async upload(endpoint, file) {
        const formData = new FormData();
        formData.append('file', file);

        return this.request(endpoint, {
            method: 'POST',
            body: formData,
            headers: {}, // Let browser set Content-Type for FormData
        });
    }
}

// Create singleton instance
const api = new APIClient();

// ==================== AUTHENTICATION API ====================
const AuthAPI = {
    login: async (email, password) => {
        return await api.post('/auth/login', { email, password });
    },

    register: async (userData) => {
        return await api.post('/auth/register', userData);
    },

    logout: async () => {
        const result = await api.post('/auth/logout');
        api.clearToken();
        return result;
    },

    getCurrentUser: async () => {
        return await api.get('/auth/me');
    },

    refreshToken: async () => {
        return await api.post('/auth/refresh');
    },
};

// ==================== USER API ====================
const UserAPI = {
    getProfile: async (userId) => {
        return await api.get(`/users/${userId}`);
    },

    updateProfile: async (userId, data) => {
        return await api.put(`/users/${userId}`, data);
    },

    uploadAvatar: async (userId, file) => {
        return await api.upload(`/users/${userId}/avatar`, file);
    },
};

// ==================== ASSIGNMENT API ====================
const AssignmentAPI = {
    getAll: async (filters = {}) => {
        const query = new URLSearchParams(filters).toString();
        return await api.get(`/assignments?${query}`);
    },

    getById: async (assignmentId) => {
        return await api.get(`/assignments/${assignmentId}`);
    },

    create: async (assignmentData) => {
        return await api.post('/assignments', assignmentData);
    },

    update: async (assignmentId, data) => {
        return await api.put(`/assignments/${assignmentId}`, data);
    },

    delete: async (assignmentId) => {
        return await api.delete(`/assignments/${assignmentId}`);
    },

    submit: async (assignmentId, submission) => {
        return await api.post(`/assignments/${assignmentId}/submit`, submission);
    },

    grade: async (assignmentId, submissionId, grade) => {
        return await api.post(`/assignments/${assignmentId}/submissions/${submissionId}/grade`, grade);
    },
};

// ==================== STUDENT API ====================
const StudentAPI = {
    getAll: async (classId = null) => {
        const endpoint = classId ? `/students?classId=${classId}` : '/students';
        return await api.get(endpoint);
    },

    getById: async (studentId) => {
        return await api.get(`/students/${studentId}`);
    },

    getProgress: async (studentId) => {
        return await api.get(`/students/${studentId}/progress`);
    },

    getAchievements: async (studentId) => {
        return await api.get(`/students/${studentId}/achievements`);
    },

    updateProgress: async (studentId, progressData) => {
        return await api.put(`/students/${studentId}/progress`, progressData);
    },
};

// ==================== ANALYTICS API ====================
const AnalyticsAPI = {
    getClassStats: async (classId) => {
        return await api.get(`/analytics/class/${classId}`);
    },

    getStudentStats: async (studentId) => {
        return await api.get(`/analytics/student/${studentId}`);
    },

    getTeacherStats: async (teacherId) => {
        return await api.get(`/analytics/teacher/${teacherId}`);
    },

    getCompletionRates: async (filters = {}) => {
        const query = new URLSearchParams(filters).toString();
        return await api.get(`/analytics/completion?${query}`);
    },
};

// ==================== NOTIFICATION API ====================
const NotificationAPI = {
    getAll: async () => {
        return await api.get('/notifications');
    },

    markAsRead: async (notificationId) => {
        return await api.put(`/notifications/${notificationId}/read`);
    },

    markAllAsRead: async () => {
        return await api.put('/notifications/read-all');
    },

    delete: async (notificationId) => {
        return await api.delete(`/notifications/${notificationId}`);
    },
};

// ==================== FILE API ====================
const FileAPI = {
    upload: async (file, metadata = {}) => {
        return await api.upload('/files/upload', file);
    },

    download: async (fileId) => {
        return await api.get(`/files/${fileId}/download`);
    },

    delete: async (fileId) => {
        return await api.delete(`/files/${fileId}`);
    },
};

// ==================== EXPORT ====================
window.EduQuestAPI = {
    client: api,
    auth: AuthAPI,
    user: UserAPI,
    assignment: AssignmentAPI,
    student: StudentAPI,
    analytics: AnalyticsAPI,
    notification: NotificationAPI,
    file: FileAPI,
};

// ==================== MOCK DATA (for development) ====================
// Remove this section when connecting to real backend
window.MOCK_MODE = true; // Set to false when using real API

if (window.MOCK_MODE) {
    console.log('📦 Running in MOCK mode - using localStorage instead of API');
    
    // Override API functions with mock implementations
    // This allows the app to work without a backend during development
}

console.log('✅ EduQuest API Client Loaded');
