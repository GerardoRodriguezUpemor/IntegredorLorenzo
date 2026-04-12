import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { BookOpen, Calendar, Download, Trophy, Clock } from 'lucide-react';

const MyCourses = () => {
  const { user } = useAuth();
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchMyCourses = async () => {
      try {
        const res = await fetch('http://127.0.0.1:8000/api/v1/my-courses', {
          headers: { 
            'Accept': 'application/json',
            'X-User-Id': user._id
          }
        });

        if (res.ok) {
          const json = await res.json();
          // Map to match frontend structure
          const mapped = json.data.map(item => {
            // Find winning schedule option (highest votes)
            const winner = item.schedule_options.reduce((prev, current) => 
               (prev.vote_count > current.vote_count) ? prev : current
            , { vote_count: -1 });

            // Just a demo heuristic for date confirmed: if there are votes. In real life we'd use a flag or expiration.
            const isConfirmed = winner.vote_count > 0; 
            
            return {
              _id: item.reservation_id,
              course: {
                name: item.course.name,
                teacher: item.teacher.name
              },
              status: item.status,
              paid_price: item.frozen_price,
              winning_date: winner.proposed_date || null,
              is_date_confirmed: isConfirmed
            };
          });
          setCourses(mapped);
        }
      } catch (e) {
        console.error("Fetch Error:", e);
      } finally {
        setLoading(false);
      }
    };

    if (user && user._id) {
      fetchMyCourses();
    }
  }, [user]);

  const downloadReceipt = (id) => {
    alert(`Descargando recibo de pago PDF de la base de datos...`);
  };

  if (loading) {
    return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;
  }

  return (
    <div className="fade-in">
      <div style={{ marginBottom: '2.5rem' }}>
        <h1 className="title" style={{ fontSize: '2.2rem', marginBottom: '0.5rem' }}>Mis Cursos Suscritos</h1>
        <p style={{ color: 'var(--text-muted)' }}>Historial de tus grupos asegurados y recibos de compra.</p>
      </div>

      {courses.length === 0 ? (
        <div className="glass-panel" style={{ padding: '3rem', textAlign: 'center', color: 'var(--text-muted)' }}>
          <BookOpen size={48} style={{ margin: '0 auto 1rem auto', opacity: 0.5 }} />
          <h3>Aún no estás suscrito a ningún curso.</h3>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          {courses.map(sub => (
            <div key={sub._id} className="glass-panel" style={{ padding: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem' }}>
              
              <div style={{ display: 'flex', alignItems: 'center', gap: '1.5rem', flex: 1, minWidth: '300px' }}>
                <div style={{ width: '60px', height: '60px', borderRadius: 'var(--radius-sm)', background: 'var(--primary-glow)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <BookOpen size={28} color="#fff" />
                </div>
                <div>
                  <h3 style={{ fontSize: '1.3rem', marginBottom: '0.3rem' }}>{sub.course.name}</h3>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Instructor: {sub.course.teacher}</p>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '2rem', flexWrap: 'wrap' }}>
                <div style={{ textAlign: 'right' }}>
                  <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.2rem' }}>Fecha Ganadora</p>
                  {sub.is_date_confirmed ? (
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--success)' }}>
                      <Trophy size={16} /> <span>10 Mayo, 18:00</span>
                    </div>
                  ) : (
                    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', color: 'var(--warning)' }}>
                      <Clock size={16} /> <span>Votos en progreso...</span>
                    </div>
                  )}
                </div>

                <div style={{ padding: '0 1.5rem', borderLeft: '1px solid var(--border-glass)', borderRight: '1px solid var(--border-glass)' }}>
                   <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginBottom: '0.2rem' }}>Inversión</p>
                   <span className="badge badge-success" style={{ fontSize: '1rem', padding: '0.3rem 0.8rem' }}>${sub.paid_price} USD</span>
                </div>

                <button onClick={() => downloadReceipt(sub._id)} className="btn btn-outline" style={{ color: 'var(--secondary)', borderColor: 'var(--secondary)' }}>
                  <Download size={18} /> Recibo PDF
                </button>
              </div>

            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default MyCourses;
