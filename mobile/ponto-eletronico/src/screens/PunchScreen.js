import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
  Modal,
  TextInput,
  Image,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { Camera } from 'expo-camera';
import { punchService } from '../services/punchService';
import { authService } from '../services/authService';
import { locationService } from '../services/locationService';
import Footer from '../components/Footer';
import moment from 'moment';

const PunchScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(false);
  const [photo, setPhoto] = useState(null);
  const [showJustificationModal, setShowJustificationModal] = useState(false);
  const [selectedJustification, setSelectedJustification] = useState(null);
  const [customJustification, setCustomJustification] = useState('');
  const [user, setUser] = useState(null);
  const [lastPunch, setLastPunch] = useState(null);

  useEffect(() => {
    loadUserData();
    checkLastPunch();
  }, []);

  const loadUserData = async () => {
    const userData = await authService.getCurrentUser();
    setUser(userData);
  };

  const checkLastPunch = async () => {
    const isDuplicate = await punchService.checkDuplicatePunch();
    if (isDuplicate) {
      Alert.alert(
        'Atenção',
        'Aguarde 30 minutos entre as batidas de ponto'
      );
    }
  };

  const takePhoto = async () => {
    try {
      const { status } = await Camera.requestCameraPermissionsAsync();
      
      if (status !== 'granted') {
        Alert.alert('Permissão necessária', 'Precisamos de acesso à câmera para registrar o ponto');
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        allowsEditing: true,
        aspect: [4, 3],
        quality: 0.8,
        base64: true,
      });

      if (!result.canceled) {
        setPhoto(result.assets[0].uri);
      }
    } catch (error) {
      console.error('Erro ao tirar foto:', error);
      Alert.alert('Erro', 'Não foi possível tirar a foto');
    }
  };

  const selectJustification = (justification) => {
    if (justification.id === 7) {
      setSelectedJustification(justification);
    } else {
      setSelectedJustification(justification);
      setCustomJustification('');
    }
  };

  const handlePunch = async () => {
    if (!photo) {
      Alert.alert('Atenção', 'Por favor, tire uma foto para registrar o ponto');
      return;
    }

    // Verificar se já bateu ponto recentemente
    const isDuplicate = await punchService.checkDuplicatePunch();
    if (isDuplicate) {
      Alert.alert(
        'Atenção',
        'Aguarde 30 minutos entre as batidas de ponto'
      );
      return;
    }

    // Verificar localização para usuários presenciais
    if (user?.modality === 'presencial') {
      try {
        const workLatitude = parseFloat(process.env.WORK_LATITUDE) || -23.5505;
        const workLongitude = parseFloat(process.env.WORK_LONGITUDE) || -46.6333;
        
        const locationCheck = await locationService.isWithinWorkRadius(
          workLatitude,
          workLongitude,
          50
        );

        if (!locationCheck.isWithinRadius) {
          Alert.alert(
            'Localização Incorreta',
            `Você está a ${locationCheck.distance}m do local de trabalho. Necessário estar dentro de 50m.`
          );
          return;
        }
      } catch (error) {
        Alert.alert(
          'Erro de Localização',
          'Não foi possível verificar sua localização'
        );
        return;
      }
    }

    setLoading(true);

    try {
      // Preparar dados do ponto
      const punchData = {
        userId: user.id,
        photo: photo,
        timestamp: moment().toISOString(),
        latitude: null,
        longitude: null,
        justification: selectedJustification 
          ? selectedJustification.id === 7 
            ? customJustification 
            : selectedJustification.label
          : null,
      };

      // Obter localização
      try {
        const location = await locationService.getCurrentLocation();
        punchData.latitude = location.latitude;
        punchData.longitude = location.longitude;
      } catch (locationError) {
        console.warn('Não foi possível obter localização:', locationError);
      }

      // Registrar ponto via API
      const result = await punchService.registerPunch(punchData);

      if (result.success) {
        // Salvar último ponto
        await punchService.saveLastPunch(punchData);
        
        Alert.alert(
          'Sucesso',
          'Ponto registrado com sucesso!',
          [
            {
              text: 'OK',
              onPress: () => {
                setPhoto(null);
                setSelectedJustification(null);
                setCustomJustification('');
                checkLastPunch();
              },
            },
          ]
        );
      } else {
        Alert.alert('Erro', result.message || 'Falha ao registrar ponto');
      }
    } catch (error) {
      console.error('Erro ao registrar ponto:', error);
      Alert.alert('Erro', 'Falha na conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  const justificationOptions = punchService.getJustificationOptions();

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContainer}>
        <View style={styles.header}>
          <Text style={styles.title}>Bater Ponto</Text>
          <Text style={styles.subtitle}>
            {moment().format('DD/MM/YYYY HH:mm')}
          </Text>
          {user && (
            <Text style={styles.userInfo}>
              {user.name} • {user.modality === 'presencial' ? 'Presencial' : 'Remoto'}
            </Text>
          )}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>1. Foto do Ponto</Text>
          <TouchableOpacity
            style={styles.photoButton}
            onPress={takePhoto}
            disabled={loading}
          >
            {photo ? (
              <Image source={{ uri: photo }} style={styles.photo} />
            ) : (
              <View style={styles.photoPlaceholder}>
                <Text style={styles.photoPlaceholderText}>
                  Clique para tirar foto
                </Text>
              </View>
            )}
          </TouchableOpacity>
          <Text style={styles.helpText}>
            Tire uma foto para comprovar sua presença
          </Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>2. Justificativa de Atraso (Opcional)</Text>
          <TouchableOpacity
            style={styles.justificationButton}
            onPress={() => setShowJustificationModal(true)}
            disabled={loading}
          >
            <Text style={styles.justificationButtonText}>
              {selectedJustification
                ? selectedJustification.label
                : 'Selecionar justificativa'}
            </Text>
          </TouchableOpacity>
          
          {selectedJustification?.id === 7 && (
            <TextInput
              style={styles.customInput}
              placeholder="Descreva o motivo do atraso"
              value={customJustification}
              onChangeText={setCustomJustification}
              multiline
              numberOfLines={3}
              editable={!loading}
            />
          )}
        </View>

        <TouchableOpacity
          style={[styles.punchButton, loading && styles.punchButtonDisabled]}
          onPress={handlePunch}
          disabled={loading || !photo}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.punchButtonText}>BATER PONTO</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.historyButton}
          onPress={() => navigation.navigate('History')}
          disabled={loading}
        >
          <Text style={styles.historyButtonText}>VER HISTÓRICO</Text>
        </TouchableOpacity>

        <Footer />
      </ScrollView>

      {/* Modal de Justificativas */}
      <Modal
        visible={showJustificationModal}
        animationType="slide"
        transparent={true}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Selecione uma justificativa</Text>
            
            <ScrollView style={styles.justificationList}>
              {justificationOptions.map((item) => (
                <TouchableOpacity
                  key={item.id}
                  style={[
                    styles.justificationItem,
                    selectedJustification?.id === item.id &&
                      styles.justificationItemSelected,
                  ]}
                  onPress={() => selectJustification(item)}
                >
                  <Text
                    style={[
                      styles.justificationItemText,
                      selectedJustification?.id === item.id &&
                        styles.justificationItemTextSelected,
                    ]}
                  >
                    {item.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <View style={styles.modalButtons}>
              <TouchableOpacity
                style={styles.modalButton}
                onPress={() => {
                  setShowJustificationModal(false);
                  setSelectedJustification(null);
                  setCustomJustification('');
                }}
              >
                <Text style={styles.modalButtonText}>Cancelar</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalButton, styles.modalButtonPrimary]}
                onPress={() => setShowJustificationModal(false)}
              >
                <Text style={styles.modalButtonPrimaryText}>Confirmar</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  scrollContainer: {
    padding: 20,
  },
  header: {
    alignItems: 'center',
    marginBottom: 30,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#2c3e50',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#7f8c8d',
    marginBottom: 8,
  },
  userInfo: {
    fontSize: 14,
    color: '#3498db',
    fontWeight: '600',
  },
  section: {
    marginBottom: 30,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#2c3e50',
    marginBottom: 12,
  },
  photoButton: {
    width: '100%',
    height: 250,
    borderRadius: 12,
    overflow: 'hidden',
    backgroundColor: '#f8f9fa',
    borderWidth: 2,
    borderColor: '#e9ecef',
    borderStyle: 'dashed',
  },
  photo: {
    width: '100%',
    height: '100%',
  },
  photoPlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  photoPlaceholderText: {
    fontSize: 16,
    color: '#95a5a6',
  },
  helpText: {
    fontSize: 14,
    color: '#7f8c8d',
    marginTop: 8,
    textAlign: 'center',
  },
  justificationButton: {
    padding: 16,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e9ecef',
    borderRadius: 8,
  },
  justificationButtonText: {
    fontSize: 16,
    color: '#2c3e50',
  },
  customInput: {
    marginTop: 12,
    padding: 12,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e9ecef',
    borderRadius: 8,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  punchButton: {
    backgroundColor: '#2ecc71',
    padding: 18,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 20,
  },
  punchButtonDisabled: {
    backgroundColor: '#bdc3c7',
  },
  punchButtonText: {
    color: '#fff',
    fontSize: 20,
    fontWeight: 'bold',
  },
  historyButton: {
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 15,
    borderWidth: 2,
    borderColor: '#3498db',
  },
  historyButtonText: {
    color: '#3498db',
    fontSize: 16,
    fontWeight: '600',
  },
  // Modal Styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 12,
    maxHeight: '80%',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#2c3e50',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#ecf0f1',
  },
  justificationList: {
    maxHeight: 300,
  },
  justificationItem: {
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#ecf0f1',
  },
  justificationItemSelected: {
    backgroundColor: '#e8f4fc',
  },
  justificationItemText: {
    fontSize: 16,
    color: '#2c3e50',
  },
  justificationItemTextSelected: {
    color: '#3498db',
    fontWeight: '600',
  },
  modalButtons: {
    flexDirection: 'row',
    borderTopWidth: 1,
    borderTopColor: '#ecf0f1',
  },
  modalButton: {
    flex: 1,
    padding: 16,
    alignItems: 'center',
  },
  modalButtonText: {
    fontSize: 16,
    color: '#7f8c8d',
  },
  modalButtonPrimary: {
    borderLeftWidth: 1,
    borderLeftColor: '#ecf0f1',
  },
  modalButtonPrimaryText: {
    fontSize: 16,
    color: '#3498db',
    fontWeight: '600',
  },
});

export default PunchScreen;
