import { useEffect, useRef } from 'react';

const PayPalButton = ({ amount, onSuccess, onError, disabled }) => {
  const paypalRef = useRef();

  useEffect(() => {
    if (window.paypal && !disabled) {
      window.paypal.Buttons({
        style: {
          layout: 'vertical',
          color:  'blue',
          shape:  'rect',
          label:  'checkout'
        },
        createOrder: (data, actions) => {
          return actions.order.create({
            purchase_units: [{
              description: "Enchanted Gardens Booking",
              amount: {
                currency_code: "USD",
                value: amount
              }
            }]
          });
        },
        onApprove: async (data, actions) => {
          const order = await actions.order.capture();
          onSuccess(order.id);
        },
        onError: (err) => {
          console.error('PayPal Error:', err);
          onError && onError(err);
        }
      }).render(paypalRef.current);
    }
    
    // Cleanup if component unmounts or re-renders
    return () => {
      if (paypalRef.current) {
        paypalRef.current.innerHTML = '';
      }
    };
  }, [amount, disabled]);

  return (
    <div ref={paypalRef} style={{ marginTop: '1rem', minHeight: '150px' }}>
      {disabled && <p style={{ color: 'var(--text-muted)', fontSize: '0.8rem', textAlign: 'center' }}>Please complete your guest details above to enable payment.</p>}
    </div>
  );
};

export default PayPalButton;
