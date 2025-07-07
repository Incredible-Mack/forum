<?php
require __DIR__ . '/vendor/autoload.php';


use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $users;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if ($data['type'] == 'join') {
            // New user joined
            $this->users[$from->resourceId] = $data['name'];
            echo "User {$data['name']} joined\n";

            // Notify all clients
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'join',
                    'user' => $data['name'],
                    'users' => array_values($this->users)
                ]));
            }
        } elseif ($data['type'] == 'message') {
            // New message received
            $user = $this->users[$from->resourceId] ?? 'Anonymous';
            echo "Message from {$user}: {$data['text']}\n";

            // Broadcast to all clients
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'message',
                    'user' => $user,
                    'text' => $data['text'],
                    'time' => date('H:i')
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        if (isset($this->users[$conn->resourceId])) {
            $user = $this->users[$conn->resourceId];
            unset($this->users[$conn->resourceId]);
            echo "User {$user} disconnected\n";

            // Notify all clients
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'leave',
                    'user' => $user,
                    'users' => array_values($this->users)
                ]));
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

echo "Server running at ws://localhost:8080\n";
$server->run();