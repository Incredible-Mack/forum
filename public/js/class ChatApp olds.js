class ChatApp {
  constructor() {
    this.elements = {};

    this.ws = null;
    this.currentRoom = null;
    this.userId = document.getElementById("user-id").value;
    this.username = document.getElementById("username").value;
    this.userInitial = this.username.charAt(0).toUpperCase();
    this.isFirstConnection = true;

    setTimeout(() => {
      this.initEventListeners();
      this.initElements();
      this.connectToServer();
      this.startActivityUpdates();
      this.setupAnnouncementModal();
    }, 50);
  }

  initElements() {
    try {
      this.elements = {
        announcementContent: document.getElementById("announcement-content"),
        newAnnouncementBtn: document.getElementById("new-announcement-btn"),

        announcementModal: document.getElementById("announcement-modal"),
        announcementBtn: document.getElementById("announcementBtn"),
        closeModal: document.querySelector(".close-modal"),

        announcementForm: document.getElementById("announcement-form"),
        announcementInput: document.getElementById("announcement-input"),
        closeAnnouncementModal: document.querySelector(
          ".close-announcement-modal"
        ),
        categoryLinks: document.querySelectorAll(".category-link") || [],
        // Add all other elements you need
      };
    } catch (error) {
      console.error("Error initializing elements:", error);
      this.elements = {}; // Ensure elements is always an object
    }
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
      const modal = document.getElementById("room-creation-modal");
      const input = document.getElementById("room-name-input");
      const description = document.getElementById("description");

      // Show modal
      modal.style.display = "block";
      input.focus();

      // Create room handler
      const createHandler = () => {
        const roomName = input.value.trim();
        const descriptionName = description.value.trim();
        if (roomName) {
          this.createRoom(roomName, descriptionName);
          modal.style.display = "none";
          input.value = ""; // Clear input for next use
        }
      };

      // Close modal handler
      const closeHandler = () => {
        modal.style.display = "none";
        input.value = "";
      };

      // Set up event listeners
      document.querySelector(".create-btn").onclick = createHandler;
      document.querySelector(".close-modal").onclick = closeHandler;
      document.querySelector(".cancel-btn").onclick = closeHandler;

      // Close when clicking outside modal
      window.onclick = (event) => {
        if (event.target === modal) {
          closeHandler();
        }
      };
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

    document
      .querySelector(".toggle-user-list")
      .addEventListener("click", (e) => {
        e.stopPropagation();
        document.getElementById("user-list").classList.toggle("show");
      });

    document.querySelector(".close-user-list").addEventListener("click", () => {
      document.getElementById("user-list").classList.remove("show");
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest(".online-users")) {
        document.getElementById("user-list").classList.remove("show");
      }
    });

    // File upload
    document.getElementById("file-input").addEventListener("change", (e) => {
      if (e.target.files.length > 0 && this.currentRoom) {
        this.uploadFile(e.target.files[0]);
        e.target.value = ""; // Reset input
      }
    });

    // if (this.elements.categoryLinks && this.elements.categoryLinks.length > 0) {
    //   Array.from(this.elements.categoryLinks).forEach((link) => {
    //     link.addEventListener("click", (e) => {
    //       e.preventDefault();
    //       const category = e.currentTarget.dataset.category;
    //       this.handleCategorySelect(category);
    //     });
    //   });
    // } else {
    //   console.warn("No category links found");
    // }

    if (this.elements.announcementBtn) {
      this.elements.announcementBtn.addEventListener("click", (e) => {
        e.preventDefault();
        this.toggleAnnouncementModal(true);
      });
    }

    if (this.elements.closeModal) {
      this.elements.closeModal.addEventListener("click", () => {
        this.toggleAnnouncementModal(false);
      });
    }

    window.addEventListener("click", (e) => {
      if (e.target === this.elements.announcementModal) {
        this.toggleAnnouncementModal(false);
      }
    });

    // Announcement controls for admin
    if (this.isAdmin) {
      this.elements.newAnnouncementBtn.addEventListener("click", () => {
        this.elements.announcementModal.style.display = "block";
      });

      this.elements.announcementForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.postAnnouncement();
      });

      this.elements.closeAnnouncementModal.addEventListener("click", () => {
        this.elements.announcementModal.style.display = "none";
      });
    } else {
      document.getElementById("announcement-controls").style.display = "none";
    }
  }
  toggleAnnouncementModal(show) {
    if (this.elements.announcementModal) {
      this.elements.announcementModal.style.display = show ? "block" : "none";
    }
  }

  setupAnnouncementModal() {
    // Close modal when clicking outside
    window.addEventListener("click", (e) => {
      if (e.target === this.elements.announcementModal) {
        this.elements.announcementModal.style.display = "none";
      }
    });
  }

  handleCategorySelect(category) {
    // Update active category
    this.elements.categoryLinks.forEach((link) => {
      link.classList.toggle("active", link.dataset.category === category);
    });

    if (category === "announcements") {
      this.loadAnnouncements();
      this.elements.announcementModal.style.display = "block";
    } else {
      // Handle other categories
      this.elements.announcementModal.style.display = "none";
    }
  }

  loadAnnouncements() {
    // Fetch announcements from server
    fetch("get_announcements.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.displayAnnouncements(data.announcements);
        }
      });
  }

  postAnnouncement() {
    const announcementText = this.elements.announcementInput.value.trim();
    if (!announcementText) return;

    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      const announcementData = {
        type: "announcement",
        text: announcementText,
        user_id: this.userId,
        username: this.username,
        time: this.formatTime(new Date()),
      };

      this.ws.send(JSON.stringify(announcementData));

      // Clear input and close modal
      this.elements.announcementInput.value = "";
      this.elements.announcementModal.style.display = "none";
    }
  }

  displayAnnouncements(announcements) {
    const container = this.elements.announcementContent;
    container.innerHTML = "";

    if (announcements.length === 0) {
      container.innerHTML = `
        <div class="empty-announcements">
          <i class="fas fa-bullhorn"></i>
          <p>No announcements yet</p>
        </div>
      `;
      return;
    }
    announcements.forEach((announcement) => {
      const announcementEl = document.createElement("div");
      announcementEl.className = "announcement-item";
      announcementEl.innerHTML = `
        <div class="announcement-header">
          <div class="announcement-icon"><i class="fas fa-bullhorn"></i></div>
          <div class="announcement-title">Announcement</div>
          <div class="announcement-time">${announcement.time}</div>
        </div>
        <div class="announcement-text">${announcement.text}</div>
        <div class="announcement-author">Posted by ${announcement.username}</div>
      `;
      container.appendChild(announcementEl);
    });
  }

  connectToServer() {
    this.ws = new WebSocket("ws://localhost:8080");

    this.ws.onopen = () => {
      console.log("Connected to WebSocket server");
      if (this.currentRoom && this.isFirstConnection) {
        this.isFirstConnection = false;
        this.joinRoom(
          this.currentRoom,
          document.getElementById("current-room").textContent
        );
      }
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
        case "announcement":
          // Show notification to all users
          this.showAnnouncementNotification(data);
          // If announcements modal is open, refresh content
          if (
            document.querySelector(".category-link.active").dataset.category ===
            "announcements"
          ) {
            this.loadAnnouncements();
          }
          break;

        case "message":
          // Only display if it's not our own message (since we display optimistically)
          if (data.user_id !== this.userId) {
            this.displayMessage({
              id: data.id || `msg-${Date.now()}`,
              user_id: data.user_id,
              username: data.user,
              user_initials: data.user
                ? data.user.charAt(0).toUpperCase()
                : "?",
              text: data.text,
              time: data.time || this.formatTime(new Date()),
              is_own: false,
              is_file: data.is_file,
              file_path: data.file_path,
              thread_count: data.thread_count || 0,
            });
          }
          break;
      }
    };

    this.ws.onclose = () => {
      console.log("Disconnected from WebSocket server");
      setTimeout(() => this.connectToServer(), 5000); // Reconnect after 5 seconds
    };
  }
  showAnnouncementNotification(announcement) {
    const notification = document.createElement("div");
    notification.className = "announcement-notification";
    notification.innerHTML = `
      <div class="notification-content">
        <i class="fas fa-bullhorn"></i>
        <span>New announcement: ${announcement.text.substring(0, 50)}...</span>
        <button class="view-announcement-btn">View</button>
      </div>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 10 seconds
    setTimeout(() => {
      notification.classList.add("fade-out");
      setTimeout(() => notification.remove(), 500);
    }, 10000);

    // Click handler
    notification
      .querySelector(".view-announcement-btn")
      .addEventListener("click", () => {
        // Switch to announcements category and open modal
        document
          .querySelector('.category-link[data-category="announcements"]')
          .click();
        notification.remove();
      });
  }

  startActivityUpdates() {
    this.activityInterval = setInterval(() => {
      if (this.currentRoom) this.updateUserActivity();
    }, 30000);
  }

  async updateUserActivity() {
    try {
      const response = await fetch("update_activity.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `room_id=${encodeURIComponent(this.currentRoom)}`,
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.text(); // or use .json() if response is JSON
      console.log("Activity updated successfully:", result);

      this.fetchOnlineUsers();
    } catch (error) {
      console.error("Error updating activity:", error);
    }
  }

  async fetchOnlineUsers() {
    try {
      const response = await fetch(
        `get_online_users.php?room_id=${encodeURIComponent(this.currentRoom)}`
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data && data.users) {
        console.log("Online users:", data.users);
        const users = data.users;

        const onlineCount = document.querySelector("#online-count");
        const userListItems = document.querySelector("#user-list");

        // if (!onlineCount || !userListItems) {
        //   console.warn("DOM elements for user list not found.");
        //   return;
        // }

        onlineCount.textContent = users.length;
        userListItems.innerHTML = users
          .map(
            (user) => `
            <li>
              <div class="user-avatar">${user.charAt(0).toUpperCase()}</div>
              <span class="username">${user}</span>
            </li>`
          )
          .join("");
      } else {
        console.warn("No users found in response:", data);
      }
    } catch (error) {
      console.error("Error fetching online users:", error);
    }
  }

  joinRoom(roomId, roomName) {
    if (this.currentRoom === roomId) return;

    this.currentRoom = roomId;
    document.getElementById("current-room").textContent = roomName;
    document.getElementById("messages-container").innerHTML = "";
    document.getElementById("user-list").innerHTML = "";

    // Notify server we're joining
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(
        JSON.stringify({
          type: "join",
          room_id: roomId,
          user_id: this.userId,
          username: this.username,
        })
      );
    }
  }

  createRoom(roomName, descriptionName) {
    fetch("create_room.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `name=${encodeURIComponent(
        roomName
      )}&descriptionName=${encodeURIComponent(descriptionName)}`,
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
      const messageId = `msg-${Date.now()}`;

      // Display optimistically
      this.displayMessage({
        id: messageId,
        user_id: this.userId,
        username: this.username,
        user_initials: this.userInitial,
        text: message,
        time: this.formatTime(new Date()),
        is_own: true,
        thread_count: 0,
      });

      // Send to server
      if (this.ws && this.ws.readyState === WebSocket.OPEN) {
        this.ws.send(
          JSON.stringify({
            type: "message",
            text: message,
            message_id: messageId,
          })
        );
      }

      input.value = "";
    }
  }

  uploadFile(file) {
    const messageId = `msg-${Date.now()}`;

    // Display optimistically
    this.displayMessage({
      id: messageId,
      user_id: this.userId,
      username: this.username,
      user_initials: this.userInitial,
      text: file.name,
      time: this.formatTime(new Date()),
      is_own: true,
      is_file: true,
      file_path: URL.createObjectURL(file),
      thread_count: 0,
    });

    const formData = new FormData();
    formData.append("file", file);
    formData.append("room_id", this.currentRoom);
    formData.append("user_id", this.userId);
    formData.append("message_id", messageId);

    fetch("upload_file.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (!data.success) {
          alert("File upload failed: " + (data.error || "Unknown error"));
        }
      });
  }

  displayHistory(messages) {
    console.log("Displaying history messages:", messages);
    console.log("Current user ID:", this.userId);
    const container = document.getElementById("messages-container");
    container.innerHTML = "";
    if (messages.length === 0) {
      container.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="far fa-comment-dots"></i>
        </div>
        <div class="empty-state-text">No messages yet. Start the conversation!</div>
      </div>
    `;
      return;
    }

    messages.forEach((msg) => {
      this.displayMessage(
        {
          id: msg.id || `msg-${Date.now()}`,
          user_id: msg.user_id,
          username: msg.username || msg.user, // Use either username or user field
          user_initials: (msg.username || msg.user).charAt(0).toUpperCase(),
          text: msg.text,
          time:
            msg.time || this.formatTime(new Date(msg.timestamp || Date.now())),
          is_own: msg.user_id == this.userId, // Use loose equality comparison
          is_file: msg.is_file,
          file_path: msg.file_path,
          thread_count: msg.thread_count || 0,
        },
        false
      );
    });
    container.scrollTop = container.scrollHeight;
  }
  displayMessage(message, scroll = true) {
    const container = document.getElementById("messages-container");

    // Check if message already exists
    if (document.getElementById(`message-${message.id}`)) {
      return;
    }

    const messageEl = document.createElement("div");
    messageEl.className = `message ${
      message.is_own ? "own-message" : "other-message"
    }`;
    messageEl.id = `message-${message.id}`;

    let contentHtml = "";
    if (message.is_file) {
      const fileExt = message.file_path.split(".").pop().toLowerCase();
      if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
        contentHtml = `<img src="${message.file_path}" alt="${message.text}" class="chat-image">`;
      } else {
        contentHtml = `<a href="${message.file_path}" download="${message.text}">Download ${message.text}</a>`;
      }
    } else {
      contentHtml = this.emojify(message.text);
    }

    // Add delete icon only for own messages
    const isAdmin = document.body.dataset.isAdmin === "true";

    const deleteIcon =
      message.is_own || isAdmin
        ? `<div class="message-actions" title='Delete Message'>
       <i class="fas fa-trash delete-message" data-message-id="${message.id}"></i>
     </div>`
        : "";

    messageEl.innerHTML = `
      <div class="message-content">
        <div class="message-info">
          <div class="message-user">
            <div class="message-user-avatar">${message.user_initials}</div>
            <span class="message-user-name">${message.username}</span>
          </div>
          <div div class = "message-time-actions">
            <span class="message-time">${message.time}</span>
            ${deleteIcon}
          </div>
        </div>
        <div class="message-text">${contentHtml}</div>
        ${
          message.thread_count > 0
            ? `<div class="thread-count">${message.thread_count} replies</div>`
            : ""
        }
      </div>
    `;

    container.appendChild(messageEl);

    // Add click handler for delete icon
    if (message.is_own || isAdmin) {
      messageEl
        .querySelector(".delete-message")
        .addEventListener("click", (e) => {
          e.stopPropagation();
          this.deleteMessage(message.id);
        });
    }

    if (scroll) {
      container.scrollTop = container.scrollHeight;
    }
  }

  async deleteMessage(messageId) {
    if (confirm("Are you sure you want to delete this message?")) {
      try {
        // Remove from DOM immediately
        const messageEl = document.getElementById(`message-${messageId}`);
        if (messageEl) messageEl.remove();

        // Send delete request to server
        const response = await fetch("delete_message.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `message_id=${encodeURIComponent(messageId)}`,
        });

        if (!response.ok) {
          throw new Error("Failed to delete message");
        }

        // Notify other clients via WebSocket
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
          this.ws.send(
            JSON.stringify({
              type: "delete",
              message_id: messageId,
              room_id: this.currentRoom,
            })
          );
        }
      } catch (error) {
        console.error("Error deleting message:", error);
        alert("Failed to delete message. Please try again.");
      }
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

  formatTime(date) {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
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

// Initialize chat when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ChatApp();

  // User list toggle - use event delegation
  document.body.addEventListener("click", (e) => {
    // Toggle user list when clicking the chevron or user count
    if (
      e.target.closest(".toggle-user-list") ||
      e.target.closest("#user-count")
    ) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById("user-list").classList.toggle("show");
    }

    // Close user list when clicking X
    if (e.target.closest(".close-user-list")) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById("user-list").classList.remove("show");
    }
  });

  // Close when clicking outside
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".online-users") && !e.target.closest("#user-list")) {
      document.getElementById("user-list").classList.remove("show");
    }
  });
});
