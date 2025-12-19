import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { punchService } from '../services/punchService';
import Footer from '../components/Footer';
import moment from 'moment';

const HistoryScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedDays, setSelectedDays] = useState(7);
  const [punchHistory, setPunchHistory] = useState([]);
  const [stats, setStats] = useState({
    total: 0,
    onTime: 0,
    late: 0,
  });

  const daysOptions = [
    { label: '7 dias', value: 7 },
    { label: '15 dias', value: 15 },
    { label: '30 dias', value: 30 },
    { label: '60 dias', value: 60 },
  ];

  useEffect(() => {
    loadHistory();
  }, [selectedDays]);

  const loadHistory = async () => {
    try {
      setLoading(true);
      const result = await punchService.getPunchHistory(selectedDays);
      
      if (result.success) {
        setPunchHistory(result.data.records);
        calculateStats(result.data.records);
      }
    } catch (error) {
      console.error('Erro ao carregar histórico:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const calculateStats = (records) => {
    const total = records.length;
    const onTime = records.filter(record => !record.justification).length;
    const late = total - onTime;
    
    setStats({ total, onTime, late });
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadHistory();
  };

  const formatTime = (timestamp) => {
    return moment(timestamp).format('HH:mm');
  };

  const formatDate = (timestamp) => {
    return moment(timestamp).format('DD/MM/YYYY');
  };

  const getJustificationText = (justification) => {
    if (!justification) return 'No horário';
    return justification.length > 30 
      ? `${justification.substring(0, 30)}...` 
      : justification;
  };

  return (
    <View style={styles.container}>
      <ScrollView
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        <View style={styles.header}>
          <Text style={styles.title}>Histórico de Pontos</Text>
          
          <View style={styles.daysSelector}>
            {daysOptions.map((option) => (
              <TouchableOpacity
                key={option.value}
                style={[
                  styles.dayButton,
                  selectedDays === option.value && styles.dayButtonActive,
                ]}
                onPress={() => setSelectedDays(option.value)}
              >
                <Text
                  style={[
                    styles.dayButtonText,
                    selectedDays === option.value && styles.dayButtonTextActive,
                  ]}
                >
                  {option.label}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.statsContainer}>
            <View style={styles.statItem}>
              <Text style={styles.statNumber}>{stats.total}</Text>
              <Text style={styles.statLabel}>Total</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={[styles.statNumber, styles.statOnTime]}>
                {stats.onTime}
              </Text>
              <Text style={styles.statLabel}>No Horário</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={[styles.statNumber, styles.statLate]}>
                {stats.late}
              </Text>
              <Text style={styles.statLabel}>Com Atraso</Text>
            </View>
          </View>
        </View>

        {loading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#3498db" />
            <Text style={styles.loadingText}>Carregando histórico...</Text>
          </View>
        ) : punchHistory.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>Nenhum ponto registrado</Text>
          </View>
        ) : (
          <View style={styles.historyList}>
            {punchHistory.map((record, index) => (
              <View key={index} style={styles.historyItem}>
                <View style={styles.historyItemHeader}>
                  <Text style={styles.historyDate}>
                    {formatDate(record.timestamp)}
                  </Text>
                  <Text style={styles.historyTime}>
                    {formatTime(record.timestamp)}
                  </Text>
                </View>
                
                <View style={styles.historyItemDetails}>
                  <Text style={styles.historyType}>
                    {record.type === 'entry' ? 'Entrada' : 'Saída'}
                  </Text>
                  
                  {record.justification && (
                    <View style={styles.justificationContainer}>
                      <Text style={styles.justificationLabel}>Justificativa:</Text>
                      <Text style={styles.justificationText}>
                        {getJustificationText(record.justification)}
                      </Text>
                    </View>
                  )}
                  
                  {record.location && (
                    <Text style={styles.locationText}>
                      Local: {record.location.address || 'Registrado com GPS'}
                    </Text>
                  )}
                </View>
                
                <View style={[
                  styles.statusIndicator,
                  record.justification ? styles.statusLate : styles.statusOnTime
                ]} />
              </View>
            ))}
          </View>
        )}

        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.backButtonText}>VOLTAR</Text>
        </TouchableOpacity>

        <Footer />
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  header: {
    padding: 20,
    backgroundColor: '#f8f9fa',
    borderBottomWidth: 1,
    borderBottomColor: '#e9ecef',
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#2c3e50',
    marginBottom: 20,
    textAlign: 'center',
  },
  daysSelector: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: 20,
  },
  dayButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#ecf0f1',
    marginHorizontal: 4,
  },
  dayButtonActive: {
    backgroundColor: '#3498db',
  },
  dayButtonText: {
    fontSize: 14,
    color: '#7f8c8d',
  },
  dayButtonTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  statsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statItem: {
    alignItems: 'center',
  },
  statNumber: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#2c3e50',
  },
  statOnTime: {
    color: '#2ecc71',
  },
  statLate: {
    color: '#e74c3c',
  },
  statLabel: {
    fontSize: 12,
    color: '#7f8c8d',
    marginTop: 4,
  },
  loadingContainer: {
    padding: 40,
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: '#7f8c8d',
  },
  emptyContainer: {
    padding: 40,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 18,
    color: '#bdc3c7',
  },
  historyList: {
    padding: 20,
  },
  historyItem: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#ecf0f1',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
    position: 'relative',
  },
  historyItemHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  historyDate: {
    fontSize: 16,
    fontWeight: '600',
    color: '#2c3e50',
  },
  historyTime: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#3498db',
  },
  historyItemDetails: {
    marginBottom: 8,
  },
  historyType: {
    fontSize: 14,
    color: '#7f8c8d',
    marginBottom: 8,
  },
  justificationContainer: {
    marginTop: 8,
    padding: 8,
    backgroundColor: '#f8f9fa',
    borderRadius: 6,
  },
  justificationLabel: {
    fontSize: 12,
    color: '#95a5a6',
    marginBottom: 2,
  },
  justificationText: {
    fontSize: 14,
    color: '#e74c3c',
  },
  locationText: {
    fontSize: 12,
    color: '#95a5a6',
    marginTop: 8,
  },
  statusIndicator: {
    position: 'absolute',
    right: 0,
    top: 0,
    bottom: 0,
    width: 4,
    borderTopRightRadius: 12,
    borderBottomRightRadius: 12,
  },
  statusOnTime: {
    backgroundColor: '#2ecc71',
  },
  statusLate: {
    backgroundColor: '#e74c3c',
  },
  backButton: {
    padding: 16,
    borderRadius: 8,
    alignItems: 'center',
    margin: 20,
    backgroundColor: '#f8f9fa',
    borderWidth: 1,
    borderColor: '#e9ecef',
  },
  backButtonText: {
    color: '#2c3e50',
    fontSize: 16,
    fontWeight: '600',
  },
});

export default HistoryScreen;
