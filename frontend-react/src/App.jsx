import { useState, useEffect } from 'react'
import './App.css'
import Hero from './components/Hero'
import DateSelector from './components/DateSelector'
import TicketPicker from './components/TicketPicker'
import AddonSelector from './components/AddonSelector'
import SummarySidebar from './components/SummarySidebar'
import CheckoutForm from './components/CheckoutForm'
import SuccessView from './components/SuccessView'
import AdminDashboard from './components/AdminDashboard'
import SupportChat from './components/SupportChat'
import LandingPage from './components/LandingPage'
import Navbar from './components/Navbar'

function App() {
  const [currentSpot, setCurrentSpot] = useState(null); // The selected attraction object
  const [isAdmin, setIsAdmin] = useState(false);
  const [selectedSlot, setSelectedSlot] = useState(null);
  const [visitDate, setVisitDate] = useState('');
  const [quantities, setQuantities] = useState({});
  const [selectedAddons, setSelectedAddons] = useState([]); // Array of {id, quantity}
  const [allAddons, setAllAddons] = useState([]); // For price/name lookup
  const [visitor, setVisitor] = useState({ name: '', email: '', phone: '', website_url: '' });
  const [bookingId, setBookingId] = useState(null);
  const [isProcessing, setIsProcessing] = useState(false);
  const [ticketTypes, setTicketTypes] = useState([]);

  // Fetch addon & ticket type metadata for the current attraction
  useEffect(() => {
    if (currentSpot) {
      // Fetch Addons
      fetch(`/api/api_bookings.php?action=get_addons&attraction_id=${currentSpot.id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) setAllAddons(data.addons);
        });
      
      // Fetch Ticket Types
      fetch(`/api/api_bookings.php?action=get_ticket_types&attraction_id=${currentSpot.id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) setTicketTypes(data.ticket_types);
        });
    }
  }, [currentSpot]);

  const handleQtyChange = (typeId, change) => {
    setQuantities(prev => ({
      ...prev,
      [typeId]: Math.max(0, (prev[typeId] || 0) + change)
    }));
  };

  const handleVisitorChange = (field, value) => {
    setVisitor(prev => ({ ...prev, [field]: value }));
  };

  const handlePayPalSuccess = async (paypalOrderId) => {
    setIsProcessing(true);
    const ticketPayload = Object.entries(quantities)
      .filter(([_, qty]) => qty > 0)
      .map(([id, qty]) => ({ type_id: parseInt(id), quantity: qty }));

    try {
      // 1. Create a reservation first
      const reserveRes = await fetch('/api/api_bookings.php?action=reserve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          date: visitDate,
          time_slot_id: selectedSlot.id,
          visitor_name: visitor.name,
          visitor_email: visitor.email,
          visitor_phone: visitor.phone,
          tickets: ticketPayload,
          addons: selectedAddons
        })
      });
      const reserveData = await reserveRes.json();

      if (reserveData.success) {
        // 2. Confirm the booking with the PayPal Order ID
        const confirmRes = await fetch('/api/api_bookings.php?action=confirm', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            booking_id: reserveData.booking_id,
            paypal_order_id: paypalOrderId 
          })
        });
        const confirmData = await confirmRes.json();

        if (confirmData.success) {
          setBookingId(reserveData.booking_id);
        } else {
          alert('Payment confirmed by PayPal but update failed: ' + confirmData.message);
        }
      } else {
        alert('Reservation failed: ' + reserveData.message);
      }
    } catch (err) {
      alert('An error occurred during finalization.');
    } finally {
      setIsProcessing(false);
    }
  };

  // Adjust prices based on multiplier
  const multiplier = selectedSlot ? selectedSlot.price_multiplier : 1.0;
  const adjustedTicketTypes = ticketTypes.map(t => ({
    ...t,
    price: t.price * multiplier
  }));

  // Build the rich addon state for the sidebar
  const richSelectedAddons = selectedAddons.map(selected => {
    const meta = allAddons.find(a => a.id === selected.id);
    return { ...selected, ...meta };
  });

  const resetBooking = () => {
    setBookingId(null);
    setQuantities({});
    setSelectedAddons([]);
    setSelectedSlot(null);
    setVisitDate('');
    setVisitor({ name: '', email: '', phone: '', website_url: '' });
    setCurrentSpot(null); // Go back to landing page
  };

  if (isAdmin) {
    return <AdminDashboard onLogout={() => setIsAdmin(false)} />;
  }

  if (bookingId) {
    return <SuccessView bookingId={bookingId} onReset={resetBooking} attractionName={currentSpot?.name} />;
  }

  // If no spot is selected, show the Landing Page
  if (!currentSpot) {
    return (
      <div className="App">
        <Navbar 
          onLogoClick={() => setCurrentSpot(null)} 
          onAdminClick={() => setIsAdmin(true)} 
        />
        <LandingPage onSelectSpot={(spot) => setCurrentSpot(spot)} />
        <footer className="footer">
          <div className="container">
            <p>&copy; 2026 YoursTruly Tours. Empowering Discoveries.</p>
          </div>
        </footer>
        <SupportChat />
      </div>
    );
  }

  // Booking Flow for a selected spot
  return (
    <div className="App">
      <Navbar 
        onLogoClick={() => setCurrentSpot(null)} 
        onAdminClick={() => setIsAdmin(true)} 
      />
      
      <Hero 
        title={currentSpot.name}
        subtitle={currentSpot.description}
        imageUrl={currentSpot.image_url}
      />
      
      <main className="container main-content-wrapper">
        <div className="booking-container">
          <div className="booking-steps">
            <div className="section-header">
              <button 
                className="btn btn-secondary btn-sm" 
                onClick={() => {
                  setCurrentSpot(null);
                  setQuantities({});
                  setSelectedAddons([]);
                }}
              >
                <i className="fa-solid fa-arrow-left"></i> Back to Destinations
              </button>
              <h1 className="page-title">Book Your Experience</h1>
            </div>

            <DateSelector onSelectSlot={(slot, date) => {
              setSelectedSlot(slot);
              setVisitDate(date);
            }} attractionId={currentSpot.id} />
            
            <TicketPicker 
              ticketTypes={adjustedTicketTypes} 
              quantities={quantities} 
              onChange={handleQtyChange} 
            />

            <AddonSelector 
              attractionId={currentSpot.id}
              onAddonChange={(addons) => setSelectedAddons(addons)} 
            />
            
            <CheckoutForm 
              visitor={visitor} 
              onChange={handleVisitorChange} 
            />
          </div>

          <SummarySidebar 
            selectedSlot={selectedSlot}
            visitDate={visitDate}
            quantities={quantities}
            ticketTypes={adjustedTicketTypes}
            selectedAddons={richSelectedAddons}
            visitor={visitor}
            onSuccess={handlePayPalSuccess}
            isProcessing={isProcessing}
          />
        </div>
      </main>

      <footer className="footer">
        <div className="container">
          <p>&copy; 2026 YoursTruly Tours. Empowering Discoveries.</p>
        </div>
      </footer>
      <SupportChat />
    </div>
  )
}

export default App
