class CommunityChat {
  constructor() {
    this.ws = null;
    this.currentRoom = null;
    this.userId = document.getElementById("user-id").value;
    this.username = document.getElementById("username").value;
    this.userInitial = this.username.charAt(0).toUpperCase();

    this.initElements();
    this.initEventListeners();
    this.connectToServer();
  }

  initElements() {
    this.elements = {
      roomDropdownToggle: document.getElementById("room-dropdown-toggle"),
      roomDropdownMenu: document.getElementById("room-dropdown-menu"),
      currentRoomDisplay: document.getElementById("current-room"),
      roomSearch: document.getElementById("room-search"),
      roomList: document.getElementById("room-list"),
      messagesContainer: document.getElementById("messages-container"),
      messageInput: document.getElementById("message-input"),
      sendBtn: document.getElementById("send-btn"),
      discussionTitle: document.getElementById("discussion-title"),
      discussionDescription: document.getElementById("discussion-description"),
      roomMemberCount: document.getElementById("room-member-count"),
      roomPostCount: document.getElementById("room-post-count"),
      emojiBtn: document.getElementById("emoji-btn"),
      fileInput: document.getElementById("file-input"),
      createRoomBtn: document.getElementById("create-room-btn"),
      categoryLinks: document.querySelectorAll(".category-link"),
      roomLinks: document.querySelectorAll(".room-link"),
    };
  }

