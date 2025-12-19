import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { authService } from '../services/authService';
import { locationService } from '../services/locationService';

const LoginScreen = ({ navigation }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [workLocation, setWorkLocation] = useState({
    latitude: parseFloat(process.env.WORK_LATITUDE) || -23.5505,
    longitude: parseFloat(process.env.WORK_LONGITUDE) || -46.6333,
  });

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Erro', 'Por favor, preencha todos os campos');
      return;
    }

    setLoading(true);
    try {
      const result = await authService.login(email, password);

      if (result.success) {
        const { user } = result;

        // Verificar modalidade do usuário
        if (user.modality === 'presencial') {
          // Verificar localização para usuários presenciais
          try {
            const locationCheck = await locationService.isWithinWorkRadius(
              workLocation.latitude,
              workLocation.longitude,
              50
            );

            if (!locationCheck.isWithinRadius) {
              Alert.alert(
                'Acesso Negado',
                `Você está a ${locationCheck.distance}m do local de trabalho. Necessário estar dentro de 50m.`
              );
              await authService.logout();
              setLoading(false);
              return;
            }
          } catch (locationError) {
            Alert.alert(
              'Erro de Localização',
              'Não foi possível verificar sua localização. Verifique as permissões do aplicativo.'
            );
            await authService.logout();
            setLoading(false);
            return;
          }
        }

        // Navegar para tela principal
        navigation.reset({
          index: 0,
          routes: [{ name: 'Punch' }],
        });
      } else {
        Alert.alert('Erro', result.message || 'Falha no login');
      }
    } catch (error) {
      Alert.alert('Erro', 'Falha na conexão com o servidor');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.scrollContainer}>
        <View style={styles.header}>
          <Text style={styles.title}>Ponto Eletrônico</Text>
          <Text style={styles.subtitle}>Faça login para continuar</Text>
        </View>

        <View style={styles.form}>
          <View style={styles.inputContainer}>
            <Text style={styles.label}>E-mail</Text>
            <TextInput
              style={styles.input}
              placeholder="seu@email.com"
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
              editable={!loading}
            />
          </View>

          <View style={styles.inputContainer}>
            <Text style={styles.label}>Senha</Text>
            <TextInput
              style={styles.input}
              placeholder="Digite sua senha"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              editable={!loading}
            />
          </View>

          <TouchableOpacity
            style={[styles.button, loading && styles.buttonDisabled]}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.buttonText}>Entrar</Text>
            )}
          </TouchableOpacity>

          <View style={styles.infoContainer}>
            <Text style={styles.infoText}>
              Para funcionários presenciais: é necessário estar dentro de 50m do local de trabalho
            </Text>
          </View>
        </View>

        <View style={styles.footer}>
          <Text style={styles.developer}>
            Desenvolvido por: João Carlos Moreira Alves Junior
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  scrollContainer: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
  },
  header: {
    alignItems: 'center',
    marginBottom: 40,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#2c3e50',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#7f8c8d',
  },
  form: {
    width: '100%',
  },
  inputContainer: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#2c3e50',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e9ecef',
    borderRadius: 8,
    padding: 16,
    fontSize: 16,
  },
  button: {
    backgroundColor: '#3498db',
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 10,
  },
  buttonDisabled: {
    backgroundColor: '#bdc3c7',
  },
  buttonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  infoContainer: {
    marginTop: 30,
    padding: 15,
    backgroundColor: '#e8f4fc',
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#3498db',
  },
  infoText: {
    color: '#2c3e50',
    fontSize: 14,
    lineHeight: 20,
  },
  footer: {
    marginTop: 40,
    alignItems: 'center',
  },
  developer: {
    fontSize: 12,
    color: '#7f8c8d',
    fontStyle: 'italic',
    textAlign: 'center',
  },
});

export default LoginScreen;
