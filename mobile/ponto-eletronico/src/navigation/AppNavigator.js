import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import LoginScreen from '../screens/LoginScreen';
import PunchScreen from '../screens/PunchScreen';
import HistoryScreen from '../screens/HistoryScreen';
import SessionTimeout from '../components/SessionTimeout';

const Stack = createStackNavigator();

const AppNavigator = () => {
  const [isSessionExpired, setIsSessionExpired] = React.useState(false);

  const handleSessionTimeout = () => {
    setIsSessionExpired(true);
  };

  return (
    <NavigationContainer>
      <SessionTimeout onTimeout={handleSessionTimeout} />
      <Stack.Navigator
        screenOptions={{
          headerShown: false,
        }}
      >
        {isSessionExpired ? (
          <Stack.Screen name="Login" component={LoginScreen} />
        ) : (
          <>
            <Stack.Screen name="Login" component={LoginScreen} />
            <Stack.Screen name="Punch" component={PunchScreen} />
            <Stack.Screen name="History" component={HistoryScreen} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
};

export default AppNavigator;
