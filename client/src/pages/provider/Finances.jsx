import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Download, Users, Lock, CheckCircle } from 'lucide-react';

const Finances = () => {
  const { user } = useAuth();
  const [courses, setCourses] = useState([]);
  const [subscribersData, setSubscribersData] = useState({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchFinances = async () => {
      try {
        const res = await fetch('http://127.0.0.1:8000/api/v1/provider/courses', {
          headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
        });
        if (res.ok) {
          const json = await res.json();
          // Filter to only APPROVED courses since DRAFT/REJECTED can't have real valid paid students
          const approved = json.data.filter(c => c.status === 'APPROVED');
          setCourses(approved);
          
          approved.forEach(async (c) => {
            const courseId = c._id || c.id;
            const subRes = await fetch(`http://127.0.0.1:8000/api/v1/provider/courses/${courseId}/subscribers`, {
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

    if (user && user._id) {
      fetchFinances();
    }
  }, [user]);

  const handleDownloadPDF = async (groupId, groupName) => {
    try {
      const response = await fetch(`http://127.0.0.1:8000/api/v1/provider/groups/${groupId}/report`, {
        headers: { 
          'X-User-Id': user._id,
          'Accept': 'application/pdf'
        }
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `reporte_finanzas_${groupName.replace(/\s+/g, '_').toLowerCase()}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
      } else {
        const errorData = await response.json();
        alert(errorData.message || "No se pudo generar el reporte.");
      }
    } catch (e) {
      console.error(e);
      alert("Error de conexión con el servidor.");
    }
  };

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;

  return (
    <div>
      <div style={{ marginBottom: '3rem' }}>
        <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Mis Finanzas</h1>
        <p style={{ color: 'var(--text-muted)' }}>Las clases solo se inicializan (y liberan cobro) cuando el cupo máximo (5) es completado.</p>
      </div>

      <div className="grid grid-cols-1 gap-6">
        {courses.map(course => {
          const courseId = course._id || course.id;
          const subs = subscribersData[courseId];
          if (!subs) return null;

          return (
            <div key={courseId} className="glass-panel" style={{ padding: '2rem' }}>
              <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <CheckCircle color="var(--success)" size={24} /> {course.name}
              </h2>

              <div className="grid grid-cols-2 gap-4">
                {subs.groups_summary.map(group => {
                  const isFull = group.current_count === group.max_capacity;
                  // Calculate revenue just for this group
                  const groupSubs = subs.subscribers.filter(s => s.group_id === group.id);
                  const groupRevenue = groupSubs.reduce((acc, current) => acc + (current.frozen_price || 0), 0);
                  
                  return (
                    <div key={group.id} style={{
                      padding: '1.5rem', 
                      background: 'rgba(0,0,0,0.3)', 
                      borderRadius: 'var(--radius-md)',
                      border: isFull ? '1px solid var(--success)' : '1px solid var(--border-glass)',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '1rem'
                    }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <h4 style={{ margin: 0, fontSize: '1.2rem' }}>{group.name}</h4>
                        <span className={`badge ${isFull ? 'badge-success' : 'badge-warning'}`}>
                          {group.current_count} / {group.max_capacity} Clientes
                        </span>
                      </div>

                      <div style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>
                        {isFull 
                          ? 'Clase Inicializada — Lista para impartirse.' 
                          : 'Esperando a que la clase se llene para inicializar.'}
                      </div>

                      <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid var(--border-glass)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                          <span style={{ color: 'var(--text-muted)' }}>Ingreso Acumulado:</span>
                          <span style={{ fontSize: '1.2rem', fontWeight: 'bold', color: isFull ? 'var(--success)' : 'white' }}>
                            ${groupRevenue} USD
                          </span>
                        </div>

                        {isFull ? (
                          <button 
                            onClick={() => handleDownloadPDF(group.id, group.name)}
                            className="btn btn-outline" 
                            style={{ width: '100%', borderColor: 'var(--success)', color: 'var(--success)', justifyContent: 'center' }}
                          >
                            <Download size={18} /> Descargar Recibo PDF
                          </button>
                        ) : (
                          <button className="btn btn-outline" disabled style={{ width: '100%', opacity: 0.5, cursor: 'not-allowed', justifyContent: 'center' }}>
                            <Lock size={18} /> Bloqueado (Faltan {group.max_capacity - group.current_count})
                          </button>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
        {courses.length === 0 && (
          <div style={{ textAlign: 'center', padding: '4rem', color: 'var(--text-muted)' }}>
            No tienes cursos activos o aprobados para generar finanzas.
          </div>
        )}
      </div>
    </div>
  );
};

export default Finances;
