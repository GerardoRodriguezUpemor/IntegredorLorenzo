import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { User, Mail, Phone, Lock, Save, Award, Shield } from 'lucide-react';

const Profile = () => {
  const { user, setUser } = useAuth();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    specialty: '',
    password: ''
  });

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const res = await fetch('http://127.0.0.1:8000/api/v1/profile', {
          headers: { 
            'Accept': 'application/json',
            'X-User-Id': user.id || user._id
          }
        });
        if (res.ok) {
          const json = await res.json();
          const profileData = json.data;
          setFormData({
            name: profileData.name || '',
            email: profileData.email || '',
            phone: profileData.phone || '',
            specialty: profileData.teacher?.specialty || '',
            password: ''
          });
        }
      } catch (e) {
        console.error("DEBUG FETCH PROFILE ERROR:", e);
        setMessage({ type: 'error', text: 'Error al cargar perfil: ' + e.message });
      } finally {
        setLoading(false);
      }
    };

    if (user && (user.id || user._id)) fetchProfile();
  }, [user]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage({ type: '', text: '' });

    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/profile', {
        method: 'PUT',
        headers: { 
          'Content-Type': 'application/json',
          'X-User-Id': user.id || user._id
        },
        body: JSON.stringify(formData)
      });

      const json = await res.json();

      if (res.ok) {
        setMessage({ type: 'success', text: 'Perfil actualizado correctamente.' });
        // Update context to reflect changes (especially name/email)
        setUser({ ...user, name: formData.name, email: formData.email });
        setFormData(prev => ({ ...prev, password: '' })); // Clear password field
      } else {
        setMessage({ type: 'error', text: json.message || 'Error al actualizar.' });
      }
    } catch (e) {
      console.error("DEBUG PROFILE ERROR:", e);
      setMessage({ type: 'error', text: 'Error de conexión: ' + e.message });
    } finally {
      setSaving(false);
    }
  };

  const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || '??';
  };

  if (loading) return <div className="spinner-container"><div className="spinner"></div></div>;

  return (
    <div className="fade-in" style={{ maxWidth: '800px', margin: '0 auto' }}>
      <div style={{ marginBottom: '2rem' }}>
        <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Mi Perfil</h1>
        <p style={{ color: 'var(--text-muted)' }}>Gestiona tu información personal y seguridad de la cuenta.</p>
      </div>

      <div className="glass-panel" style={{ padding: '2rem', marginBottom: '2rem', display: 'flex', gap: '2rem', alignItems: 'center' }}>
        <div style={{ 
          width: '100px', 
          height: '100px', 
          borderRadius: '50%', 
          background: 'linear-gradient(45deg, var(--primary), var(--secondary))',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: '2.5rem',
          fontWeight: 'bold',
          color: 'white',
          boxShadow: '0 0 20px var(--primary-glow)'
        }}>
          {getInitials(formData.name)}
        </div>
        <div>
          <h2 style={{ margin: 0 }}>{formData.name}</h2>
          <span className="badge badge-primary">{user?.role}</span>
          <p style={{ marginTop: '0.5rem', color: 'var(--text-muted)' }}>Desde este panel puedes mantener tus datos al día.</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
        {message.text && (
          <div className={`badge ${message.type === 'success' ? 'badge-success' : 'badge-danger'}`} style={{ padding: '1rem', width: '100%', textAlign: 'center' }}>
            {message.text}
          </div>
        )}

        <div className="grid grid-cols-2 gap-4">
          <div className="input-group">
            <label><User size={16}/> Nombre Completo</label>
            <input 
              type="text" 
              className="glass-input" 
              value={formData.name}
              onChange={(e) => setFormData({...formData, name: e.target.value})}
              required 
            />
          </div>
          <div className="input-group">
            <label><Mail size={16}/> Correo Electrónico</label>
            <input 
              type="email" 
              className="glass-input" 
              value={formData.email}
              onChange={(e) => setFormData({...formData, email: e.target.value})}
              required 
            />
          </div>
          <div className="input-group">
            <label><Phone size={16}/> Teléfono de Contacto</label>
            <input 
              type="text" 
              className="glass-input" 
              placeholder="+52 ..."
              value={formData.phone}
              onChange={(e) => setFormData({...formData, phone: e.target.value})}
            />
          </div>
          
          {user?.role === 'TEACHER' && (
            <div className="input-group">
              <label><Award size={16}/> Especialidad Académica</label>
              <input 
                type="text" 
                className="glass-input" 
                value={formData.specialty}
                onChange={(e) => setFormData({...formData, specialty: e.target.value})}
                required 
              />
            </div>
          )}
        </div>

        <div className="glass-panel" style={{ padding: '1.5rem', marginTop: '1rem' }}>
          <h3 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1.5rem' }}>
            <Shield size={20} color="var(--warning)" /> Seguridad de la Cuenta
          </h3>
          <div className="input-group">
            <label><Lock size={16}/> Nueva Contraseña (dejar en blanco para no cambiar)</label>
            <input 
              type="password" 
              className="glass-input" 
              value={formData.password}
              onChange={(e) => setFormData({...formData, password: e.target.value})}
              placeholder="••••••••"
            />
          </div>
        </div>

        <button type="submit" className="btn btn-primary" disabled={saving} style={{ width: '100%', padding: '1.2rem', fontSize: '1.1rem' }}>
          {saving ? 'Guardando cambios...' : <><Save size={20}/> Guardar Perfil</>}
        </button>
      </form>
    </div>
  );
};

export default Profile;
