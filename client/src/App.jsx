import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import Landing from './pages/Landing';
import Login from './pages/Login';
import Register from './pages/Register';
import ClientLayout from './components/layouts/ClientLayout';
import ProviderLayout from './components/layouts/ProviderLayout';
import Catalog from './pages/client/Catalog';
import Checkout from './pages/client/Checkout';
import MyCourses from './pages/client/MyCourses';
import ProviderDashboard from './pages/provider/Dashboard';
import Finances from './pages/provider/Finances';
import AdminLayout from './components/layouts/AdminLayout';
import AdminDashboard from './pages/admin/Dashboard';
import Profile from './pages/shared/Profile';

// A simple protected route wrapper
const ProtectedRoute = ({ children, allowedRole }) => {
  const { user, loading } = useAuth();
  if (loading) return <div>Loading...</div>;
  if (!user) return <Navigate to="/login" replace />;
  if (allowedRole && user.role !== allowedRole) return <Navigate to="/" replace />;
  return children;
};

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <div className="app-container">
          <Routes>
            <Route path="/" element={<Landing />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            
            {/* Client Routes */}
            <Route path="/client" element={
              <ProtectedRoute allowedRole="CLIENT">
                <ClientLayout />
              </ProtectedRoute>
            }>
              <Route path="services" element={<Catalog />} />
              <Route path="my-services" element={<MyCourses />} />
              <Route path="checkout/:groupId" element={<Checkout />} />
              <Route path="profile" element={<Profile />} />
            </Route>

            {/* Provider Routes */}
            <Route path="/provider" element={
              <ProtectedRoute allowedRole="PROVIDER">
                <ProviderLayout />
              </ProtectedRoute>
            }>
              <Route path="dashboard" element={<ProviderDashboard />} />
              <Route path="finances" element={<Finances />} />
              <Route path="profile" element={<Profile />} />
            </Route>

            {/* Admin Routes */}
            <Route path="/admin" element={
              <ProtectedRoute allowedRole="ADMIN">
                <AdminLayout />
              </ProtectedRoute>
            }>
              <Route path="dashboard" element={<AdminDashboard />} />
              <Route path="profile" element={<Profile />} />
            </Route>
            
            {/* Catch All */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </div>
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
