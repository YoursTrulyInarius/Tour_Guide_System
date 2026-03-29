import PayPalButton from './PayPalButton';

const SummarySidebar = ({ 
  selectedSlot, 
  visitDate, 
  quantities, 
  ticketTypes, 
  selectedAddons, 
  visitor, // New prop needed
  onSuccess, // Handler for PayPal success
  isProcessing 
}) => {
  const selectedTickets = ticketTypes.filter(type => (quantities[type.id] || 0) > 0);
  const ticketTotal = selectedTickets.reduce((sum, type) => sum + (type.price * (quantities[type.id] || 0)), 0);
  const addonTotal = (selectedAddons || []).reduce((sum, addon) => sum + (addon.price * addon.quantity), 0);
  const totalAmount = ticketTotal + addonTotal;

  // Validation: Check if name and email are present
  const isFormValid = visitor && visitor.name && visitor.email && visitor.email.includes('@');

  return (
    <aside className="summary-sidebar">
      <div className="card">
        <h2 className="card-title">Order Summary</h2>
        
        {selectedTickets.length === 0 ? (
          <p style={{ color: 'var(--text-muted)', textAlign: 'center', padding: '1rem' }}>
            Select tickets to see summary
          </p>
        ) : (
          <div className="summary-details">
            {/* Tickets & Addons ... */}
            {selectedTickets.map(type => (
              <div key={type.id} className="summary-item">
                <span>{type.name} x {quantities[type.id]}</span>
                <span>${(type.price * quantities[type.id]).toFixed(2)}</span>
              </div>
            ))}
            
            {(selectedAddons || []).map(addon => (
              <div key={addon.id} className="summary-item" style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>
                <span>{addon.name} x {addon.quantity}</span>
                <span>+${(addon.price * addon.quantity).toFixed(2)}</span>
              </div>
            ))}

            <div className="summary-item" style={{ fontSize: '0.85rem', color: 'var(--text-muted)', borderTop: '1px dashed var(--border)', paddingTop: '0.5rem', marginTop: '0.5rem' }}>
              <span>Taxes & Service Fees</span>
              <span>Included</span>
            </div>
            
            <div className="total-line">
              <span>Total Amount</span>
              <span>${totalAmount.toFixed(2)}</span>
            </div>

            {/* PayPal Button Integration */}
            <PayPalButton 
              amount={totalAmount.toFixed(2)} 
              onSuccess={onSuccess} 
              disabled={!isFormValid || !selectedSlot || !visitDate || isProcessing}
            />

            <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '1rem', textAlign: 'center' }}>
              <i className="fa-solid fa-lock"></i> Secure Payment by PayPal
            </p>
          </div>
        )}
      </div>
    </aside>
  );
};

export default SummarySidebar;
