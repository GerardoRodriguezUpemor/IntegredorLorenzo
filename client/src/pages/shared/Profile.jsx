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
      // Solo enviamos el campo password si el usuario escribió algo en él
      // para evitar errores de validación de longitud mínima (min:6)
      const submitData = { ...formData };
      if (!submitData.password) {
        delete submitData.password;
      }

      const res = await fetch('http://127.0.0.1:8000/api/v1/profile', {
        method: 'PUT',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-User-Id': user.id || user._id
        },
        body: JSON.stringify(submitData)
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
    <div className="fade-in" style={{ maxWidth: '1000px', margin: '0 auto', paddingBottom: '4rem' }}>
      
      {/* HEADER SECTION */}
      <div className="profile-header-grid">
        <div className="avatar-container">
          <div className="avatar-ring"></div>
          <div className="avatar-main">
            {getInitials(formData.name)}
          </div>
        </div>
        <div>
          <h1 className="title" style={{ fontSize: '3rem', marginBottom: '0.5rem' }}>{formData.name}</h1>
          <div style={{ display: 'flex', gap: '1rem', alignItems: 'center' }}>
            <span className="badge badge-primary" style={{ padding: '0.5rem 1.2rem', fontSize: '0.85rem' }}>
              {user?.role === 'STUDENT' ? '🎓 Estudiante' : (user?.role === 'TEACHER' ? '👨‍🏫 Profesor' : '🛡️ Administrador')}
            </span>
            <span style={{ color: 'var(--text-muted)', fontSize: '0.9rem', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
              <Mail size={14} /> {formData.email}
            </span>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit}>
        {message.text && (
          <div className={`badge ${message.type === 'success' ? 'badge-success' : 'badge-danger'}`} 
               style={{ padding: '1.2rem', width: '100%', textAlign: 'center', marginBottom: '2rem', borderRadius: 'var(--radius-md)', fontSize: '1rem' }}>
            {message.text}
          </div>
        )}

        <div className="grid grid-cols-1 gap-6">
          
          {/* PERSONAL INFO CARD */}
          <div className="section-card">
            <h3 style={{ fontSize: '1.5rem', marginBottom: '2rem', display: 'flex', alignItems: 'center', gap: '0.8rem', color: 'var(--primary)' }}>
              <User size={24} /> Información Personal
            </h3>
            
            <div className="grid grid-cols-2 gap-6">
              <div className="glass-input-group">
                <label className="glass-input-label">Nombre Completo</label>
                <div className="glass-input-wrapper">
                  <User size={18} />
                  <input 
                    type="text" 
                    className="glass-input" 
                    value={formData.name}
                    onChange={(e) => setFormData({...formData, name: e.target.value})}
                    placeholder="Tu nombre real"
                    required 
                  />
                </div>
              </div>

              <div className="glass-input-group">
                <label className="glass-input-label">Correo Electrónico</label>
                <div className="glass-input-wrapper">
                  <Mail size={18} />
                  <input 
                    type="email" 
                    className="glass-input" 
                    value={formData.email}
                    onChange={(e) => setFormData({...formData, email: e.target.value})}
                    placeholder="ejemplo@correo.com"
                    required 
                  />
                </div>
              </div>

              <div className="glass-input-group">
                <label className="glass-input-label">Teléfono de Contacto</label>
                <div className="glass-input-wrapper">
                  <Phone size={18} />
                  <input 
                    type="text" 
                    className="glass-input" 
                    value={formData.phone}
                    onChange={(e) => setFormData({...formData, phone: e.target.value})}
                    placeholder="+52 000 000 0000"
                  />
                </div>
              </div>

              {user?.role === 'PROVIDER' && (
                <div className="glass-input-group">
                  <label className="glass-input-label">Especialidad / Giro</label>
                  <div className="glass-input-wrapper">
                    <Award size={18} />
                    <input 
                      type="text" 
                      className="glass-input" 
                      value={formData.specialty}
                      onChange={(e) => setFormData({...formData, specialty: e.target.value})}
                      placeholder="Ej. Consultoría, Software, etc."
                      required 
                    />
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* SECURITY CARD */}
          <div className="section-card">
            <h3 style={{ fontSize: '1.5rem', marginBottom: '2rem', display: 'flex', alignItems: 'center', gap: '0.8rem', color: 'var(--warning)' }}>
              <Shield size={24} /> Seguridad de la Cuenta
            </h3>
            
            <div className="glass-input-group" style={{ marginBottom: 0 }}>
              <label className="glass-input-label">Nueva Contraseña</label>
              <div className="glass-input-wrapper">
                <Lock size={18} />
                <input 
                  type="password" 
                  className="glass-input" 
                  value={formData.password}
                  onChange={(e) => setFormData({...formData, password: e.target.value})}
                  placeholder="•••••••• (dejar en blanco para no cambiar)"
                />
              </div>
            </div>
          </div>

          {/* ACTIONS */}
          <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem' }}>
            <button type="submit" className="btn btn-primary" disabled={saving} 
                    style={{ flex: 1, padding: '1.5rem', fontSize: '1.2rem', borderRadius: 'var(--radius-lg)' }}>
              {saving ? (
                <><div className="spinner" style={{ marginRight: '10px' }}></div> Guardando...</>
              ) : (
                <><Save size={24} style={{ marginRight: '10px' }}/> Actualizar Perfil Profresional</>
              )}
            </button>
          </div>

        </div>
      </form>
    </div>
  );
};

export default Profile;
