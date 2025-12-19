import AsyncStorage from '@react-native-async-storage/async-storage';

export const session = {
  updateActivity: async () => {
    try {
      await AsyncStorage.setItem('@Auth:lastActivity', Date.now().toString());
    } catch (error) {
      console.error('Erro ao atualizar atividade:', error);
    }
  },

  clearSession: async () => {
    try {
      await AsyncStorage.multiRemove([
        '@Auth:token',
        '@Auth:user',
        '@Auth:lastActivity',
        '@Punch:lastPunch',
      ]);
    } catch (error) {
      console.error('Erro ao limpar sessão:', error);
    }
  },
};
