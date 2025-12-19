import api from '../api';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const authService = {
  login: async (email, password) => {
    try {
      const response = await api.post('/auth/login', {
        email,
        password,
      });
      
      if (response.data.success) {
        const { token, user, company } = response.data.data;
        
        // Armazenar token e dados
        await AsyncStorage.setItem('@Auth:token', token);
        await AsyncStorage.setItem('@Auth:user', JSON.stringify(user));
        await AsyncStorage.setItem('@Company:name', company.name);
        
        return {
          success: true,
          user,
          company,
        };
      }
      
      return { success: false, message: response.data.message };
    } catch (error) {
      console.error('Erro no login:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erro na conexão' 
      };
    }
  },

  logout: async () => {
    try {
      await AsyncStorage.removeItem('@Auth:token');
      await AsyncStorage.removeItem('@Auth:user');
      await AsyncStorage.removeItem('@Auth:lastActivity');
      return { success: true };
    } catch (error) {
      console.error('Erro no logout:', error);
      return { success: false };
    }
  },

  getCurrentUser: async () => {
    try {
      const userStr = await AsyncStorage.getItem('@Auth:user');
      return userStr ? JSON.parse(userStr) : null;
    } catch (error) {
      console.error('Erro ao obter usuário:', error);
      return null;
    }
  },

  getCompanyName: async () => {
    try {
      return await AsyncStorage.getItem('@Company:name');
    } catch (error) {
      console.error('Erro ao obter nome da empresa:', error);
      return 'Empresa';
    }
  },

  updateLastActivity: async () => {
    try {
      await AsyncStorage.setItem('@Auth:lastActivity', Date.now().toString());
    } catch (error) {
      console.error('Erro ao atualizar última atividade:', error);
    }
  },

  checkSessionTimeout: async () => {
    try {
      const lastActivity = await AsyncStorage.getItem('@Auth:lastActivity');
      if (!lastActivity) return true;
      
      const currentTime = Date.now();
      const timeDiff = currentTime - parseInt(lastActivity);
      const sessionTimeout = 20 * 60 * 1000; // 20 minutos em milissegundos
      
      return timeDiff > sessionTimeout;
    } catch (error) {
      console.error('Erro ao verificar timeout:', error);
      return true;
    }
  },
};