  initEventListeners() {
    // Room dropdown toggle
    this.elements.roomDropdownToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      this.elements.roomDropdownMenu.classList.toggle("active");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", () => {
      this.elements.roomDropdownMenu.classList.remove("active");
    });

    // Room search functionality
    this.elements.roomSearch.addEventListener("input", () =>
      this.filterRooms()
    );

    // Category filtering
    this.elements.categoryLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        this.filterRoomsByCategory(link.getAttribute("data-category"));
      });
    });

    // Room selection
    this.elements.roomLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        this.selectRoom(
          link.getAttribute("data-room-id"),
          link.querySelector(".room-name").textContent,
          link.querySelector(".member-count").textContent,
          link.querySelector(".post-count").textContent,
          link.getAttribute("data-category")
        );
      });
    });

    // Message input
    this.elements.messageInput.addEventListener("input", () => {
      this.elements.sendBtn.disabled =
        this.elements.messageInput.value.trim() === "";
    });

    this.elements.messageInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        if (!this.elements.sendBtn.disabled) {
          this.sendMessage();
        }
      }
    });

    // Send button
    this.elements.sendBtn.addEventListener("click", () => this.sendMessage());

    // Emoji button
    this.elements.emojiBtn.addEventListener("click", () =>
      this.toggleEmojiPicker()
    );

    // File upload
    this.elements.fileInput.addEventListener("change", (e) => {
      if (e.target.files.length > 0 && this.currentRoom) {
        this.uploadFile(e.target.files[0]);
        e.target.value = "";
      }
    });

    // Create room button
    this.elements.createRoomBtn.addEventListener("click", () =>
      this.createRoomPrompt()
    );
  }

  connectToServer() {
    // In a real implementation, you would connect to your WebSocket server
    console.log("Connecting to WebSocket server...");

    // Mock connection for demonstration
    setTimeout(() => {
      console.log("Connected to WebSocket server");
      this.ws = {
        send: (data) => {
          console.log("Sending message:", data);
          // Simulate receiving messages
          if (JSON.parse(data).type === "message") {
            setTimeout(() => {
              this.receiveMessage({
                type: "message",
                id: "msg-" + Date.now(),
                user_id: this.userId,
                username: this.username,
                user_initials: this.userInitial,
                text: JSON.parse(data).text,
                time: this.formatTime(new Date()),
                is_own: true,
              });
            }, 300);
          }
        },
      };
    }, 1000);
  }

  filterRooms() {
    const searchTerm = this.elements.roomSearch.value.toLowerCase();
    this.elements.roomLinks.forEach((room) => {
      const roomName = room
        .querySelector(".room-name")
        .textContent.toLowerCase();
      room.style.display = roomName.includes(searchTerm) ? "flex" : "none";
    });
  }

  filterRoomsByCategory(category) {
    // Update active category
    this.elements.categoryLinks.forEach((l) => l.classList.remove("active"));
    document
      .querySelector(`.category-link[data-category="${category}"]`)
      .classList.add("active");

    // Filter rooms
    this.elements.roomLinks.forEach((room) => {
      const roomCategory = room.getAttribute("data-category");
      room.style.display =
        category === "all" || roomCategory === category ? "flex" : "none";
    });
  }

  selectRoom(roomId, roomName, memberCount, postCount, category) {
    if (this.currentRoom === roomId) return;

    this.currentRoom = roomId;
    this.elements.currentRoomDisplay.textContent = roomName;
    this.elements.discussionTitle.textContent = roomName;
    this.elements.roomMemberCount.textContent = memberCount;
    this.elements.roomPostCount.textContent = postCount;

    // Update active room
    this.elements.roomLinks.forEach((l) => l.classList.remove("active"));
    document
      .querySelector(`.room-link[data-room-id="${roomId}"]`)
      .classList.add("active");

    // Close dropdown
    this.elements.roomDropdownMenu.classList.remove("active");

    // Notify server we're joining
    if (this.ws) {
      this.ws.send(
        JSON.stringify({
          type: "join",
          room_id: roomId,
          user_id: this.userId,
          username: this.username,
        })
      );
    }

    // Load room content
    this.loadRoomContent(roomId);
  }

  loadRoomContent(roomId) {
    // In a real app, this would fetch from your API
    // For demo purposes, we'll use mock data
    const mockData = {
      description: `This is a discussion about ${
        document.querySelector(
          `.room-link[data-room-id="${roomId}"] .room-name`
        ).textContent
      }`,
      member_count: Math.floor(Math.random() * 100) + 10,
      post_count: Math.floor(Math.random() * 50) + 5,
      messages: this.generateMockMessages(10),
    };

    this.elements.discussionDescription.textContent = mockData.description;
    this.elements.roomMemberCount.textContent = mockData.member_count;
    this.elements.roomPostCount.textContent = mockData.post_count;

    this.displayMessages(mockData.messages);
  }

  generateMockMessages(count) {
    const messages = [];
    const users = [
      { name: "Alex", id: "1", initials: "A" },
      { name: "Jamie", id: "2", initials: "J" },
      { name: "Taylor", id: "3", initials: "T" },
      { name: this.username, id: this.userId, initials: this.userInitial },
    ];

    for (let i = 0; i < count; i++) {
      const isOwn = Math.random() > 0.7;
      const user = isOwn ? users[3] : users[Math.floor(Math.random() * 3)];

      messages.push({
        id: "msg-" + i,
        user_id: user.id,
        username: user.name,
        user_initials: user.initials,
        text: this.generateRandomMessage(),
        time: this.formatTime(new Date()),
        is_own: isOwn,
        thread_count: Math.random() > 0.7 ? Math.floor(Math.random() * 5) : 0,
      });
    }

    return messages;
  }

  generateRandomMessage() {
    const messages = [
      "What does everyone think about this topic?",
      "I've been researching this and found some interesting information.",
      "Has anyone else experienced this before?",
      "Thanks for sharing your perspective!",
      "I agree with what was said earlier.",
      "Here's a different way to look at it...",
      "Does anyone have recommendations for resources?",
      "This discussion has been really helpful so far.",
      "I'd love to hear more opinions on this.",
      "Let me share my experience with this.",
    ];
    return messages[Math.floor(Math.random() * messages.length)];
  }

  formatTime(date) {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }

  displayMessages(messages) {
    this.elements.messagesContainer.innerHTML = "";

    if (messages.length === 0) {
      this.elements.messagesContainer.innerHTML = `
        <div class="empty-state">
          <div class="empty-state-icon">
            <i class="far fa-comment-dots"></i>
          </div>
          <div class="empty-state-text">No posts yet. Be the first to contribute!</div>
        </div>
      `;
      return;
    }

    messages.forEach((message) => {
      this.displayMessage(message, false);
    });

    this.elements.messagesContainer.scrollTop =
      this.elements.messagesContainer.scrollHeight;
  }

  displayMessage(message, scroll = true) {
    const messageEl = document.createElement("div");
    messageEl.className = `message ${
      message.is_own ? "own-message" : "other-message"
    }`;

    messageEl.innerHTML = `
      <div class="message-content">
        <div class="message-info">
          <div class="message-user">
            <div class="message-user-avatar">${message.user_initials}</div>
            <span class="message-user-name">${message.username}</span>
          </div>
          <span class="message-time">${message.time}</span>
        </div>
        <div class="message-text">${this.emojify(message.text)}</div>
        ${
          message.thread_count > 0
            ? `<div class="thread-count">${message.thread_count} replies</div>`
            : ""
        }
      </div>
    `;

    this.elements.messagesContainer.appendChild(messageEl);

    if (scroll) {
      this.elements.messagesContainer.scrollTop =
        this.elements.messagesContainer.scrollHeight;
    }
  }

  sendMessage() {
    const messageText = this.elements.messageInput.value.trim();
    if (messageText === "" || !this.currentRoom) return;

    // Create message object
    const message = {
      id: "msg-" + Date.now(),
      user_id: this.userId,
      username: this.username,
      user_initials: this.userInitial,
      text: messageText,
      time: this.formatTime(new Date()),
      is_own: true,
      thread_count: 0,
    };

    // Send to server
    if (this.ws) {
      this.ws.send(
        JSON.stringify({
          type: "message",
          room_id: this.currentRoom,
          text: messageText,
        })
      );
    }

    // Display immediately (optimistic UI)
    this.displayMessage(message);

    // Clear input
    this.elements.messageInput.value = "";
    this.elements.sendBtn.disabled = true;

    // Update post count
    const currentCount = parseInt(this.elements.roomPostCount.textContent);
    this.elements.roomPostCount.textContent = currentCount + 1;
  }

  receiveMessage(message) {
    this.displayMessage(message);

    // Update post count if not our own message
    if (!message.is_own) {
      const currentCount = parseInt(this.elements.roomPostCount.textContent);
      this.elements.roomPostCount.textContent = currentCount + 1;
    }
  }

  toggleEmojiPicker() {
    // In a real implementation, this would show an emoji picker
    alert("Emoji picker would open here");
    // Example: https://github.com/missive/emoji-mart
  }

  createRoomPrompt() {
    const roomName = prompt("Enter new discussion name:");
    if (roomName && roomName.trim() !== "") {
      this.createRoom(roomName.trim());
    }
  }

  createRoom(roomName) {
    // In a real implementation, this would call your API
    console.log(`Creating room: ${roomName}`);

    // Mock response
    setTimeout(() => {
      const roomId = "room-" + Date.now();
      const newRoom = document.createElement("a");
      newRoom.className = "dropdown-item room-link";
      newRoom.setAttribute("data-room-id", roomId);
      newRoom.setAttribute("data-category", "general");
      newRoom.innerHTML = `
        <span class="room-name">${roomName}</span>
        <span class="room-meta">
          <span class="member-count"><i class="fas fa-user"></i> 1</span>
          <span class="post-count"><i class="fas fa-comment"></i> 0</span>
        </span>
      `;

      newRoom.addEventListener("click", (e) => {
        e.preventDefault();
        this.selectRoom(roomId, roomName, "1", "0", "general");
      });

      this.elements.roomList.appendChild(newRoom);
      this.selectRoom(roomId, roomName, "1", "0", "general");
    }, 500);
  }

  uploadFile(file) {
    // In a real implementation, this would upload to your server
    console.log(`Uploading file: ${file.name}`);

    // Mock response
    setTimeout(() => {
      const message = {
        id: "msg-" + Date.now(),
        user_id: this.userId,
        username: this.username,
        user_initials: this.userInitial,
        text: file.name,
        time: this.formatTime(new Date()),
        is_own: true,
        is_file: true,
        file_path: URL.createObjectURL(file),
        thread_count: 0,
      };

      this.displayMessage(message);

      // Update post count
      const currentCount = parseInt(this.elements.roomPostCount.textContent);
      this.elements.roomPostCount.textContent = currentCount + 1;
    }, 1000);
  }

  emojify(text) {
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
}

// Initialize the chat when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new CommunityChat();
});
