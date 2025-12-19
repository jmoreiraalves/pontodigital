import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { authService } from '../services/authService';

const Footer = () => {
  const [companyName, setCompanyName] = useState('');

  useEffect(() => {
    loadCompanyName();
  }, []);

  const loadCompanyName = async () => {
    const name = await authService.getCompanyName();
    setCompanyName(name);
  };

  return (
    <View style={styles.footer}>
      <Text style={styles.companyName}>{companyName}</Text>
      <Text style={styles.developer}>Desenvolvido por: João Carlos Moreira Alves Junior</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  footer: {
    padding: 16,
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#e0e0e0',
    backgroundColor: '#f5f5f5',
  },
  companyName: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 4,
  },
  developer: {
    fontSize: 12,
    color: '#666',
    fontStyle: 'italic',
  },
});

export default Footer;
