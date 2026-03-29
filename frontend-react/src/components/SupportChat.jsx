import { useState } from 'react';

const SupportChat = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    { text: "Hello! How can I help you today?", isBot: true }
  ]);

  const faqs = [
    { q: "Is it open today?", a: "Yes, Enchanted Gardens is open from 9 AM to 9 PM daily!" },
    { q: "What is the pet policy?", a: "Service animals are welcome. Other pets are not allowed inside the botanical area." },
    { q: "Can I get a refund?", a: "Refunds are available up to 24 hours before your visit date. Contact support for assistance." }
  ];

  const handleFaqClick = (faq) => {
    setMessages([...messages, { text: faq.q, isBot: false }, { text: faq.a, isBot: true }]);
  };

  return (
    <div style={{ position: 'fixed', bottom: '2rem', right: '2rem', zIndex: 2000 }}>
      {isOpen ? (
        <div className="card" style={{ width: '300px', height: '400px', display: 'flex', flexDirection: 'column', boxShadow: '0 10px 25px rgba(0,0,0,0.2)', padding: '0' }}>
          <div style={{ background: 'var(--primary)', color: 'white', padding: '1rem', borderTopLeftRadius: '0.5rem', borderTopRightRadius: '0.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontWeight: 600 }}>Garden Assistant</span>
            <button onClick={() => setIsOpen(false)} style={{ background: 'none', border: 'none', color: 'white', cursor: 'pointer' }}>×</button>
          </div>
          <div style={{ flex: 1, padding: '1rem', overflowY: 'auto' }}>
            {messages.map((m, i) => (
              <div key={i} style={{ 
                marginBottom: '1rem', 
                textAlign: m.isBot ? 'left' : 'right',
                backgroundColor: m.isBot ? '#f3f4f6' : 'var(--primary)',
                color: m.isBot ? 'var(--secondary)' : 'white',
                padding: '0.75rem',
                borderRadius: '0.5rem',
                fontSize: '0.9rem',
                maxWidth: '85%',
                marginLeft: m.isBot ? '0' : 'auto'
              }}>
                {m.text}
              </div>
            ))}
          </div>
          <div style={{ padding: '0.75rem', borderTop: '1px solid var(--border)', background: '#f9fafb' }}>
            <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>Frequently Asked:</p>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
              {faqs.map((f, i) => (
                <button 
                  key={i} 
                  onClick={() => handleFaqClick(f)} 
                  style={{ fontSize: '0.7rem', padding: '4px 8px', borderRadius: '12px', border: '1px solid var(--primary)', background: 'white', color: 'var(--primary)', cursor: 'pointer' }}
                >
                  {f.q}
                </button>
              ))}
            </div>
          </div>
        </div>
      ) : (
        <button 
          onClick={() => setIsOpen(true)} 
          style={{ width: '60px', height: '60px', borderRadius: '30px', background: 'var(--primary)', color: 'white', border: 'none', boxShadow: '0 5px 15px rgba(37,99,235,0.4)', cursor: 'pointer', fontSize: '1.5rem' }}
        >
          <i className="fa-solid fa-comments"></i>
        </button>
      )}
    </div>
  );
};

export default SupportChat;
