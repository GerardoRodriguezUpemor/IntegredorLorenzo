import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { ArrowLeft, Clock, Calendar, CheckCircle2, AlertTriangle } from 'lucide-react';

const Checkout = () => {
  const { groupId } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  
  const [loading, setLoading] = useState(true);
  const [group, setGroup] = useState(null);
  const [selectedDate, setSelectedDate] = useState(null);
  const [timeLeft, setTimeLeft] = useState(5 * 60); // 5 minutes in seconds
  const [exchangeRate, setExchangeRate] = useState(0.05); // Fallback: 1 MXN = 0.05 USD
  const [reserving, setReserving] = useState(false);
  const [reserved, setReserved] = useState(false);

  useEffect(() => {
    const fetchDetails = async () => {
      try {
        // Fetch pricing
        const reqPricing = fetch(`http://127.0.0.1:8000/api/v1/groups/${groupId}/pricing`, { headers: { 'Accept': 'application/json', 'X-User-Id': user._id }});
        // Fetch schedule options
        const reqSchedule = fetch(`http://127.0.0.1:8000/api/v1/groups/${groupId}/schedule`, { headers: { 'Accept': 'application/json', 'X-User-Id': user._id }});
        // Fetch exchange rate (API 2)
        const reqRate = fetch(`http://127.0.0.1:8000/api/v1/exchange-rate`, { headers: { 'Accept': 'application/json', 'X-User-Id': user._id }});

        const [resPricing, resSchedule, resRate] = await Promise.all([reqPricing, reqSchedule, reqRate]);

        if (resPricing.ok && resSchedule.ok) {
          const jsonPricing = await resPricing.json();
          const jsonSchedule = await resSchedule.json();
          
          if (resRate.ok) {
            const jsonRate = await resRate.json();
            setExchangeRate(jsonRate.rate);
          }

          setGroup({
            _id: groupId,
            name: jsonPricing.data.group.course,
            teacher: jsonPricing.data.group.teacher,
            priceBreakdown: { 
              currentPrice: jsonPricing.data.pricing ? jsonPricing.data.pricing.current_price : null, 
              nextPrice: jsonPricing.data.pricing ? jsonPricing.data.pricing.next_price : null 
            },
            availableDateOptions: jsonSchedule.data.options.map(opt => ({
              _id: opt.id,
              date: opt.proposed_date,
              label: new Date(opt.proposed_date).toLocaleString()
            }))
          });
        }
    } catch { console.error("Fetch Checkout Data Error"); } finally {
        setLoading(false);
      }
    };
    fetchDetails();
  }, [groupId, user]);

  useEffect(() => {
    if (!reserved) return;
    if (timeLeft <= 0) {
      alert("Tu tiempo de pago ha expirado. Lugar liberado.");
      navigate('/client/services');
      return;
    }
    const timer = setInterval(() => setTimeLeft(prev => prev - 1), 1000);
    return () => clearInterval(timer);
  }, [reserved, timeLeft, navigate]);

  const [reservationId, setReservationId] = useState(null);

  const handleReserve = async () => {
    if (!selectedDate) {
      alert('Por favor selecciona un horario antes de continuar');
      return;
    }

    setReserving(true);
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/groups/${groupId}/reserve`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-User-Id': user._id },
        body: JSON.stringify({ schedule_option_id: selectedDate })
      });
      const data = await res.json();
      
      if (res.ok) {
        setReservationId(data.data.reservation_id);
        
        // Find TTL from expires_at if preferred, currently fallback to local 5 min
        if (data.data.expires_at) {
           const expiresDiff = new Date(data.data.expires_at).getTime() - Date.now();
           if (expiresDiff > 0) setTimeLeft(Math.floor(expiresDiff / 1000));
        }

        setReserved(true);
      } else {
        alert(data.message || 'Error al reservar el asiento.');
      }
    } catch {
      alert('Error de conexión');
    } finally {
      setReserving(false);
    }
  };

  const handlePay = async () => {
    if (!reservationId) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/v1/reservations/${reservationId}/confirm`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-User-Id': user._id }
      });
      const data = await res.json();
      if (res.ok) {
        alert('¡Pago Confirmado Exitosamente! Ya estás inscrito.');
        navigate('/client/my-services');
      } else {
        alert(data.message || 'El pago no pudo procesarse');
      }
    } catch {
      alert('Error confirmando el pago');
    }
  };

  const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  };

  if (loading) {
    return <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}><div className="spinner"></div></div>;
  }

  return (
    <div className="fade-in" style={{ maxWidth: '900px', margin: '0 auto' }}>
      <button onClick={() => navigate('/client/services')} className="btn btn-outline" style={{ border: 'none', padding: 0, marginBottom: '2rem', color: 'var(--text-muted)' }}>
        <ArrowLeft size={20} /> Volver al catálogo
      </button>

      <div className="grid grid-cols-2 gap-6">
        {/* Left Column: Details & Voting */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <h1 style={{ fontSize: '1.8rem', marginBottom: '0.5rem' }}>{group.name}</h1>
            <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Proveedor: {group.teacher}</p>

            <div style={{ background: 'var(--primary-glow)', padding: '1rem', borderRadius: 'var(--radius-sm)', border: '1px solid var(--primary)', marginBottom: '2rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <p style={{ fontSize: '0.9rem', color: '#ccc' }}>Precio Reservado</p>
                <h2 style={{ fontSize: '2rem', color: 'white', margin: 0 }}>
                  ${group.priceBreakdown.currentPrice} <small style={{ fontSize: '1rem', color: 'var(--success)', opacity: 0.8 }}>MXN</small>
                </h2>
                <div style={{ marginTop: '0.5rem', padding: '0.4rem 0.8rem', background: 'rgba(255,255,255,0.1)', borderRadius: 'var(--radius-sm)', display: 'inline-block', fontSize: '0.85rem' }}>
                  ≈ ${(group.priceBreakdown.currentPrice * exchangeRate).toFixed(2)} USD
                </div>
              </div>
              <div style={{ textAlign: 'right' }}>
                {/* Funcionalidad dinámica oculta por ahora */}
                {/* <p style={{ fontSize: '0.8rem', color: '#ccc' }}>Si no reservas ahora, el siguiente inscrito pagará:</p>
                <span style={{ fontSize: '1.2rem', color: 'var(--danger)', textDecoration: 'line-through' }}>${group.priceBreakdown.nextPrice}</span> */}
              </div>
            </div>

            <h3 style={{ fontSize: '1.2rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <Calendar size={20} /> Selecciona tu Voto de Horario
            </h3>
            
            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.8rem' }}>
              {group.availableDateOptions.map(opt => (
                <label 
                  key={opt._id} 
                  style={{ 
                    display: 'flex', alignItems: 'center', gap: '1rem', padding: '1rem', 
                    border: `1px solid ${selectedDate === opt._id ? 'var(--primary)' : 'var(--border-glass)'}`, 
                    borderRadius: 'var(--radius-sm)', cursor: 'pointer',
                    background: selectedDate === opt._id ? 'var(--bg-card-hover)' : 'transparent',
                    transition: 'var(--transition-fast)'
                  }}
                >
                  <input 
                    type="radio" 
                    name="schedule" 
                    value={opt._id} 
                    disabled={reserved}
                    checked={selectedDate === opt._id}
                    onChange={() => setSelectedDate(opt._id)}
                    style={{ accentColor: 'var(--primary)', width: '18px', height: '18px' }}
                  />
                  <span>{opt.label}</span>
                </label>
              ))}
            </div>
          </div>
        </div>

        {/* Right Column: Reservation / Checkout */}
        <div>
          <div className="glass-panel" style={{ padding: '2rem', position: 'sticky', top: '100px' }}>
            {!reserved ? (
              <>
                <h3 style={{ fontSize: '1.3rem', marginBottom: '1.5rem' }}>Resumen</h3>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem', color: 'var(--text-muted)' }}>
                  <span>Servicio</span>
                  <span>{group.name}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2rem', color: 'var(--text-muted)' }}>
                  <span>Lugar</span>
                  <span>1 Asiento (Máximo 5)</span>
                </div>
                
                <hr style={{ border: 'none', borderTop: '1px solid var(--border-glass)', marginBottom: '1.5rem' }} />

                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2rem', fontSize: '1.2rem', fontWeight: 'bold' }}>
                  <span>Total</span>
                  <span style={{ color: 'var(--success)' }}>${group.priceBreakdown.currentPrice}</span>
                </div>

                <div style={{ background: 'rgba(255, 165, 2, 0.1)', border: '1px solid var(--warning)', padding: '1rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.5rem', display: 'flex', gap: '0.5rem', color: 'var(--warning)', fontSize: '0.85rem' }}>
                  <AlertTriangle size={32} />
                  <p>Al reservar, bloquearás este asiento durante 5 minutos para procesar el pago.</p>
                </div>

                <button 
                  onClick={handleReserve}
                  disabled={reserving || !selectedDate}
                  className="btn btn-primary" 
                  style={{ width: '100%', padding: '1.2rem', fontSize: '1.1rem' }}
                >
                  {reserving ? 'Reservando lugar atómicamente...' : 'Reservar y Votar'}
                </button>
              </>
            ) : (
              <div className="fade-in">
                <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
                  <div style={{ display: 'inline-flex', padding: '1rem', background: 'rgba(46, 213, 115, 0.2)', borderRadius: '50%', marginBottom: '1rem' }}>
                    <CheckCircle2 size={48} color="var(--success)" />
                  </div>
                  <h3 style={{ fontSize: '1.5rem', color: 'var(--success)' }}>¡Silla Reservada!</h3>
                  <p style={{ color: 'var(--text-muted)' }}>Tu voto ha sido registrado.</p>
                </div>

                <div style={{ background: 'var(--bg-dark)', padding: '1.5rem', borderRadius: 'var(--radius-md)', textAlign: 'center', marginBottom: '2rem', border: '1px solid var(--danger)' }}>
                  <p style={{ color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Tiempo restante para pagar:</p>
                  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '0.5rem', color: 'var(--danger)', fontSize: '2.5rem', fontWeight: 800, fontFamily: 'monospace' }}>
                    <Clock size={32} />
                    {formatTime(timeLeft)}
                  </div>
                </div>

                {/* Fake Credit Card Form */}
                <div style={{ background: 'rgba(0,0,0,0.2)', padding: '1.5rem', borderRadius: 'var(--radius-sm)', marginBottom: '1.5rem', border: '1px solid var(--border-glass)' }}>
                  <p style={{ marginBottom: '1rem', fontWeight: 600 }}>💳 Tarjeta de Crédito Segura</p>
                  <input type="text" className="form-input" placeholder="0000 0000 0000 0000" style={{ marginBottom: '0.5rem' }} value="4111 1111 1111 1111" readOnly />
                  <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <input type="text" className="form-input" placeholder="MM/YY" value="12/28" readOnly />
                    <input type="text" className="form-input" placeholder="CVC" value="123" readOnly />
                  </div>
                </div>

                <button 
                  onClick={handlePay}
                  className="btn btn-primary" 
                  style={{ width: '100%', padding: '1.2rem', fontSize: '1.1rem', background: 'var(--success)' }}
                >
                  Confirmar Pago de ${group.priceBreakdown.currentPrice}
                </button>
              </div>
            )}
          </div>
        </div>

      </div>
    </div>
  );
};

export default Checkout;
