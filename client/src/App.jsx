import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import Landing from './pages/Landing';
import Login from './pages/Login';
import Register from './pages/Register';
import StudentLayout from './components/layouts/StudentLayout';
import TeacherLayout from './components/layouts/TeacherLayout';
import Catalog from './pages/student/Catalog';
import Checkout from './pages/student/Checkout';
import MyCourses from './pages/student/MyCourses';
import TeacherDashboard from './pages/teacher/Dashboard';
import Finances from './pages/teacher/Finances';
import AdminLayout from './components/layouts/AdminLayout';
import AdminDashboard from './pages/admin/Dashboard';

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
            
            {/* Student Routes */}
            <Route path="/student" element={
              <ProtectedRoute allowedRole="STUDENT">
                <StudentLayout />
              </ProtectedRoute>
            }>
              <Route path="courses" element={<Catalog />} />
              <Route path="my-courses" element={<MyCourses />} />
              <Route path="checkout/:groupId" element={<Checkout />} />
            </Route>

            {/* Teacher Routes */}
            <Route path="/teacher" element={
              <ProtectedRoute allowedRole="TEACHER">
                <TeacherLayout />
              </ProtectedRoute>
            }>
              <Route path="dashboard" element={<TeacherDashboard />} />
              <Route path="finances" element={<Finances />} />
            </Route>

            {/* Admin Routes */}
            <Route path="/admin" element={
              <ProtectedRoute allowedRole="ADMIN">
                <AdminLayout />
              </ProtectedRoute>
            }>
              <Route path="dashboard" element={<AdminDashboard />} />
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
