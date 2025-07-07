class ChatApp {
  constructor() {
    this.ws = null;
    this.currentRoom = null;
    this.userId = document.getElementById("user-id").value;
    this.username = document.getElementById("username").value;

    this.initEventListeners();
    this.connectToServer();
  }

  initEventListeners() {
    // Room selection
    document.querySelectorAll(".room-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        this.joinRoom(e.target.dataset.roomId, e.target.textContent);
      });
    });

    // Create room
    document.getElementById("create-room-btn").addEventListener("click", () => {
      const roomName = prompt("Enter room name:");
      if (roomName) {
        this.createRoom(roomName);
      }
    });

    // Message sending
    document
      .getElementById("message-input")
      .addEventListener("keypress", (e) => {
        if (e.key === "Enter" && this.currentRoom) {
          this.sendMessage();
        }
      });

    document.getElementById("send-btn").addEventListener("click", () => {
      if (this.currentRoom) {
        this.sendMessage();
      }
    });

    // File upload
    document.getElementById("file-input").addEventListener("change", (e) => {
      if (e.target.files.length > 0 && this.currentRoom) {
        this.uploadFile(e.target.files[0]);
        e.target.value = ""; // Reset input
      }
    });
  }

  connectToServer() {
    this.ws = new WebSocket("ws://localhost:8080");

    this.ws.onopen = () => {
      console.log("Connected to WebSocket server");
    };

    this.ws.onmessage = (e) => {
      const data = JSON.parse(e.data);

      switch (data.type) {
        case "history":
          this.displayHistory(data.messages);
          break;

        case "join":
          this.displaySystemMessage(`${data.user} joined the room`);
          this.updateUserList(data.users);
          break;

        case "leave":
          this.displaySystemMessage(`${data.user} left the room`);
          this.updateUserList(data.users);
          break;

        case "message":
          this.displayMessage(data);
          break;
      }
    };

    this.ws.onclose = () => {
      console.log("Disconnected from WebSocket server");
      setTimeout(() => this.connectToServer(), 5000); // Reconnect after 5 seconds
    };
  }

  joinRoom(roomId, roomName) {
    if (this.currentRoom === roomId) return;

    this.currentRoom = roomId;
    document.getElementById("current-room").textContent = roomName;
    document.getElementById("messages-container").innerHTML = "";
    document.getElementById("user-list").innerHTML = "";

    // Notify server we're joining
    this.ws.send(
      JSON.stringify({
        type: "join",
        room_id: roomId,
        user_id: this.userId,
        username: this.username,
      })
    );
  }

  createRoom(roomName) {
    fetch("create_room.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `name=${encodeURIComponent(roomName)}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Add to room list and join
          const roomList = document.getElementById("room-list");
          const newRoom = document.createElement("li");
          newRoom.innerHTML = `<a href="#" class="room-link" data-room-id="${data.room_id}">${roomName}</a>`;
          roomList.appendChild(newRoom);

          // Add click event to new room
          newRoom.querySelector(".room-link").addEventListener("click", (e) => {
            e.preventDefault();
            this.joinRoom(e.target.dataset.roomId, e.target.textContent);
          });

          this.joinRoom(data.room_id, roomName);
        } else {
          alert("Failed to create room: " + (data.error || "Unknown error"));
        }
      });
  }

  sendMessage() {
    const input = document.getElementById("message-input");
    const message = input.value.trim();

    if (message) {
      this.ws.send(
        JSON.stringify({
          type: "message",
          text: message,
        })
      );
      input.value = "";
    }
  }

  uploadFile(file) {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("room_id", this.currentRoom);
    formData.append("user_id", this.userId);

    fetch("upload_file.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Notify server about the file
          this.ws.send(
            JSON.stringify({
              type: "file",
              message_id: data.message_id,
            })
          );
        } else {
          alert("File upload failed: " + (data.error || "Unknown error"));
        }
      });
  }

  displayHistory(messages) {
    const container = document.getElementById("messages-container");
    container.innerHTML = "";

    messages.forEach((msg) => {
      this.displayMessage(msg, false);
    });

    container.scrollTop = container.scrollHeight;
  }

  displayMessage(msg, scroll = true) {
    const container = document.getElementById("messages-container");
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${
      msg.user_id == this.userId ? "own-message" : ""
    }`;

    if (msg.is_file) {
      const fileExt = msg.file_path.split(".").pop().toLowerCase();
      let fileContent = "";

      if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
        fileContent = `<img src="${msg.file_path}" alt="${msg.text}" class="chat-image">`;
      } else {
        fileContent = `<a href="${msg.file_path}" download="${msg.text}">Download ${msg.text}</a>`;
      }

      messageDiv.innerHTML = `
                <div class="message-header">
                    <strong>${msg.user}</strong>
                    <small>${msg.time}</small>
                </div>
                <div class="message-content">${fileContent}</div>
            `;
    } else {
      messageDiv.innerHTML = `
                <div class="message-header">
                    <strong>${msg.user}</strong>
                    <small>${msg.time}</small>
                </div>
                <div class="message-content">${emojify(msg.text)}</div>
            `;
    }

    container.appendChild(messageDiv);

    if (scroll) {
      container.scrollTop = container.scrollHeight;
    }
  }

  displaySystemMessage(text) {
    const container = document.getElementById("messages-container");
    const messageDiv = document.createElement("div");
    messageDiv.className = "system-message";
    messageDiv.textContent = text;
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
  }

  updateUserList(users) {
    const userList = document.getElementById("user-list");
    userList.innerHTML =
      "<h3>Online Users</h3><ul>" +
      users.map((user) => `<li>${user}</li>`).join("") +
      "</ul>";
  }
}

// Room dropdown functionality
const roomDropdownToggle = document.getElementById("room-dropdown-toggle");
const roomDropdownMenu = document.getElementById("room-dropdown-menu");
const currentRoomDisplay = document.getElementById("current-room");

// Toggle dropdown menu
roomDropdownToggle.addEventListener("click", (e) => {
  e.stopPropagation();
  roomDropdownMenu.classList.toggle("active");
});

// Close dropdown when clicking outside
document.addEventListener("click", () => {
  roomDropdownMenu.classList.remove("active");
});

// Room selection
document.querySelectorAll(".room-link").forEach((link) => {
  link.addEventListener("click", (e) => {
    e.preventDefault();
    const roomId = link.getAttribute("data-room-id");
    const roomName = link.textContent;

    // Update UI
    currentRoomDisplay.textContent = roomName;
    document
      .querySelectorAll(".room-link")
      .forEach((l) => l.classList.remove("active"));
    link.classList.add("active");

    // Close dropdown
    roomDropdownMenu.classList.remove("active");

    // Your existing room switching logic here
    switchRoom(roomId);
  });
});

// Make sure to update the current room display when switching rooms
function updateCurrentRoomDisplay(roomName) {
  currentRoomDisplay.textContent = roomName;
}

// Helper function to replace text emojis
function emojify(text) {
  const emojiMap = {
    ":)": "😊",
    ":-)": "😊",
    ":D": "😃",
    ":-D": "😃",
    ":(": "😞",
    ":-(": "😞",
    ";)": "😉",
    ";-)": "😉",
    ":P": "😛",
    ":-P": "😛",
    ":O": "😮",
    ":-O": "😮",
    ":*": "😘",
    ":-*": "😘",
  };

  return text.replace(/(:\S)/g, (match) => emojiMap[match] || match);
}

// Initialize chat when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ChatApp();
});
