import React, { useEffect, useRef } from 'react';
import { AppState } from 'react-native';
import { authService } from '../services/authService';

const SessionTimeout = ({ onTimeout }) => {
  const appState = useRef(AppState.currentState);
  const timeoutRef = useRef(null);

  const checkAndResetTimeout = async () => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    const isExpired = await authService.checkSessionTimeout();
    if (isExpired) {
      onTimeout();
    } else {
      timeoutRef.current = setTimeout(() => {
        onTimeout();
      }, 20 * 60 * 1000); // 20 minutos
    }
  };

  useEffect(() => {
    // Atualizar última atividade
    authService.updateLastActivity();

    // Configurar timeout inicial
    checkAndResetTimeout();

    // Listener para mudanças de estado do app
    const subscription = AppState.addEventListener('change', (nextAppState) => {
      if (
        appState.current.match(/inactive|background/) &&
        nextAppState === 'active'
      ) {
        // App voltou ao foreground
        checkAndResetTimeout();
      }
      appState.current = nextAppState;
    });

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
      subscription?.remove();
    };
  }, []);

  return null;
};

export default SessionTimeout;
