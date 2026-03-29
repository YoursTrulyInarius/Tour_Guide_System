const CheckoutForm = ({ visitor, onChange }) => {
  return (
    <div className="card">
      <h2 className="card-title">Guest Details</h2>
      <div className="form-group">
        <label htmlFor="guest-name">Full Name</label>
        <input 
          type="text" 
          id="guest-name" 
          placeholder="John Doe" 
          value={visitor.name || ''} 
          onChange={(e) => onChange('name', e.target.value)} 
          required 
        />
      </div>
      <div className="form-group">
        <label htmlFor="guest-email">Email Address</label>
        <input 
          type="email" 
          id="guest-email" 
          placeholder="john@example.com" 
          value={visitor.email || ''} 
          onChange={(e) => onChange('email', e.target.value)} 
          required 
        />
      </div>
      <div className="form-group">
        <label htmlFor="guest-phone">Phone Number</label>
        <input 
          type="tel" 
          id="guest-phone" 
          placeholder="09171234567" 
          value={visitor.phone || ''} 
          onChange={(e) => {
            const val = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (val.length <= 11) onChange('phone', val);
          }} 
        />
      </div>

      {/* Honeypot Field for Spam Prevention */}
      <div style={{ display: 'none' }} aria-hidden="true">
        <label htmlFor="website_url">Website URL</label>
        <input 
          type="text" 
          id="website_url" 
          name="website_url" 
          tabIndex="-1" 
          autoComplete="off" 
          value={visitor.website_url || ''}
          onChange={(e) => onChange('website_url', e.target.value)}
        />
      </div>
    </div>
  );
};

export default CheckoutForm;
