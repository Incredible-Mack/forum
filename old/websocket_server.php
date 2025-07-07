<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if ($data['type'] === 'auth') {
            // Authenticate user with token (you should implement proper token validation)
            $this->userConnections[$data['user_id']] = $from;
            echo "User {$data['user_id']} authenticated\n";
            return;
        }

        if ($data['type'] === 'message') {
            // Broadcast message to all clients in the same thread
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode([
                        'type' => 'message',
                        'thread_id' => $data['thread_id'],
                        'user_id' => $data['user_id'],
                        'username' => $data['username'],
                        'message' => $data['message'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]));
                }
            }

            // Save message to database
            global $conn;
            $stmt = $conn->prepare("INSERT INTO messages (thread_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $data['thread_id'], $data['user_id'], $data['message']);
            $stmt->execute();
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        // Remove from user connections
        if ($userId = array_search($conn, $this->userConnections)) {
            unset($this->userConnections[$userId]);
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Run the server
$server = IoServer::factory(
    new WsServer(new Chat()),
    8080
);

echo "WebSocket server running on port 8080\n";
$server->run();