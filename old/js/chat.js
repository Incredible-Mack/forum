class ChatWebSocket {
  constructor() {
    this.socket = null;
    this.userId = document.body.getAttribute("data-user-id");
    this.username = document.body.getAttribute("data-username");
    this.threadId = document
      .querySelector(".chat-container")
      ?.getAttribute("data-thread-id");
    this.connect();
  }

  connect() {
    // Check if WebSocket is supported
    if (!window.WebSocket) {
      console.error("WebSocket not supported in this browser");
      return;
    }

    // Connect to WebSocket server
    const protocol = window.location.protocol === "https:" ? "wss://" : "ws://";
    this.socket = new WebSocket(protocol + window.location.hostname + ":8080");

    this.socket.onopen = () => {
      console.log("WebSocket connected");
      // Authenticate
      this.send({
        type: "auth",
        user_id: this.userId,
      });
    };

    this.socket.onmessage = (event) => {
      const data = JSON.parse(event.data);
      this.handleMessage(data);
    };

    this.socket.onclose = () => {
      console.log("WebSocket disconnected");
      // Try to reconnect after 5 seconds
      setTimeout(() => this.connect(), 5000);
    };

    this.socket.onerror = (error) => {
      console.error("WebSocket error:", error);
    };
  }

  send(data) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data));
    } else {
      console.error("WebSocket is not connected");
    }
  }

  handleMessage(data) {
    switch (data.type) {
      case "message":
        this.displayMessage(data);
        break;
      default:
        console.log("Unknown message type:", data.type);
    }
  }

  displayMessage(data) {
    if (this.threadId && data.thread_id != this.threadId) return;

    const chatContainer = document.querySelector(".chat-container");
    if (!chatContainer) return;

    const isCurrentUser = data.user_id == this.userId;
    const messageClass = isCurrentUser ? "sent" : "received";

    const messageElement = document.createElement("div");
    messageElement.className = `message ${messageClass}`;
    messageElement.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong>${data.username}</strong>
                <small class="message-time">${new Date(
                  data.timestamp
                ).toLocaleString()}</small>
            </div>
            <p class="mb-1">${data.message}</p>
        `;

    chatContainer.appendChild(messageElement);
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }

  sendMessage(message, threadId) {
    this.send({
      type: "message",
      thread_id: threadId || this.threadId,
      user_id: this.userId,
      username: this.username,
      message: message,
    });
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.chatSocket = new ChatWebSocket();

  // Message form submission
  const messageForm = document.getElementById("message-form");
  if (messageForm) {
    messageForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const messageInput = messageForm.querySelector('input[name="message"]');
      const message = messageInput.value.trim();

      if (message) {
        const threadId = messageForm.getAttribute("data-thread-id");
        window.chatSocket.sendMessage(message, threadId);
        messageInput.value = "";
      }
    });
  }
});
