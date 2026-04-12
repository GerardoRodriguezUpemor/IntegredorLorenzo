import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { LogOut, Shield, CheckSquare, BarChart } from 'lucide-react';

const AdminLayout = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <div style={{ display: 'flex', minHeight: '100vh', background: 'var(--bg-dark)' }}>
      {/* Sidebar */}
      <aside className="glass-panel" style={{ width: '280px', borderRadius: 0, borderTop: 'none', borderBottom: 'none', borderLeft: 'none', display: 'flex', flexDirection: 'column' }}>
        <div style={{ padding: '2rem' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '2rem' }}>
            <Shield size={32} style={{ color: 'var(--warning)' }} />
            <div>
              <h1 className="title" style={{ fontSize: '1.5rem', margin: 0, textGradient: 'linear-gradient(45deg, var(--warning), #ffb8b8)' }}>MicroCohorts</h1>
              <span style={{ fontSize: '0.8rem', color: 'var(--warning)', letterSpacing: '2px', fontWeight: 'bold' }}>ADMIN CENTER</span>
            </div>
          </div>
          <div style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>
            <p>Hola, <strong>{user?.name || 'Administrador'}</strong></p>
          </div>
        </div>

        <nav style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', padding: '0 1.5rem', flex: 1 }}>
          <Link to="/admin/dashboard" className="btn btn-outline" style={{ justifyContent: 'flex-start', border: 'none', background: 'var(--bg-card-hover)', color: 'var(--warning)' }}>
            <CheckSquare size={20} /> Aprobación de Cursos
          </Link>
          <div className="btn btn-outline" style={{ justifyContent: 'flex-start', border: 'none', opacity: 0.5, cursor: 'not-allowed' }}>
            <BarChart size={20} /> Métricas Generales
          </div>
        </nav>

        <div style={{ padding: '1.5rem', borderTop: '1px solid var(--border-glass)' }}>
          <button onClick={handleLogout} className="btn btn-outline" style={{ width: '100%', justifyContent: 'center', borderColor: 'var(--danger)', color: 'var(--danger)' }}>
            <LogOut size={18} /> Cerrar Sesión
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main style={{ flex: 1, padding: '3rem 4rem', overflowY: 'auto' }}>
        <Outlet />
      </main>
    </div>
  );
};

export default AdminLayout;
