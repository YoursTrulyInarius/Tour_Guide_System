import { useState, useEffect } from 'react';

const AddonSelector = ({ onAddonChange, attractionId }) => {
  const [addons, setAddons] = useState([]);
  const [quantities, setQuantities] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (attractionId) {
      fetchAddons();
    }
  }, [attractionId]);

  const fetchAddons = async () => {
    setLoading(true);
    try {
      const response = await fetch(`/api/api_bookings.php?action=get_addons&attraction_id=${attractionId}`);
      const data = await response.json();
      if (data.success) {
        setAddons(data.addons);
      }
    } catch (err) {
      console.error('Error fetching addons:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleQtyChange = (id, change) => {
    const newQty = Math.max(0, (quantities[id] || 0) + change);
    const newQuantities = { ...quantities, [id]: newQty };
    setQuantities(newQuantities);
    
    // Convert to payload format [{id, quantity}, ...]
    const payload = Object.entries(newQuantities)
      .filter(([_, qty]) => qty > 0)
      .map(([id, qty]) => ({ id: parseInt(id), quantity: qty }));
    
    onAddonChange(payload);
  };

  if (loading) return <div className="card">Loading enhancements...</div>;

  return (
    <div className="card">
      <h2 className="card-title">Enhance Your Experience</h2>
      <div className="addon-list">
        {addons.map(addon => (
          <div key={addon.id} className="ticket-type-item">
            <div>
              <h3 style={{ fontSize: '1rem' }}>{addon.name}</h3>
              <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem' }}>{addon.description} • +${addon.price}</p>
            </div>
            <div className="stepper">
              <button type="button" onClick={() => handleQtyChange(addon.id, -1)} disabled={!quantities[addon.id]}>-</button>
              <span>{quantities[addon.id] || 0}</span>
              <button type="button" onClick={() => handleQtyChange(addon.id, 1)}>+</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default AddonSelector;
