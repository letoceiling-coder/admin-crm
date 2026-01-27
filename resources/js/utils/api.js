import axios from 'axios';
import { handleApiError, getErrorMessage, getValidationErrors } from './errors.js';

// Настройка axios для работы с Sanctum
axios.defaults.withCredentials = true; // Важно для Sanctum cookies
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

/**
 * ⚠️ ВАЖНО: Правила использования API роутов в Vue компонентах
 * 
 * API_BASE уже содержит '/api/v1', поэтому в компонентах НЕ нужно добавлять '/v1/' или '/api/v1/'
 * 
 * ✅ ПРАВИЛЬНО:
 *   apiGet('/folders')           → /api/v1/folders
 *   apiGet('/media')              → /api/v1/media
 *   apiGet('/folders/1')          → /api/v1/folders/1
 *   apiPost('/folders', data)     → /api/v1/folders
 * 
 * ❌ НЕПРАВИЛЬНО:
 *   apiGet('/v1/folders')       → /api/v1/v1/folders (ОШИБКА!)
 *   apiGet('/api/v1/folders')    → /api/v1/api/v1/folders (ОШИБКА!)
 * 
 * Структура роутов в routes/api.php:
 *   Route::prefix('v1')->group(function () {
 *       Route::apiResource('folders', FolderController::class);
 *       // Полный путь: /api/v1/folders
 *   });
 * 
 * Всегда начинайте путь с '/' и БЕЗ '/v1/'!
 */
const API_BASE = '/api/v1';

// Получить заголовки авторизации
const getAuthHeaders = () => {
    const token = localStorage.getItem('token');
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return headers;
};

// GET запрос
export const apiGet = async (url, params = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    const headers = getAuthHeaders();
    
    try {
        const response = await axios.get(fullUrl, {
            params,
            headers,
            withCredentials: true,
        });
        return {
            ok: true,
            status: response.status,
            json: async () => response.data,
        };
    } catch (error) {
        // Преобразуем ошибку axios в формат fetch Response
        if (error.response) {
            return {
                ok: false,
                status: error.response.status,
                json: async () => error.response.data,
            };
        }
        throw error;
    }
};

// POST запрос
export const apiPost = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'POST',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// PUT запрос
export const apiPut = async (url, data = {}) => {
    const fullUrl = `${API_BASE}${url}`;
    
    // Если data - FormData, не устанавливаем Content-Type
    const headers = data instanceof FormData 
        ? { ...getAuthHeaders(), 'Content-Type': undefined }
        : getAuthHeaders();
    
    // Удаляем Content-Type если это FormData (браузер установит сам)
    if (data instanceof FormData) {
        delete headers['Content-Type'];
    }
    
    return fetch(fullUrl, {
        method: 'PUT',
        headers,
        body: data instanceof FormData ? data : JSON.stringify(data),
    });
};

// DELETE запрос
export const apiDelete = async (url) => {
    const fullUrl = `${API_BASE}${url}`;
    
    return fetch(fullUrl, {
        method: 'DELETE',
        headers: getAuthHeaders(),
    });
};
