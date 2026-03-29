import { useState, useEffect } from 'react';

const AdminDashboard = ({ onLogout }) => {
  const [qrInput, setQrInput] = useState('');
  const [validationResult, setValidationResult] = useState(null);
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({ morning: 0, afternoon: 0, total: 0, checkedIn: 0 });

  useEffect(() => {
    fetchBookings();
  }, []);

  const fetchBookings = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/api_bookings.php?action=get_all_bookings');
      const data = await response.json();
      if (data.success) {
        setBookings(data.bookings);
        calculateStats(data.bookings);
      }
    } catch (err) {
      console.error('Error fetching bookings:', err);
    } finally {
      setLoading(false);
    }
  };

  const calculateStats = (allBookings) => {
    // Simplified stat calculation for the demo
    const today = new Date().toISOString().split('T')[0];
    const todayBookings = allBookings.filter(b => b.visit_date === today);
    
    let morning = 0, afternoon = 0, checkedIn = 0;
    todayBookings.forEach(b => {
      if (b.status === 'paid') {
        if (b.slot_name === 'Morning') morning++;
        else afternoon++;
      }
    });

    setStats({ 
      morning, 
      afternoon, 
      total: todayBookings.length,
      checkedIn: 0 // In a real app, this would be based on scanned tickets
    });
  };

  const handleValidate = async () => {
    if (!qrInput) return;
    
    try {
      const response = await fetch(`/api/api_bookings.php?action=validate&qr=${qrInput}`);
      const data = await response.json();
      setValidationResult({
        success: data.success,
        message: data.message,
        visitor: data.visitor || null,
        type: data.type || null,
        timestamp: new Date().toLocaleTimeString()
      });
      if (data.success) {
        setQrInput('');
        fetchBookings(); // Refresh to update check-in stats if needed
      }
    } catch (err) {
      setValidationResult({ success: false, message: 'Connection Error', timestamp: new Date().toLocaleTimeString() });
    }
  };

  const handleRefund = async (bookingId) => {
    if (!confirm('Are you sure you want to refund this booking?')) return;
    
    try {
      const response = await fetch('/api/api_bookings.php?action=refund', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId })
      });
      const data = await response.json();
      if (data.success) {
        alert('Refund successful');
        fetchBookings();
      } else {
        alert('Refund failed: ' + data.message);
      }
    } catch (err) {
      alert('Error processing refund');
    }
  };

  const renderCalendar = () => {
    const today = new Date();
    const month = today.getMonth();
    const year = today.getFullYear();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();

    const days = [];
    for (let i = 0; i < firstDay; i++) days.push(null);
    for (let i = 1; i <= daysInMonth; i++) days.push(i);

    const getDayStatus = (day) => {
      if (!day) return 'empty';
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const count = bookings.filter(b => b.visit_date === dateStr && b.status === 'paid').length;
      if (count === 0) return 'none';
      if (count >= 1000) return 'soldout'; // Red
      if (count >= 700) return 'busy'; // Orange
      return 'available'; // Green
    };

    return (
      <div className="card" style={{ marginTop: '2rem' }}>
        <h2 className="card-title"><i className="fa-solid fa-calendar-days"></i> Monthly Capacity Overview</h2>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: '0.5rem', textAlign: 'center' }}>
          {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(d => (
            <div key={d} style={{ fontWeight: 'bold', fontSize: '0.8rem', opacity: 0.6 }}>{d}</div>
          ))}
          {days.map((day, i) => {
            const status = getDayStatus(day);
            const colors = {
              empty: 'transparent',
              none: '#f3f4f6',
              available: '#dcfce7', // Green
              busy: '#ffedd5',      // Orange
              soldout: '#fee2e2'    // Red
            };
            const textColors = {
              available: '#166534',
              busy: '#9a3412',
              soldout: '#991b1b'
            };

            return (
              <div key={i} style={{ 
                height: '60px', 
                background: colors[status], 
                borderRadius: '4px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '0.9rem',
                fontWeight: 600,
                color: textColors[status] || 'inherit',
                border: status === 'empty' ? 'none' : '1px solid var(--border)'
              }}>
                {day}
              </div>
            );
          })}
        </div>
        <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem', fontSize: '0.75rem', justifyContent: 'center' }}>
          <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}><div style={{ width: '12px', height: '12px', background: '#dcfce7', borderRadius: '2px' }}></div> Low</span>
          <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}><div style={{ width: '12px', height: '12px', background: '#ffedd5', borderRadius: '2px' }}></div> Busy (70%+)</span>
          <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}><div style={{ width: '12px', height: '12px', background: '#fee2e2', borderRadius: '2px' }}></div> Sold Out</span>
        </div>
      </div>
    );
  };

  return (
    <div className="container" style={{ padding: '2rem 1rem' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Staff Dashboard</h1>
        <button onClick={onLogout} className="btn btn-secondary" style={{ width: 'auto' }}>Logout</button>
      </div>

      <div className="booking-container" style={{ gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)', gap: '2rem' }}>
        {/* Render Scanner & Stats Cards... */}
        {/* Scanner Card */}
        <div className="card">
          <h2 className="card-title"><i className="fa-solid fa-qrcode"></i> Entry Validator</h2>
          <div className="form-group" style={{ display: 'flex', gap: '0.5rem' }}>
            <input 
              type="text" 
              placeholder="Scan or enter QR code..." 
              value={qrInput} 
              onChange={(e) => setQrInput(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleValidate()}
            />
            <button className="btn btn-primary" style={{ width: '120px' }} onClick={handleValidate}>Verify</button>
          </div>
          {validationResult && (
            <div style={{ 
              marginTop: '1.5rem', 
              padding: '1.5rem', 
              borderRadius: '0.5rem',
              backgroundColor: validationResult.success ? '#ecfdf5' : '#fef2f2',
              border: `2px solid ${validationResult.success ? '#10b981' : '#ef4444'}`,
              textAlign: 'center'
            }}>
              <i className={`fa-solid ${validationResult.success ? 'fa-circle-check' : 'fa-circle-xmark'}`} style={{ fontSize: '2rem', color: validationResult.success ? '#10b981' : '#ef4444' }}></i>
              <h3 style={{ margin: '0.5rem 0' }}>{validationResult.success ? 'GRANTED' : 'DENIED'}</h3>
              <p>{validationResult.message}</p>
            </div>
          )}
        </div>

        {/* Stats Card */}
        <div className="card">
          <h2 className="card-title"><i className="fa-solid fa-chart-pie"></i> Today's Live Capacity</h2>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
            <div>Morning: {stats.morning} / 500</div>
            <div style={{ height: '8px', background: '#e5e7eb', borderRadius: '4px' }}><div style={{ width: `${(stats.morning/500)*100}%`, height: '100%', background: 'var(--primary)', borderRadius: '4px' }}></div></div>
            <div>Afternoon: {stats.afternoon} / 500</div>
            <div style={{ height: '8px', background: '#e5e7eb', borderRadius: '4px' }}><div style={{ width: `${(stats.afternoon/500)*100}%`, height: '100%', background: 'var(--complementary)', borderRadius: '4px' }}></div></div>
          </div>
        </div>
      </div>

      {renderCalendar()}

      <div className="card" style={{ marginTop: '2rem' }}>
        {/* Order Management Table */}
        <h2 className="card-title">Order Management</h2>
        <div style={{ overflowX: 'auto' }}>
          <table>
            <thead>
              <tr><th>Date</th><th>Name</th><th>Slot</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
              {bookings.map(b => (
                <tr key={b.id}>
                  <td>{b.visit_date}</td>
                  <td>{b.visitor_name}</td>
                  <td>{b.slot_name}</td>
                  <td><span className={`status-badge status-${b.status}`}>{b.status}</span></td>
                  <td>
                    {b.status === 'paid' && <button onClick={() => handleRefund(b.id)} style={{ color: 'red', border: 'none', background: 'none', cursor: 'pointer' }}>Refund</button>}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
