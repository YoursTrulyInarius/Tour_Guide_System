const TicketPicker = ({ ticketTypes, quantities, onChange }) => {
  return (
    <div className="card">
      <h2 className="card-title">Select Tickets</h2>
      <div className="ticket-type-list">
        {ticketTypes.map(type => (
          <div key={type.id} className="ticket-type-item">
            <div>
              <h3 style={{ fontSize: '1.1rem' }}>{type.name}</h3>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>{type.description} • ${type.price.toFixed(2)}</p>
            </div>
            <div className="stepper">
              <button type="button" onClick={() => onChange(type.id, -1)} disabled={quantities[type.id] <= 0}>-</button>
              <span>{quantities[type.id] || 0}</span>
              <button type="button" onClick={() => onChange(type.id, 1)}>+</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default TicketPicker;
