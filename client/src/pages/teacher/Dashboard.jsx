import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Plus, Send, Users, Activity, ExternalLink, Calendar as CalendarIcon } from 'lucide-react';

const TeacherDashboard = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [subscribersData, setSubscribersData] = useState({});
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDatesModal, setShowDatesModal] = useState(false);
  const [activeCourseId, setActiveCourseId] = useState(null);
  const [activeGroupId, setActiveGroupId] = useState(null);
  
  const [newCourseName, setNewCourseName] = useState('');
  const [newCourseDesc, setNewCourseDesc] = useState('');
  
  const [dates, setDates] = useState(['', '', '']);

  const fetchCourses = async () => {
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/teacher/courses', {
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        const json = await res.json();
        setCourses(json.data);
        
        // Fetch subscribers summary for each course
        json.data.forEach(async (c) => {
          const courseId = c._id || c.id;
          const subRes = await fetch(`http://127.0.0.1:8000/api/v1/teacher/courses/${courseId}/subscribers`, {
            headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
          });
          if (subRes.ok) {
            const subJson = await subRes.json();
            setSubscribersData(prev => ({ ...prev, [courseId]: subJson.data }));
          }
        });
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user && user._id) {
      fetchCourses();
    }
  }, [user]);

  const handleCreateCourse = async (e) => {
    e.preventDefault();
    try {
      const res = await fetch('http://127.0.0.1:8000/api/v1/teacher/courses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify({ name: newCourseName, description: newCourseDesc, groups_count: 2 })
      });
      if (res.ok) {
        setNewCourseName('');
        setNewCourseDesc('');
        setShowCreateModal(false);
        fetchCourses();
        alert('Curso DRAFT creado con éxito. Carga fechas para proponer grupos.');
      } else {
        const d = await res.json();
        alert(d.message);
      }
    } catch (error) {
      alert('Error connecting to API');
    }
  };

  const handleSubmitCourse = async (courseId) => {
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/teacher/courses/${courseId}/submit`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      if (res.ok) {
        alert('Curso mandado a revisión de administrador');
        fetchCourses();
      }
    } catch (e) {
      alert('Error');
    }
  };

  const handleEditCourse = async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/teacher/courses/${activeCourseId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify({ name: newCourseName, description: newCourseDesc })
      });
      if (res.ok) {
        setShowEditModal(false);
        fetchCourses();
      } else {
        const d = await res.json();
        alert(d.message);
      }
    } catch (e) { alert('Error conectando al servidor'); }
  };

  const handleDateSubmit = async (e) => {
    e.preventDefault();
    try {
      const validDates = dates.filter(d => d.trim() !== '');
      if (validDates.length < 3) return alert('Debes proveer al menos 3 fechas futuras.');
      
      const payload = {
         dates: validDates.map(d => new Date(d).toISOString().split('.')[0] + 'Z')
      };

      const res = await fetch(`http://127.0.0.1:8000/api/v1/teacher/groups/${activeGroupId}/schedule`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify(payload)
      });
      if (res.ok) {
        alert('Fechas propuestas agregadas al grupo exitosamente.');
        setShowDatesModal(false);
        fetchCourses();
      } else {
         const d = await res.json();
         alert(d.message);
      }
    } catch (e) { alert('Error conectando al servidor'); }
  };

  const openEditModal = (course) => {
    setActiveCourseId(course._id || course.id);
    setNewCourseName(course.name);
    setNewCourseDesc(course.description);
    setShowEditModal(true);
  };

  const openDatesModal = (groupId) => {
    setActiveGroupId(groupId);
    setDates(['', '', '']);
    setShowDatesModal(true);
  };

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '3rem' }}>
        <div>
          <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Panel de Tutor</h1>
          <p style={{ color: 'var(--text-muted)' }}>Gestiona tus micro-grupos, revisa ingresos generados y propone fechas.</p>
        </div>
        <button className="btn btn-primary" onClick={() => setShowCreateModal(true)}>
          <Plus size={20} /> Nuevo Curso
        </button>
      </div>

      <div className="grid grid-cols-2 gap-6">
        {courses.map(course => {
          const courseId = course._id || course.id;
          const subs = subscribersData[courseId];
          const totalRevenue = subs?.subscribers?.reduce((sum, s) => sum + (s.frozen_price || 0), 0) || 0;
          
          return (
            <div key={courseId} className="glass-panel" style={{ display: 'flex', flexDirection: 'column' }}>
              <div style={{ padding: '1.5rem', borderBottom: '1px solid var(--border-glass)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
                  <span className={`badge ${course.status === 'APPROVED' ? 'badge-success' : (course.status === 'DRAFT' ? 'badge-warning' : 'badge-primary')}`}>
                    {course.status}
                  </span>
                  <span style={{ color: 'var(--success)', fontWeight: 'bold' }}>
                    ${totalRevenue} USD Generados
                  </span>
                </div>
                <h3 style={{ fontSize: '1.4rem', marginBottom: '0.5rem' }}>{course.name}</h3>
                <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '1.5rem', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                  {course.description}
                </p>
                
                <div style={{ display: 'flex', gap: '2rem' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-muted)' }}>
                    <Users size={18} color="var(--primary)" />
                    <span>{subs?.total_subscribers || 0} Alumnos Inscritos</span>
                  </div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--text-muted)' }}>
                    <Activity size={18} color="var(--secondary)" />
                    <span>{course.groups?.length || 0} Grupos Activos</span>
                  </div>
                </div>
              </div>

              <div style={{ padding: '1rem 1.5rem', background: 'rgba(0,0,0,0.2)', borderBottomLeftRadius: 'var(--radius-md)', borderBottomRightRadius: 'var(--radius-md)' }}>
                {course.status === 'DRAFT' && (
                  <div style={{ marginBottom: '1.5rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                    <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)', marginBottom: '0.2rem' }}>Debes proponer fechas para tus grupos antes de pedir aprobación.</p>
                    <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                      {course.groups?.map((g, i) => (
                        <button key={g._id || g.id} onClick={() => openDatesModal(g._id || g.id)} className="btn btn-outline" style={{ fontSize: '0.8rem', padding: '0.4rem 0.8rem' }}>
                          <CalendarIcon size={14} /> Fechas G{i+1}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
                
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  {course.status !== 'DRAFT' ? (
                    <button onClick={() => navigate('/teacher/finances')} className="btn btn-outline" style={{ padding: '0.5rem 1rem', fontSize: '0.85rem', border: 'none', color: 'var(--text-main)' }}>
                      <CalendarIcon size={16} /> Ver Resultados de Votación
                    </button>
                  ) : (
                    <button onClick={() => openEditModal(course)} className="btn btn-outline" style={{ padding: '0.5rem 1rem', fontSize: '0.85rem', border: 'none', color: 'var(--text-main)' }}>
                      📝 Editar Curso
                    </button>
                  )}

                  {course.status === 'DRAFT' && (
                    <button onClick={() => handleSubmitCourse(courseId)} className="btn btn-primary" style={{ padding: '0.5rem 1rem', fontSize: '0.85rem' }}>
                      <Send size={16} /> Pedir Aprobación
                    </button>
                  )}
                </div>
              </div>
            </div>
          );
        })}
        {courses.length === 0 && (
          <div style={{ gridColumn: 'span 2', textAlign: 'center', padding: '4rem', color: 'var(--text-muted)' }}>
            <p>Todavía no tienes cursos creados.</p>
          </div>
        )}
      </div>

      {/* CREATE MODAL */}
      {showCreateModal && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.8)', backdropFilter: 'blur(5px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
          <div className="glass-panel fade-in" style={{ padding: '2.5rem', width: '100%', maxWidth: '500px' }}>
            <h2 style={{ fontSize: '1.8rem', marginBottom: '1.5rem' }}>Crear Nuevo Curso</h2>
            <form onSubmit={handleCreateCourse}>
              <div className="form-group">
                <label className="form-label">Nombre del Curso</label>
                <input required type="text" className="form-input" value={newCourseName} onChange={e => setNewCourseName(e.target.value)} placeholder="Ej. Taller de React Avanzado" />
              </div>
              <div className="form-group">
                <label className="form-label">Descripción</label>
                <textarea required className="form-input" value={newCourseDesc} onChange={e => setNewCourseDesc(e.target.value)} placeholder="Describe qué aprenderán..." rows={4}></textarea>
              </div>
              <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
                <button type="button" className="btn btn-outline" style={{ flex: 1 }} onClick={() => setShowCreateModal(false)}>Cancelar</button>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }}>Crear Borrador</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* EDIT MODAL */}
      {showEditModal && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.8)', backdropFilter: 'blur(5px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
          <div className="glass-panel fade-in" style={{ padding: '2.5rem', width: '100%', maxWidth: '500px' }}>
            <h2 style={{ fontSize: '1.8rem', marginBottom: '1.5rem' }}>Editar Curso</h2>
            <form onSubmit={handleEditCourse}>
              <div className="form-group">
                <label className="form-label">Nombre del Curso</label>
                <input required type="text" className="form-input" value={newCourseName} onChange={e => setNewCourseName(e.target.value)} />
              </div>
              <div className="form-group">
                <label className="form-label">Descripción</label>
                <textarea required className="form-input" value={newCourseDesc} onChange={e => setNewCourseDesc(e.target.value)} rows={4}></textarea>
              </div>
              <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
                <button type="button" className="btn btn-outline" style={{ flex: 1 }} onClick={() => setShowEditModal(false)}>Cancelar</button>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }}>Guardar Cambios</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* DATES MODAL */}
      {showDatesModal && (
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.8)', backdropFilter: 'blur(5px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
          <div className="glass-panel fade-in" style={{ padding: '2.5rem', width: '100%', maxWidth: '500px' }}>
            <h2 style={{ fontSize: '1.8rem', marginBottom: '1.5rem' }}>Proponer Fechas de Grupo</h2>
            <p style={{ color: 'var(--text-muted)', marginBottom: '1rem', fontSize: '0.9rem' }}>Propón hasta 3 fechas futuras para que los alumnos voten durante la inscripción.</p>
            <form onSubmit={handleDateSubmit}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                {dates.map((date, idx) => (
                  <div key={idx} className="form-group">
                    <label className="form-label">Opción {idx + 1}</label>
                    <input type="datetime-local" className="form-input" required={idx < 3} value={date} onChange={e => {
                      const newDates = [...dates];
                      newDates[idx] = e.target.value;
                      setDates(newDates);
                    }} />
                  </div>
                ))}
              </div>
              <div style={{ display: 'flex', gap: '1rem', marginTop: '2rem' }}>
                <button type="button" className="btn btn-outline" style={{ flex: 1 }} onClick={() => setShowDatesModal(false)}>Cancelar</button>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }}>Guardar Opciones</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default TeacherDashboard;
