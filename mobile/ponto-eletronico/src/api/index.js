import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_BASE_URL = process.env.API_BASE_URL || 'https://sua-api.com/v1';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: parseInt(process.env.API_TIMEOUT) || 10000,
});

// Interceptor para adicionar token
api.interceptors.request.use(async (config) => {
  const token = await AsyncStorage.getItem('@Auth:token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para tratamento de erros
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado ou inválido
      AsyncStorage.removeItem('@Auth:token');
      AsyncStorage.removeItem('@Auth:user');
    }
    return Promise.reject(error);
  }
);

export default api;
