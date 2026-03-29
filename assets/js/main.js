document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainSidebar = document.getElementById('mainSidebar');
    if(sidebarToggle && mainSidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            mainSidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !mainSidebar.contains(e.target)) {
                mainSidebar.classList.remove('active');
            }
        });
    }

    // Chat Widget Logic
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const chatWindow = document.getElementById('chatWindow');
    
    if (chatToggleBtn && chatWindow) {
        chatToggleBtn.addEventListener('click', () => {
            if (chatWindow.style.display === 'none') {
                chatWindow.style.display = 'flex';
                chatToggleBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            } else {
                chatWindow.style.display = 'none';
                chatToggleBtn.innerHTML = '<i class="fa-regular fa-comment-dots"></i>';
            }
        });

        // Chatbot logic
        const chatInput = document.getElementById('chatInput');
        const chatBody = document.getElementById('chatBody');
        if(chatInput && chatBody) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim() !== '') {
                    const msg = this.value.trim();
                    this.value = '';
                    appendMessage('You', msg, 'user-msg');
                    processBotResponse(msg);
                }
            });
        }

        function appendMessage(sender, text, className) {
            const div = document.createElement('div');
            div.className = className;
            div.style.marginBottom = '1rem';
            div.style.padding = '0.5rem';
            div.style.borderRadius = '0.5rem';
            div.style.background = className === 'user-msg' ? 'var(--bg-light)' : 'rgba(79, 70, 229, 0.1)';
            div.style.textAlign = className === 'user-msg' ? 'right' : 'left';
            div.innerHTML = `<strong>${sender}:</strong> <br> ${text}`;
            chatBody.appendChild(div);
            chatBody.scrollTop = chatBody.scrollHeight;
            
            const placeholder = document.getElementById('chatPlaceholder');
            if(placeholder) placeholder.style.display = 'none';
        }

        function processBotResponse(msg) {
            const lowerMsg = msg.toLowerCase();
            let reply = "I'm a simple automated assistant. Try asking me 'Is it open today?' or 'What is your pet policy?'.";
            
            if (lowerMsg.includes('open') || lowerMsg.includes('today') || lowerMsg.includes('hours') || lowerMsg.includes('time')) {
                reply = "Yes, we are open today! Our standard operating hours are from 9:00 AM to 5:00 PM.";
            } else if (lowerMsg.includes('pet') || lowerMsg.includes('dog') || lowerMsg.includes('cat') || lowerMsg.includes('animal')) {
                reply = "Service animals are welcome. However, general pets are not allowed inside the main attraction areas for safety reasons.";
            }

            setTimeout(() => {
                appendMessage('Bot', reply, 'bot-msg');
            }, 600);
        }
    }
});
