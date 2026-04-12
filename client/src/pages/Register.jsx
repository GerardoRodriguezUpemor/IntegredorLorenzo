import { useState } from 'react';
import { useNavigate, Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { BookOpen, Users, Rocket } from 'lucide-react';

const Register = () => {
  const [searchParams] = useSearchParams();
  const initialRole = searchParams.get('role') === 'TEACHER' ? 'TEACHER' : 'STUDENT';
  
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState(initialRole);
  const [specialty, setSpecialty] = useState('');
  
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleRegister = async (e) => {
    e.preventDefault();
    if (!email || !name) return;

    try {
      const payload = {
        name,
        email,
        password: role === 'STUDENT' ? 'student123' : 'teacher123',
        role,
        specialty: role === 'TEACHER' ? specialty : undefined
      };

      const res = await fetch('http://127.0.0.1:8000/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      
      const data = await res.json();
      
      if (!res.ok) {
        alert(data.message || 'Error en el registro');
        return;
      }

      const realUser = {
        ...data.data.user,
        _id: data.data.user.id
      };

      login(realUser);
      
      if (role === 'STUDENT') navigate('/student/courses');
      else navigate('/teacher/dashboard');
      
    } catch (err) {
      alert('Error de conexión al registrar');
    }
  };

  return (
    <div className="app-container fade-in" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh', padding: '2rem' }}>
      <div className="glass-panel" style={{ padding: '3rem', width: '100%', maxWidth: '500px' }}>
        
        <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
          <BookOpen size={48} className="highlight" style={{ margin: '0 auto 1rem auto' }} />
          <h2 style={{ fontSize: '2rem', fontWeight: 600 }}>Crear Cuenta</h2>
          <p style={{ color: 'var(--text-muted)' }}>Únete a la nueva era de micro-grupos</p>
        </div>

        <div style={{ display: 'flex', gap: '1rem', marginBottom: '2rem' }}>
          <button 
            type="button"
            className={`btn ${role === 'STUDENT' ? 'btn-primary' : 'btn-outline'}`}
            style={{ flex: 1, padding: '1rem' }}
            onClick={() => setRole('STUDENT')}
          >
            <Rocket size={18} /> Alumno
          </button>
          <button 
            type="button"
            className={`btn ${role === 'TEACHER' ? 'btn-primary' : 'btn-outline'}`}
            style={{ flex: 1, padding: '1rem' }}
            onClick={() => setRole('TEACHER')}
          >
            <Users size={18} /> Profesor
          </button>
        </div>

        <form onSubmit={handleRegister}>
          <div className="form-group">
            <label className="form-label">Nombre completo</label>
            <input 
              type="text" 
              className="form-input" 
              placeholder="John Doe" 
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
            />
          </div>

          <div className="form-group">
            <label className="form-label">Email</label>
            <input 
              type="email" 
              className="form-input" 
              placeholder="tu@email.com" 
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>

          {role === 'TEACHER' && (
            <div className="form-group fade-in">
              <label className="form-label">Especialidad</label>
              <input 
                type="text" 
                className="form-input" 
                placeholder="Ej. Matemáticas Avanzadas" 
                value={specialty}
                onChange={(e) => setSpecialty(e.target.value)}
                required
              />
            </div>
          )}

          <button type="submit" className="btn btn-primary" style={{ width: '100%', padding: '1rem', marginTop: '1rem', fontSize: '1.1rem' }}>
            Completar Registro
          </button>
        </form>

        <p style={{ textAlign: 'center', marginTop: '2rem', color: 'var(--text-muted)' }}>
          ¿Ya tienes cuenta? <Link to="/login" style={{ color: 'var(--primary)', textDecoration: 'none', fontWeight: 600 }}>Inicia Sesión</Link>
        </p>
      </div>
    </div>
  );
};

export default Register;
