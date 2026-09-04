<!-- Messenger-Style Floating Chat Widget -->
<div class="fixed bottom-5 right-5 z-50">
    <!-- Floating Toggle Button -->
    <button 
        id="chat-toggle-btn" 
        class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-105 focus:outline-none cursor-pointer"
        aria-label="Open Messenger Chat"
    >
        <svg id="chat-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <svg id="chat-icon-close" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Chat Popup Box (Hidden by default) -->
    <div 
        id="chat-popup-window" 
        class="hidden absolute bottom-20 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col transition-all duration-300 transform origin-bottom-right scale-95 opacity-0"
        style="height: 480px;"
    >
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-4 py-3 flex items-center justify-between text-white">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-bold text-xs">💬</div>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-blue-600 rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight">Hostel Community Chat</h3>
                    <p class="text-[10px] text-blue-100">ECC Encrypted & Secure</p>
                </div>
            </div>
            <button id="chat-close-popup" class="text-white/80 hover:text-white p-1 rounded-lg transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Message Feed Window -->
        <div id="chat-messages-container" class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50 text-xs flex flex-col">
            <div class="text-center text-slate-400 my-auto">Loading encrypted messages...</div>
        </div>

        <!-- Message Input Form -->
        <form id="chat-form" class="p-3 bg-white border-t border-slate-100 flex gap-2 items-center">
            @csrf
            <input 
                type="text" 
                id="chat-input-message" 
                placeholder="Type a secure message..." 
                required 
                maxlength="1000"
                autocomplete="off"
                class="flex-1 bg-slate-100 border border-slate-200 text-slate-800 text-xs rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 p-2.5 outline-none transition"
            >
            <button 
                type="submit" 
                id="chat-send-btn"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl transition shadow-sm cursor-pointer"
            >
                Send
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("chat-toggle-btn");
    const closeBtn = document.getElementById("chat-close-popup");
    const popupWindow = document.getElementById("chat-popup-window");
    const openIcon = document.getElementById("chat-icon-open");
    const closeIcon = document.getElementById("chat-icon-close");
    
    const chatContainer = document.getElementById("chat-messages-container");
    const chatForm = document.getElementById("chat-form");
    const chatInput = document.getElementById("chat-input-message");
    const sendBtn = document.getElementById("chat-send-btn");

    if (!toggleBtn || !popupWindow) return;

    let isOpen = false;
    let pollInterval = null;

    function toggleChat() {
        isOpen = !isOpen;
        if (isOpen) {
            popupWindow.classList.remove("hidden");
            setTimeout(() => {
                popupWindow.classList.remove("scale-95", "opacity-0");
                popupWindow.classList.add("scale-100", "opacity-100");
            }, 10);
            openIcon.classList.add("hidden");
            closeIcon.classList.remove("hidden");
            chatInput.focus();
            fetchMessages();
            if (!pollInterval) {
                pollInterval = setInterval(fetchMessages, 4000);
            }
        } else {
            popupWindow.classList.remove("scale-100", "opacity-100");
            popupWindow.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                popupWindow.classList.add("hidden");
            }, 300);
            openIcon.classList.remove("hidden");
            closeIcon.classList.add("hidden");
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
    }

    toggleBtn.addEventListener("click", toggleChat);
    closeBtn.addEventListener("click", toggleChat);

    function fetchMessages() {
        if (!isOpen) return;
        fetch('/api/hostel/messages', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                renderMessages(data.messages);
            }
        })
        .catch(err => console.error("Error loading chat messages:", err));
    }

    function renderMessages(messages) {
        if (messages.length === 0) {
            chatContainer.innerHTML = '<div class="text-center text-slate-400 my-auto">No messages yet. Start the secure conversation!</div>';
            return;
        }

        let html = '';
        messages.forEach(msg => {
            let roleBadgeClass = 'bg-slate-100 text-slate-700';
            if (msg.role === 'admin') roleBadgeClass = 'bg-purple-50 text-purple-700 border border-purple-200';
            if (msg.role === 'warden') roleBadgeClass = 'bg-amber-50 text-amber-700 border border-amber-200';
            if (msg.role === 'student') roleBadgeClass = 'bg-blue-50 text-blue-700 border border-blue-200';

            html += `
                <div class="flex flex-col space-y-1">
                    <div class="flex items-center space-x-1.5">
                        <span class="font-semibold text-[11px] text-slate-800">${escapeHtml(msg.sender_name)}</span>
                        <span class="px-1 py-0.2 text-[8px] font-bold uppercase tracking-wider rounded ${roleBadgeClass}">${msg.role}</span>
                        <span class="text-[9px] text-slate-400">${msg.created_at}</span>
                    </div>
                    <div class="p-2.5 bg-white border border-slate-100 rounded-2xl shadow-2xs text-xs text-slate-700 max-w-[85%] break-words">
                        ${escapeHtml(msg.message)}
                    </div>
                </div>
            `;
        });

        const isScrolledToBottom = chatContainer.scrollHeight - chatContainer.clientHeight <= chatContainer.scrollTop + 50;
        chatContainer.innerHTML = html;
        if (isScrolledToBottom || chatContainer.innerHTML === '') {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    chatForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const messageText = chatInput.value.trim();
        if (!messageText) return;

        sendBtn.disabled = true;
        sendBtn.innerText = '...';

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('/api/hostel/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ message: messageText })
        })
        .then(response => response.json())
        .then(data => {
            chatInput.value = '';
            fetchMessages();
        })
        .catch(err => {
            alert('Failed to send encrypted message.');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerText = 'Send';
        });
    });
});
</script>