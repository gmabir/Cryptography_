<!-- Messenger-Style Floating Chat Widget -->
<div class="fixed bottom-6 right-6 z-[9999]">
    <!-- Chat Popup Box -->
    <div 
        id="chat-popup-window" 
        style="display: none; height: 480px;"
        class="absolute bottom-20 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex-col"
    >
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 px-4 py-3 flex items-center justify-between text-white shrink-0 shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="relative">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm">💬</div>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-blue-800 rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight text-white">Hostel Community Chat</h3>
                    <p class="text-[10px] text-blue-200 font-medium">ECC Encrypted & Verified</p>
                </div>
            </div>
            <button type="button" onclick="toggleMessengerChat()" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition cursor-pointer">
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
        <form id="chat-form" class="p-3 bg-white border-t border-slate-100 flex gap-2 items-center shrink-0">
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

    <!-- Floating Toggle Button -->
    <button 
        type="button"
        onclick="toggleMessengerChat()"
        id="chat-toggle-btn" 
        class="w-14 h-14 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-full shadow-2xl flex items-center justify-center transition-transform focus:outline-none cursor-pointer"
        aria-label="Toggle Messenger Chat"
    >
        <!-- Open Icon (Chat Bubble) -->
        <svg id="chat-icon-open" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <!-- Close Icon (X) -->
        <svg id="chat-icon-close" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>

<script>
let isChatOpen = false;
let chatPollInterval = null;

function toggleMessengerChat() {
    const popup = document.getElementById("chat-popup-window");
    const openIcon = document.getElementById("chat-icon-open");
    const closeIcon = document.getElementById("chat-icon-close");
    const input = document.getElementById("chat-input-message");

    if (!popup) return;

    isChatOpen = !isChatOpen;

    if (isChatOpen) {
        popup.style.display = "flex";
        openIcon.style.display = "none";
        closeIcon.style.display = "block";
        if (input) input.focus();
        fetchChatMessages();
        if (!chatPollInterval) {
            chatPollInterval = setInterval(fetchChatMessages, 4000);
        }
    } else {
        popup.style.display = "none";
        openIcon.style.display = "block";
        closeIcon.style.display = "none";
        if (chatPollInterval) {
            clearInterval(chatPollInterval);
            chatPollInterval = null;
        }
    }
}

function fetchChatMessages() {
    if (!isChatOpen) return;
    fetch('/api/hostel/messages', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.messages) {
            renderChatMessages(data.messages);
        }
    })
    .catch(err => console.error("Error loading chat:", err));
}

function renderChatMessages(messages) {
    const container = document.getElementById("chat-messages-container");
    if (!container) return;

    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-slate-400 my-auto">No messages yet. Say hello!</div>';
        return;
    }

    let html = '';
    messages.forEach(msg => {
        let badgeClass = 'bg-slate-100 text-slate-700';
        if (msg.role === 'admin') badgeClass = 'bg-purple-100 text-purple-800';
        if (msg.role === 'warden') badgeClass = 'bg-amber-100 text-amber-800';
        if (msg.role === 'student') badgeClass = 'bg-blue-100 text-blue-800';

        html += `
            <div class="flex flex-col space-y-1">
                <div class="flex items-center space-x-1.5">
                    <span class="font-semibold text-[11px] text-slate-800">${escapeChatHtml(msg.sender_name)}</span>
                    <span class="px-1.5 py-0.2 text-[8px] font-bold uppercase tracking-wider rounded ${badgeClass}">${msg.role}</span>
                    <span class="text-[9px] text-slate-400">${msg.created_at}</span>
                </div>
                <div class="p-2.5 bg-white border border-slate-100 rounded-2xl shadow-xs text-xs text-slate-700 max-w-[85%] break-words">
                    ${escapeChatHtml(msg.message)}
                </div>
            </div>
        `;
    });

    const isAtBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 50;
    container.innerHTML = html;
    if (isAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

function escapeChatHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener("DOMContentLoaded", function () {
    const chatForm = document.getElementById("chat-form");
    if (!chatForm) return;

    chatForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const input = document.getElementById("chat-input-message");
        const sendBtn = document.getElementById("chat-send-btn");
        const text = input.value.trim();
        if (!text) return;

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
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(() => {
            input.value = '';
            fetchChatMessages();
        })
        .catch(() => alert('Failed to send encrypted message.'))
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerText = 'Send';
        });
    });
});
</script>