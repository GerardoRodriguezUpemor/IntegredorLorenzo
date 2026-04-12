import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { Search, MapPin, Users, Clock, ArrowRight } from 'lucide-react';

const Catalog = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [courses, setCourses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // In a real app we'd fetch from Laravel. We'll simulate the API call here to demonstrate pure frontend logic first.
    const fetchCatalog = async () => {
      try {
        const res = await fetch('http://127.0.0.1:8000/api/v1/courses', {
          headers: { 'Accept': 'application/json' }
        });
        
        if (res.ok) {
          const json = await res.json();
          // Map to match the component's expected structure
          const mappedCourses = json.data.map(c => ({
            ...c,
            _id: c.id,
            groups: c.groups.map(g => {
              // Replicate pricing formula for fast frontend display: P(n) = 50 + 70 * exp(1.25 * (1 - n))
              // Real calculation is done strictly in backend when reserving.
              const currentPrice = Math.round(50 + 70 * Math.exp(1.25 * (1 - (g.current_count + 1))));
              return {
                 ...g,
                 _id: g.id,
                 priceBreakdown: { currentPrice }
              };
            })
          }));
          
          setCourses(mappedCourses);
        }
      } catch (e) {
        console.error("API Fetch Error:", e);
      } finally {
        setLoading(false);
      }
    };

    fetchCatalog();
  }, [user]);

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}>
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="fade-in">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '2rem' }}>
        <div>
          <h1 className="title" style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>Explorar Cohortes</h1>
          <p style={{ color: 'var(--text-muted)' }}>Cursos exclusivos. Topes estrictos de 5 alumnos. Precios dinámicos.</p>
        </div>
        <div className="glass-panel" style={{ display: 'flex', alignItems: 'center', padding: '0.5rem 1rem', width: '300px' }}>
          <Search size={18} color="var(--text-muted)" style={{ marginRight: '0.5rem' }} />
          <input 
            type="text" 
            placeholder="Buscar por tecnología..." 
            style={{ background: 'transparent', border: 'none', color: 'white', flex: 1, outline: 'none' }}
          />
        </div>
      </div>

      <div className="grid grid-cols-3 gap-6">
        {courses.map(course => {
          const group = course.groups?.[0]; // Taking the first group for demo
          if (!group) return null;
          
          const isFull = group.current_count >= group.max_capacity;
          const availableSeats = group.max_capacity - group.current_count;

          return (
            <div key={course._id} className="glass-panel" style={{ padding: '0', display: 'flex', flexDirection: 'column', overflow: 'hidden', position: 'relative' }}>
              <div style={{ padding: '1.5rem', flex: 1 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1rem' }}>
                  <span className={`badge ${isFull ? 'badge-danger' : 'badge-primary'}`}>
                    {isFull ? 'LLeno' : `${availableSeats} Lugares Quedan`}
                  </span>
                  <span style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--success)' }}>
                    ${group.priceBreakdown?.currentPrice || 120}
                  </span>
                </div>
                <h3 style={{ fontSize: '1.3rem', marginBottom: '0.5rem' }}>{course.name}</h3>
                <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '1rem', display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>
                  {course.description}
                </p>
                
                <div style={{ display: 'flex', gap: '1rem', color: 'var(--text-muted)', fontSize: '0.85rem' }}>
                  <span style={{ display: 'flex', alignItems: 'center', gap: '0.3rem' }}><Users size={14}/> Prof. {course.teacher?.name}</span>
                </div>
              </div>
              
              <button 
                className={`btn ${isFull ? 'btn-outline' : 'btn-primary'}`} 
                style={{ margin: '1.5rem', marginTop: '0', padding: '1rem', borderRadius: 'var(--radius-sm)' }}
                disabled={isFull}
                onClick={() => navigate(`/student/checkout/${group._id}`)}
              >
                {isFull ? 'Agotado' : 'Asegurar mi lugar'} <ArrowRight size={18} />
              </button>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default Catalog;
