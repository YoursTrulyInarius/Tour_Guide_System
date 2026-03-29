import { useState, useEffect } from 'react';

const LandingPage = ({ onSelectSpot }) => {
  const [attractions, setAttractions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch('/api/api_bookings.php?action=get_all_attractions')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setAttractions(data.attractions);
        }
        setLoading(false);
      })
      .catch(err => {
        console.error('Error fetching attractions:', err);
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <div className="container" style={{ textAlign: 'center', padding: '10rem 0' }}>
        <div className="spinner"></div>
        <p>Discovering destinations...</p>
      </div>
    );
  }

  return (
    <div className="landing-page">
      <section className="hero-landing animate-fade-in">
        <div className="container" style={{ textAlign: 'center' }}>
          <h1 style={{ fontSize: '4.5rem', color: 'white', marginBottom: '1.5rem', fontWeight: 800 }}>Explore Your Next Adventure</h1>
          <p style={{ fontSize: '1.5rem', color: 'rgba(255,255,255,0.9)', maxWidth: '750px', margin: '0 auto', lineHeight: 1.4 }}>
            Discover breathtaking tourist spots and book your experience in under 60 seconds.
          </p>
        </div>
      </section>

      <div className="container" style={{ marginTop: '-2rem', position: 'relative', zIndex: 10 }}>
        <div className="section-header" style={{ marginBottom: '3rem' }}>
          <h2 style={{ fontSize: '2.5rem' }}>Top Rated Spots</h2>
        </div>
        
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))',
          gap: '2.5rem',
          marginBottom: '8rem'
        }}>
          {attractions.map(spot => (
            <div key={spot.id} className="card spot-card animate-fade-in" style={{ padding: '0' }}>
              <div style={{ 
                height: '280px', 
                overflow: 'hidden',
                position: 'relative'
              }}>
                <img 
                  src={spot.image_url} 
                  alt={spot.name}
                  style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                />
                <div style={{
                  position: 'absolute',
                  top: '1.25rem',
                  left: '1.25rem',
                  background: 'rgba(15, 23, 42, 0.7)',
                  backdropFilter: 'blur(8px)',
                  color: 'white',
                  padding: '0.4rem 1rem',
                  borderRadius: '2rem',
                  fontSize: '0.8rem',
                  fontWeight: 600,
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem'
                }}>
                  <i className="fa-solid fa-location-dot"></i> {spot.location}
                </div>
              </div>
              <div style={{ padding: '2rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                <h3 style={{ fontSize: '1.75rem', margin: 0 }}>{spot.name}</h3>
                <p style={{ color: 'var(--text-muted)', fontSize: '1rem', flex: 1, margin: 0 }}>
                  {spot.description}
                </p>
                <button 
                  className="btn btn-primary" 
                  onClick={() => onSelectSpot(spot)}
                  style={{ marginTop: '1rem' }}
                >
                  Book Experience
                  <i className="fa-solid fa-chevron-right" style={{ fontSize: '0.8rem' }}></i>
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default LandingPage;
