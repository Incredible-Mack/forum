<!DOCTYPE html>
<html>

<head>
    <title>WebSocket Chat</title>
    <style>
    #chat {
        width: 500px;
        height: 300px;
        border: 1px solid #ccc;
        overflow-y: scroll;
    }

    #users {
        width: 200px;
        height: 300px;
        border: 1px solid #ccc;
        float: right;
    }

    #message {
        width: 400px;
    }
    </style>
</head>

<body>
    <h1>Group Chat</h1>
    <div id="users"></div>
    <div id="chat"></div>
    <input type="text" id="message" placeholder="Type your message">
    <button onclick="sendMessage()">Send</button>

    <script>
    const name = prompt("Enter your name:");
    const chat = document.getElementById('chat');
    const usersList = document.getElementById('users');
    const messageInput = document.getElementById('message');

    const ws = new WebSocket('ws://localhost:8080');

    ws.onopen = () => {
        ws.send(JSON.stringify({
            type: 'join',
            name: name
        }));
    };

    ws.onmessage = (e) => {
        const data = JSON.parse(e.data);

        if (data.type === 'join') {
            chat.innerHTML += `<p><em>${data.user} joined the chat</em></p>`;
            updateUsersList(data.users);
        } else if (data.type === 'leave') {
            chat.innerHTML += `<p><em>${data.user} left the chat</em></p>`;
            updateUsersList(data.users);
        } else if (data.type === 'message') {
            chat.innerHTML += `<p><strong>${data.user}:</strong> ${data.text} <small>${data.time}</small></p>`;
        }

        chat.scrollTop = chat.scrollHeight;
    };

    function updateUsersList(users) {
        usersList.innerHTML = '<h3>Online Users</h3><ul>' +
            users.map(user => `<li>${user}</li>`).join('') + '</ul>';
    }

    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            ws.send(JSON.stringify({
                type: 'message',
                text: message
            }));
            messageInput.value = '';
        }
    }

    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
    </script>
</body>

</html>