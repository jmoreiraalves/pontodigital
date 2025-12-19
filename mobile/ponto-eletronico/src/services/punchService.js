import api from '../api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import moment from 'moment';

export const punchService = {
  registerPunch: async (data) => {
    try {
      const response = await api.post('/punch/register', data);
      return response.data;
    } catch (error) {
      console.error('Erro ao registrar ponto:', error);
      throw error;
    }
  },

  getPunchHistory: async (days = 7) => {
    try {
      const response = await api.get(`/punch/history?days=${days}`);
      return response.data;
    } catch (error) {
      console.error('Erro ao obter histórico:', error);
      throw error;
    }
  },

  checkDuplicatePunch: async () => {
    try {
      const lastPunchStr = await AsyncStorage.getItem('@Punch:lastPunch');
      if (!lastPunchStr) return false;
      
      const lastPunch = JSON.parse(lastPunchStr);
      const now = moment();
      const lastPunchTime = moment(lastPunch.timestamp);
      const minutesDiff = now.diff(lastPunchTime, 'minutes');
      
      return minutesDiff < 30; // 30 minutos mínimo entre batidas
    } catch (error) {
      console.error('Erro ao verificar duplicidade:', error);
      return false;
    }
  },

  saveLastPunch: async (punchData) => {
    try {
      await AsyncStorage.setItem('@Punch:lastPunch', JSON.stringify({
        ...punchData,
        timestamp: Date.now(),
      }));
    } catch (error) {
      console.error('Erro ao salvar último ponto:', error);
    }
  },

  getJustificationOptions: () => {
    return [
      { id: 1, label: 'Trânsito intenso' },
      { id: 2, label: 'Problemas de saúde' },
      { id: 3, label: 'Problemas familiares' },
      { id: 4, label: 'Problemas no transporte' },
      { id: 5, label: 'Reunião externa' },
      { id: 6, label: 'Home office' },
      { id: 7, label: 'Outro' },
    ];
  },
};
