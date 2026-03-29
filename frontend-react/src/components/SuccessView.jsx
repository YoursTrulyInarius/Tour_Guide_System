import { useState, useEffect } from 'react';

const SuccessView = ({ bookingId, onReset, attractionName }) => {
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (bookingId) {
      fetchBooking();
    }
  }, [bookingId]);

  const fetchBooking = async () => {
    try {
      const response = await fetch(`/api/api_bookings.php?action=get_booking&id=${bookingId}`);
      const data = await response.json();
      if (data.success) {
        setBooking(data.booking);
      } else {
        setError(data.message);
      }
    } catch (err) {
      setError('Error loading booking details.');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="card">Loading your tickets...</div>;
  if (error) return <div className="card">Error: {error}</div>;

  return (
    <div className="container animate-fade-in" style={{ maxWidth: '700px', margin: '6rem auto', textAlign: 'center' }}>
      <div style={{ color: 'var(--primary)', fontSize: '5rem', marginBottom: '2rem' }}>
        <i className="fa-solid fa-circle-check"></i>
      </div>
      <h1 style={{ fontSize: '3rem', marginBottom: '1rem' }}>Booking Confirmed!</h1>
      <p style={{ color: 'var(--text-muted)', fontSize: '1.25rem' }}>Your tickets have been sent to your email.</p>

      <div style={{ marginTop: '2rem', padding: '1rem', borderRadius: '1rem', backgroundColor: 'var(--white)', border: '1px solid var(--border)', display: 'inline-block' }}>
        <span style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>Booking Reference: </span>
        <span style={{ fontFamily: 'monospace', fontSize: '1.1rem', color: 'var(--secondary)', fontWeight: 800 }}>{booking.id}</span>
      </div>

      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
        gap: '2rem',
        marginTop: '4rem'
      }}>
        {booking.tickets.map(ticket => (
          <div key={ticket.id} className="card" style={{ padding: '0', overflow: 'hidden', position: 'relative' }}>
            <div style={{ height: '0.5rem', backgroundColor: 'var(--accent)' }}></div>
            <div style={{ padding: '2rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' }}>
                <span style={{ fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--text-muted)', fontSize: '0.8rem' }}>{ticket.type_name}</span>
                <span style={{ color: 'var(--primary)', fontWeight: 700 }}>#{ticket.id}</span>
              </div>
              
              <div style={{ background: '#f8fafc', padding: '2rem', borderRadius: '1rem', marginBottom: '1.5rem', display: 'flex', alignItems: 'center', justifyContent: 'center', border: '2px dashed var(--border)' }}>
                <i className="fa-solid fa-qrcode" style={{ fontSize: '6rem', color: 'var(--secondary)', opacity: 0.8 }}></i>
              </div>

              <div style={{ textAlign: 'left', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px solid var(--border)', paddingBottom: '0.5rem' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Destination</span>
                  <span style={{ fontWeight: 700 }}>{attractionName}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', borderBottom: '1px solid var(--border)', paddingBottom: '0.5rem' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Date</span>
                  <span style={{ fontWeight: 700 }}>{booking.visit_date}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span style={{ color: 'var(--text-muted)' }}>Time Slot</span>
                  <span style={{ fontWeight: 700 }}>{booking.slot_name}</span>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div style={{ marginTop: '5rem' }}>
        <button onClick={onReset} className="btn btn-primary" style={{ width: 'auto', padding: '1rem 3rem', fontSize: '1.1rem' }}>
          Explore More Destinations
          <i className="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>
  );
};

export default SuccessView;
