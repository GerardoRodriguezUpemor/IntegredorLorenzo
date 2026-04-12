import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { LogOut, BookOpen, UserCircle } from 'lucide-react';

const StudentLayout = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <>
      <header className="navbar">
        <Link to="/student/courses" className="nav-brand">
          <BookOpen className="highlight" />
          <span>Micro<span className="highlight">Cohorts</span></span>
        </Link>
        <div className="nav-links">
          <Link to="/student/my-courses" className="btn btn-outline" style={{ border: 'none' }}>
            Mis Cursos
          </Link>
          <span>Hola, {user?.name}</span>
          <button onClick={handleLogout} className="btn btn-outline" style={{ padding: '0.4rem 1rem' }}>
            <LogOut size={16} /> Salir
          </button>
        </div>
      </header>
      <main className="main-content fade-in">
        <Outlet />
      </main>
    </>
  );
};

export default StudentLayout;
