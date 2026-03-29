import { useState, useEffect } from 'react';

const DateSelector = ({ onSelectSlot, attractionId }) => {
  const [date, setDate] = useState('');
  const [slots, setSlots] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const today = new Date().toISOString().split('T')[0];

  useEffect(() => {
    if (date && attractionId) {
      fetchSlots(date);
    }
  }, [date, attractionId]);

  const fetchSlots = async (selectedDate) => {
    setLoading(true);
    setError('');
    try {
      const response = await fetch(`/api/api_bookings.php?action=get_availability&date=${selectedDate}&attraction_id=${attractionId}`);
      const data = await response.json();
      if (data.success) {
        setSlots(data.slots);
      } else {
        setError(data.message);
      }
    } catch (err) {
      setError('Could not connect to the booking server.');
    } finally {
      setLoading(false);
    }
  };

  const [selectedSlotId, setSelectedSlotId] = useState(null);

  const handleSlotClick = (slot) => {
    if (slot.available_capacity > 0) {
      setSelectedSlotId(slot.id);
      onSelectSlot(slot, date);
    }
  };

  return (
    <div className="card">
      <h2 className="card-title">Select Date & Time</h2>
      <div className="form-group">
        <label htmlFor="visit-date">Visit Date</label>
        <input 
          type="date" 
          id="visit-date" 
          min={today} 
          value={date} 
          onChange={(e) => setDate(e.target.value)} 
          required 
        />
      </div>
      
      <label>Available Slots</label>
      <div className="slot-grid">
        {!date && <div className="slot-item disabled">Please select a date</div>}
        {loading && <div className="slot-item">Loading slots...</div>}
        {error && <div className="slot-item disabled">Error: {error}</div>}
        {!loading && !error && slots.map(slot => (
          <div 
            key={slot.id} 
            className={`slot-item ${selectedSlotId === slot.id ? 'selected' : ''} ${slot.available_capacity <= 0 ? 'disabled' : ''}`}
            onClick={() => handleSlotClick(slot)}
          >
            <div style={{ fontWeight: 600 }}>{slot.slot_name}</div>
            <div style={{ fontSize: '0.8rem' }}>{slot.start_time.substring(0, 5)} - {slot.end_time.substring(0, 5)}</div>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>{slot.available_capacity} left</div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default DateSelector;
