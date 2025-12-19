export const validation = {
  isEmail: (email) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  },

  isPasswordValid: (password) => {
    return password.length >= 6;
  },

  isWithinRadius: (distance, maxDistance = 50) => {
    return distance <= maxDistance;
  },

  isTimeBetweenPunchesValid: (lastPunchTime) => {
    const now = Date.now();
    const timeDiff = now - lastPunchTime;
    const minTimeBetweenPunches = 30 * 60 * 1000; // 30 minutos em milissegundos
    return timeDiff >= minTimeBetweenPunches;
  },
};
