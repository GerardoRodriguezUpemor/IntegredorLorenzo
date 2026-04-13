import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { LogOut, Presentation, Users, BookOpen, Activity } from 'lucide-react';

const ProviderLayout = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <div className="app-container" style={{ display: 'flex', flexDirection: 'row' }}>
      
      {/* Sidebar for Provider */}
      <aside style={{ width: '250px', background: 'rgba(15, 15, 19, 0.9)', borderRight: '1px solid var(--border-glass)', display: 'flex', flexDirection: 'column' }}>
        <div style={{ padding: '2rem 1.5rem', borderBottom: '1px solid var(--border-glass)' }}>
          <Link to="/provider/dashboard" className="nav-brand" style={{ fontSize: '1.2rem' }}>
            <BookOpen className="highlight" size={24} />
            <span>Micro<span className="highlight">Cohorts</span></span>
          </Link>
        </div>

        <nav style={{ padding: '2rem 1rem', flex: 1, display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <Link to="/provider/dashboard" className="btn btn-outline" style={{ justifyContent: 'flex-start', border: 'none', background: 'var(--bg-card-hover)', color: 'var(--primary)' }}>
            <Presentation size={20} /> Mis Cohortes
          </Link>
          <Link to="/provider/finances" className="btn btn-outline" style={{ justifyContent: 'flex-start', border: 'none', color: 'var(--text-main)' }}>
            <Activity size={20} /> Finanzas y Gestión
          </Link>
          <Link to="/provider/profile" className="btn btn-outline" style={{ justifyContent: 'flex-start', border: 'none', color: 'var(--text-main)' }}>
            <Users size={20} /> Mi Perfil
          </Link>
        </nav>

        <div style={{ padding: '1.5rem', borderTop: '1px solid var(--border-glass)' }}>
          <div style={{ marginBottom: '1rem' }}>
            <p style={{ margin: 0, fontWeight: 600 }}>{user?.name}</p>
            <p style={{ margin: 0, fontSize: '0.8rem', color: 'var(--text-muted)' }}>Proveedor: {user?.email}</p>
          </div>
          <button onClick={handleLogout} className="btn btn-outline" style={{ width: '100%', justifyContent: 'center' }}>
            <LogOut size={16} /> Salir
          </button>
        </div>
      </aside>

      <main className="main-content fade-in" style={{ flex: 1, overflowY: 'auto', height: '100vh', padding: '3rem 5%' }}>
        <Outlet />
      </main>

    </div>
  );
};

export default ProviderLayout;
