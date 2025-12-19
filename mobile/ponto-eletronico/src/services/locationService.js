import * as Location from 'expo-location';
import { Platform } from 'react-native';

export const locationService = {
  getCurrentLocation: async () => {
    try {
      // Verificar permissões
      let { status } = await Location.requestForegroundPermissionsAsync();
      
      if (status !== 'granted') {
        throw new Error('Permissão de localização negada');
      }

      // Obter localização
      const location = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.BestForNavigation,
      });

      return {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
        accuracy: location.coords.accuracy,
        timestamp: location.timestamp,
      };
    } catch (error) {
      console.error('Erro ao obter localização:', error);
      throw error;
    }
  },

  calculateDistance: (lat1, lon1, lat2, lon2) => {
    const R = 6371e3; // Raio da Terra em metros
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c; // Distância em metros
  },

  isWithinWorkRadius: async (workLatitude, workLongitude, maxDistance = 50) => {
    try {
      const currentLocation = await locationService.getCurrentLocation();
      const distance = locationService.calculateDistance(
        currentLocation.latitude,
        currentLocation.longitude,
        workLatitude,
        workLongitude
      );

      return {
        isWithinRadius: distance <= maxDistance,
        distance: Math.round(distance),
        currentLocation,
      };
    } catch (error) {
      console.error('Erro ao verificar raio:', error);
      throw error;
    }
  },
};
