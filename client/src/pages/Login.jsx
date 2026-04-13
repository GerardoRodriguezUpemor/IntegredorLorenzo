import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { BookOpen, AlertCircle } from 'lucide-react';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('CLIENT');
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleLogin = async (e) => {
    e.preventDefault();
    if (!email || !password) {
      setError('Por favor ingresa email y contraseña');
      return;
    }
    
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.message || 'Error de autenticación');
        return;
      }

      // Map the returned user id to _id so it conforms to the standard we setup earlier
      const realUser = {
        ...data.data.user,
        _id: data.data.user.id
      };

      login(realUser);

      if (realUser.role === 'CLIENT') navigate('/client/services');
      else if (realUser.role === 'PROVIDER') navigate('/provider/dashboard');
      else navigate('/admin/dashboard');

    } catch {
      setError('Error conectando al servidor');
    }
  };

  return (
    <div className="app-container fade-in" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh' }}>
      <div className="glass-panel" style={{ padding: '3rem', width: '100%', maxWidth: '450px' }}>
        
        <div style={{ textAlign: 'center', marginBottom: '2.5rem' }}>
          <BookOpen size={48} className="highlight" style={{ margin: '0 auto 1rem auto' }} />
          <h2 style={{ fontSize: '2rem', fontWeight: 600 }}>Bienvenido de nuevo</h2>
          <p style={{ color: 'var(--text-muted)' }}>Accede a tu cuenta de MicroCohorts</p>
        </div>

        {error && (
          <div style={{ backgroundColor: 'rgba(255, 71, 87, 0.1)', border: '1px solid var(--danger)', padding: '1rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.5rem', display: 'flex', gap: '0.5rem', alignItems: 'center', color: 'var(--danger)' }}>
            <AlertCircle size={20} />
            <span style={{ fontSize: '0.9rem' }}>{error}</span>
          </div>
        )}

        <form onSubmit={handleLogin}>
          <div className="form-group">
            <label className="form-label">Email</label>
            <input 
              type="email" 
              className="form-input" 
              placeholder="tu@email.com" 
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>

          <div className="form-group">
            <label className="form-label">Contraseña</label>
            <input 
              type="password" 
              className="form-input" 
              placeholder="••••••••" 
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <div className="form-group">
            <label className="form-label">Rol de ingreso</label>
            <select 
              className="form-select"
              value={role}
              onChange={(e) => setRole(e.target.value)}
            >
              <option value="CLIENT">Cliente</option>
              <option value="PROVIDER">Proveedor</option>
              <option value="ADMIN">Administrador</option>
            </select>
          </div>

          <button type="submit" className="btn btn-primary" style={{ width: '100%', padding: '1rem', marginTop: '1rem', fontSize: '1.1rem' }}>
            Iniciar Sesión
          </button>
        </form>

        <p style={{ textAlign: 'center', marginTop: '2rem', color: 'var(--text-muted)' }}>
          ¿No tienes cuenta? <Link to="/register" style={{ color: 'var(--primary)', textDecoration: 'none', fontWeight: 600 }}>Regístrate</Link>
        </p>
      </div>
    </div>
  );
};

export default Login;
