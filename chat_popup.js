// Simple Live Chat Popup JS
(function(){
    // Create chat button
    var chatBtn = document.createElement('div');
    chatBtn.id = 'chat-popup-btn';
    chatBtn.innerHTML = '<span>💬 Chat</span>';
    document.body.appendChild(chatBtn);

    // Create chat popup
    var chatPopup = document.createElement('div');
    chatPopup.id = 'chat-popup';
    chatPopup.innerHTML = `
        <div id="chat-header">Live Chat <button id="chat-close">&times;</button></div>
        <div id="chat-messages"></div>
        <form id="chat-form">
            <input type="text" id="chat-input" autocomplete="off" placeholder="Type your message..." required />
            <button type="submit">Send</button>
        </form>
    `;
    document.body.appendChild(chatPopup);
    chatPopup.style.display = 'none';

    // Toggle popup
    chatBtn.onclick = function(){ chatPopup.style.display = 'block'; chatBtn.style.display = 'none'; scrollMessages(); };
    chatPopup.querySelector('#chat-close').onclick = function(){ chatPopup.style.display = 'none'; chatBtn.style.display = 'block'; };

    // Send message
    var chatForm = chatPopup.querySelector('#chat-form');
    var chatInput = chatPopup.querySelector('#chat-input');
    chatForm.onsubmit = function(e){
        e.preventDefault();
        var msg = chatInput.value.trim();
        if (!msg) return;
        fetch('chat_popup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        }).then(function(){ chatInput.value = ''; fetchMessages(); });
    };

    // Fetch messages
    function fetchMessages(){
        fetch('chat_popup.php')
            .then(r => r.json())
            .then(function(data){
                var html = '';
                (data.messages||[]).forEach(function(m){
                    html += '<div class="chat-msg"><span class="chat-time">['+m.time+']</span> <b>'+escapeHtml(m.user)+':</b> '+escapeHtml(m.message)+'</div>';
                });
                chatPopup.querySelector('#chat-messages').innerHTML = html;
                scrollMessages();
            });
    }
    function scrollMessages(){
        var box = chatPopup.querySelector('#chat-messages');
        box.scrollTop = box.scrollHeight;
    }
    function escapeHtml(str){
        return str.replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    // Poll for new messages
    setInterval(fetchMessages, 2000);
    fetchMessages();
})();
