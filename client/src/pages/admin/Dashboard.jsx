import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Check, X, Search, CheckSquare, FileText, UserPlus, Users, Trash2, ShieldCheck } from 'lucide-react';

const AdminDashboard = () => {
  const { user } = useAuth();
  const [courses, setCourses] = useState([]);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchPendingCourses = useCallback(async () => {
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/admin/courses/pending', {
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        const json = await res.json();
        setCourses(json.data);
      }
    } catch(e) { console.error(e); }
  }, [user._id]);

  const fetchUsers = useCallback(async () => {
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/admin/users', {
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        const json = await res.json();
        setUsers(json.data);
      }
    } catch(e) { console.error(e); }
  }, [user._id]);

  useEffect(() => {
    if (user && user._id) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      Promise.all([fetchPendingCourses(), fetchUsers()]).finally(() => setLoading(false));
    }
  }, [user, fetchPendingCourses, fetchUsers]);

  const handleApprove = async (courseId) => {
    if (!window.confirm('¿Estás seguro de que deseas APROBAR este curso?')) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/courses/${courseId}/approve`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) { alert('✅ Curso aprobado.'); fetchPendingCourses(); }
    } catch { alert('Error de conexión'); }
  };

  const handleReject = async (courseId) => {
    const reason = window.prompt('Razón de rechazo:');
    if (!reason) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/courses/${courseId}/reject`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify({ reason })
      });
      if (res.ok) { alert('❌ Curso devuelto.'); fetchPendingCourses(); }
    } catch { alert('Error de conexión'); }
  };

  const handleDeleteUser = async (userId) => {
    if (!window.confirm('¿Estás seguro de eliminar (borrado lógico) a este usuario?')) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/users/${userId}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) { alert('✅ Usuario eliminado correctamente.'); fetchUsers(); }
      else { const d = await res.json(); alert(d.message); }
    } catch { alert('Error de conexión'); }
  };

  const downloadReport = async (type) => {
    const endpoint = type === 'users' ? 'users/report' : 'courses/report';
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/${endpoint}`, {
        headers: { 'X-User-Id': user._id }
      });
      if (res.ok) {
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = type === 'users' ? 'directorio_usuarios.pdf' : 'catalogo_cursos.pdf';
        a.click();
      }
    } catch { alert('Error al descargar reporte'); }
  };

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;

  return (
    <div className="fade-in">
      <div style={{ marginBottom: '3rem', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
        <div>
          <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Panel Administrativo</h1>
          <p style={{ color: 'var(--text-muted)' }}>Control total del ecosistema MicroCohorts.</p>
        </div>
        <div style={{ display: 'flex', gap: '1rem' }}>
          <button onClick={() => downloadReport('users')} className="btn btn-outline" style={{ gap: '0.5rem' }}>
            <Users size={18} /> Reporte Usuarios
          </button>
          <button onClick={() => downloadReport('courses')} className="btn btn-primary" style={{ gap: '0.5rem' }}>
            <FileText size={18} /> Catálogo Servicios
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-8">
        
        {/* PENDING COURSES */}
        <div className="section-card" style={{ padding: '1.5rem' }}>
          <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.8rem' }}>
            <CheckSquare size={24} color="var(--warning)" /> Servicios Pendientes de Revisión
          </h2>
          <div className="grid grid-cols-1 gap-4">
            {courses.map(course => (
              <div key={course._id} className="glass-panel" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1.2rem' }}>
                <div>
                  <h4 style={{ fontSize: '1.1rem', marginBottom: '0.2rem' }}>{course.name}</h4>
                  <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Prov: {course.teacher?.name}</p>
                </div>
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                  <button onClick={() => handleApprove(course._id)} className="btn btn-outline" style={{ color: 'var(--success)', borderColor: 'var(--success)', padding: '0.4rem 1rem' }}>Aprobar</button>
                  <button onClick={() => handleReject(course._id)} className="btn btn-outline" style={{ color: 'var(--danger)', borderColor: 'var(--danger)', padding: '0.4rem 1rem' }}>Rechazar</button>
                </div>
              </div>
            ))}
            {courses.length === 0 && <p style={{ color: 'var(--text-muted)', textAlign: 'center', padding: '2rem' }}>Todo al día. No hay cursos pendientes.</p>}
          </div>
        </div>

        {/* USER LIST (CRUD) */}
        <div className="section-card" style={{ padding: '1.5rem' }}>
          <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.8rem' }}>
            <ShieldCheck size={24} color="var(--primary)" /> Gestión de Usuarios (CRUD)
          </h2>
          <div className="glass-panel" style={{ overflow: 'hidden' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
              <thead style={{ background: 'rgba(255,255,255,0.05)', fontSize: '0.85rem' }}>
                <tr>
                  <th style={{ padding: '1rem' }}>Nombre</th>
                  <th style={{ padding: '1rem' }}>Rol</th>
                  <th style={{ padding: '1rem' }}>Estado</th>
                  <th style={{ padding: '1rem' }}>Acciones</th>
                </tr>
              </thead>
              <tbody>
                {users.map(u => (
                  <tr key={u.id} style={{ borderBottom: '1px solid var(--border-glass)' }}>
                    <td style={{ padding: '1rem' }}>
                      <div style={{ fontWeight: 600 }}>{u.name}</div>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{u.email}</div>
                    </td>
                    <td style={{ padding: '1rem' }}>
                      <span className={`badge ${u.role === 'ADMIN' ? 'badge-danger' : 'badge-primary'}`} style={{ fontSize: '0.65rem' }}>{u.role}</span>
                    </td>
                    <td style={{ padding: '1rem' }}>
                      <span style={{ color: u.is_active ? 'var(--success)' : 'var(--danger)', fontSize: '0.8rem' }}>
                        {u.is_active ? '● Activo' : '○ Inactivo'}
                      </span>
                    </td>
                    <td style={{ padding: '1rem' }}>
                      <button 
                        onClick={() => handleDeleteUser(u.id)}
                        className="btn btn-outline" 
                        style={{ padding: '0.4rem', color: u.id === user._id ? '#444' : 'var(--danger)', borderColor: u.id === user._id ? '#333' : 'var(--danger)' }}
                        disabled={u.id === user._id}
                        title="Borrado Lógico"
                      >
                        <Trash2 size={16} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  );
};

export default AdminDashboard;
