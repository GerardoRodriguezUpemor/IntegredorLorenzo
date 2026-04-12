import { Link } from 'react-router-dom';
import { BookOpen, Users, Rocket, Zap } from 'lucide-react';

const Landing = () => {
  return (
    <>
      <header className="navbar">
        <div className="nav-brand">
          <BookOpen className="highlight" />
          <span>Micro<span className="highlight">Cohorts</span></span>
        </div>
        <div className="nav-links">
          <Link to="/login" className="btn btn-outline" style={{ padding: '0.5rem 1.2rem'}}>Entrar</Link>
          <Link to="/register" className="btn btn-primary" style={{ padding: '0.5rem 1.2rem'}}>Crear Cuenta</Link>
        </div>
      </header>

      <main className="main-content fade-in" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', flex: 1, textAlign: 'center', paddingTop: '10vh' }}>
        <h1 className="title" style={{ fontSize: '4rem', marginBottom: '1.5rem', lineHeight: 1.1 }}>
          El primer ecosistema de <br/>
          <span style={{ color: 'var(--primary)' }}>Súper Grupos Educativos</span>
        </h1>
        <p style={{ fontSize: '1.2rem', color: 'var(--text-muted)', maxWidth: '600px', marginBottom: '3rem' }}>
          Únete a micro-clases exclusivas. Reserva temprano y asegura el precio base utilizando nuestro motor dinámico.
        </p>
        
        <div style={{ display: 'flex', gap: '1rem' }}>
          <Link to="/register" className="btn btn-primary" style={{ fontSize: '1.1rem', padding: '1rem 2rem' }}>
            <Rocket size={20} /> Empezar a Aprender
          </Link>
          <Link to="/register?role=TEACHER" className="btn btn-outline" style={{ fontSize: '1.1rem', padding: '1rem 2rem' }}>
            <Users size={20} /> Soy Profesor
          </Link>
        </div>

        <div className="grid grid-cols-3 gap-6" style={{ marginTop: '5rem', width: '100%', maxWidth: '1000px', textAlign: 'left' }}>
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <Zap size={32} color="var(--secondary)" style={{ marginBottom: '1rem' }} />
            <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>Precio Dinámico</h3>
            <p style={{ color: 'var(--text-muted)' }}>Mismo grupo, diferentes precios. Reserva primero y ahorra hasta un 80% del valor total.</p>
          </div>
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <Users size={32} color="var(--primary)" style={{ marginBottom: '1rem' }} />
            <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>Micro Cohortes</h3>
            <p style={{ color: 'var(--text-muted)' }}>Topes estrictos de 5 alumnos. Para garantizar educación VIP sin perder rentabilidad.</p>
          </div>
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <BookOpen size={32} color="var(--success)" style={{ marginBottom: '1rem' }} />
            <h3 style={{ fontSize: '1.2rem', marginBottom: '0.5rem' }}>Decisión Colegiada</h3>
            <p style={{ color: 'var(--text-muted)' }}>El profesor propone, ustedes eligen. Vota la fecha de tu clase en tiempo real al confirmar.</p>
          </div>
        </div>
      </main>
    </>
  );
};

export default Landing;
