import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Check, X, Search, CheckSquare } from 'lucide-react';

const AdminDashboard = () => {
  const { user } = useAuth();
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchPendingCourses = async () => {
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/admin/courses/pending', {
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        const json = await res.json();
        setCourses(json.data);
      }
    } catch(e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user && user._id) fetchPendingCourses();
  }, [user]);

  const handleApprove = async (courseId) => {
    if (!window.confirm('¿Estás seguro de que deseas APROBAR este curso y liberarlo al público?')) return;
    
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/courses/${courseId}/approve`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        alert('✅ Curso aprobado y liberado exitosamente.');
        fetchPendingCourses();
      } else {
        const d = await res.json();
        alert(d.message);
      }
    } catch(e) {
      alert('Error de conexión');
    }
  };

  const handleReject = async (courseId) => {
    const reason = window.prompt('Por favor ingresa la razón de rechazo para el profesor:');
    if (!reason) return;

    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/admin/courses/${courseId}/reject`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify({ reason })
      });
      if (res.ok) {
        alert('❌ Curso devuelto al profesor.');
        fetchPendingCourses();
      } else {
        const d = await res.json();
        alert(d.message);
      }
    } catch(e) {
      alert('Error de conexión');
    }
  };

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;

  return (
    <div>
      <div style={{ marginBottom: '3rem', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}>
        <div>
          <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Aprobación de Cursos</h1>
          <p style={{ color: 'var(--text-muted)' }}>Cursos enviados a revisión por los Profesores.</p>
        </div>
        <div className="form-group" style={{ position: 'relative', width: '300px', margin: 0 }}>
          <Search size={18} style={{ position: 'absolute', left: '1rem', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
          <input type="text" className="form-input" style={{ paddingLeft: '2.5rem' }} placeholder="Buscar cursos..." />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6">
        {courses.map(course => {
          const courseId = course._id || course.id;
          return (
            <div key={courseId} className="glass-panel" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1.5rem', borderLeft: '4px solid var(--warning)' }}>
              <div style={{ flex: 1, paddingRight: '2rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem' }}>
                  <span className="badge badge-warning" style={{ fontSize: '0.7rem' }}>
                    <CheckSquare size={12} /> PENDING_APPROVAL
                  </span>
                  <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Profesor: {course.teacher?.name || 'Desconocido'}</span>
                </div>
                <h3 style={{ fontSize: '1.4rem', marginBottom: '0.5rem', color: 'white' }}>{course.name}</h3>
                <p style={{ color: 'var(--text-muted)', fontSize: '0.95rem', lineHeight: '1.5' }}>{course.description}</p>
                <div style={{ marginTop: '1rem', fontSize: '0.85rem', color: 'var(--primary)' }}>
                  Precio Calculado Post-Inicialización: ${course.price_override || 200} USD Max.
                </div>
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', minWidth: '150px' }}>
                <button onClick={() => handleApprove(courseId)} className="btn btn-outline" style={{ borderColor: 'var(--success)', color: 'var(--success)', justifyContent: 'center' }}>
                  <Check size={16} /> Aprobar
                </button>
                <button onClick={() => handleReject(courseId)} className="btn btn-outline" style={{ borderColor: 'var(--danger)', color: 'var(--danger)', justifyContent: 'center' }}>
                  <X size={16} /> Rechazar
                </button>
              </div>
            </div>
          );
        })}

        {courses.length === 0 && (
          <div style={{ textAlign: 'center', padding: '5rem', color: 'var(--text-muted)', background: 'rgba(0,0,0,0.1)', borderRadius: 'var(--radius-lg)' }}>
            <CheckSquare size={48} style={{ opacity: 0.2, margin: '0 auto 1rem auto' }} />
            <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>No hay pendientes</h3>
            <p>Se han revisado todos los cursos draft enviados por los profesores.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminDashboard;
