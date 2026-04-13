import { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem('ep4_user');
    return savedUser ? JSON.parse(savedUser) : null;
  });
  const [loading] = useState(false);

  useEffect(() => {
    // Session already initialized in useState
  }, []);

  const login = (userData) => {
    setUser(userData);
    localStorage.setItem('ep4_user', JSON.stringify(userData));
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem('ep4_user');
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

// eslint-disable-next-line react-refresh/only-export-components
export const useAuth = () => useContext(AuthContext);
