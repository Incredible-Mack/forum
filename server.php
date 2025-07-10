<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/helpers.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;
    protected $users;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
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
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['type'])) {
            echo "Invalid JSON data received\n";
            return;
        }

        try {
            switch ($data['type']) {
                case 'join':
                    $this->handleJoin($from, $data);
                    break;

                case 'message':
                    $this->handleMessage($from, $data);
                    break;

                case 'file':
                    $this->handleFile($from, $data);
                    break;

                default:
                    echo "Unknown message type: {$data['type']}\n";
            }
        } catch (Exception $e) {
            echo "Error processing message: " . $e->getMessage() . "\n";
        }
    }

    protected function handleJoin(ConnectionInterface $from, array $data)
    {
        if (!isset($data['user_id'], $data['username'], $data['room_id'])) {
            throw new Exception("Missing required join data");
        }

        $this->users[$from->resourceId] = [
            'id' => (int)$data['user_id'],
            'name' => trim($data['username']),
            'room_id' => (int)$data['room_id']
        ];

        $roomId = $this->users[$from->resourceId]['room_id'];

        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = new \SplObjectStorage;
        }

        $this->rooms[$roomId]->attach($from);

        // Load previous messages
        $messages = $this->get_room_messages($roomId);
        $from->send(json_encode([
            'type' => 'history',
            'messages' => $messages
        ]));

        // Notify others in the room
        $this->broadcastToRoom($roomId, json_encode([
            'type' => 'join',
            'user' => $this->users[$from->resourceId]['name'],
            'users' => $this->get_room_users($roomId)
        ]), $from);
    }




    protected function handleMessage(ConnectionInterface $from, array $data)
    {
        if (!isset($data['text']) || !isset($this->users[$from->resourceId])) {
            throw new Exception("Invalid message data");
        }

        $user = $this->users[$from->resourceId];
        $messageData = [
            'room_id' => $user['room_id'],
            'user_id' => $user['id'],
            'message' => $data['text'],
            'is_file' => false
        ];

        // Changed to use messages.php with POST
        $messageResponse = $this->callApi('POST', 'messages.php', $messageData);

        if (!$messageResponse || !isset($messageResponse['id'])) {
            throw new Exception("Failed to save message");
        }

        $this->broadcastToRoom($user['room_id'], json_encode([
            'type' => 'message',
            'id' => $messageResponse['id'],
            'user' => $user['name'],
            'user_id' => $user['id'],
            'text' => $data['text'],
            'time' => date('H:i'),
            'is_file' => false
        ]));
    }

    protected function handleFile(ConnectionInterface $from, array $data)
    {
        if (!isset($data['message_id']) || !isset($this->users[$from->resourceId])) {
            throw new Exception("Invalid file data");
        }

        $user = $this->users[$from->resourceId];

        // Changed to use singlemessage.php with GET parameter
        $file_message = $this->callApi('GET', 'singlemessage.php?id=' . $data['message_id']);

        if (!$file_message) {
            throw new Exception("File message not found");
        }

        $this->broadcastToRoom($user['room_id'], json_encode([
            'type' => 'message',
            'id' => $file_message['id'],
            'user' => $file_message['username'],
            'user_id' => $file_message['user_id'],
            'text' => $file_message['message'],
            'file_path' => $file_message['file_path'],
            'time' => date('H:i', strtotime($file_message['created_at'])),
            'is_file' => true
        ]));
    }

    protected function get_room_messages($room_id, $limit = 50)
    {
        // Changed to use messages.php with GET parameters
        $response = $this->callApi('GET', "messages.php?room_id=$room_id&limit=$limit");
        return $response['messages'] ?? [];
    }

    protected function callApi($method, $endpoint, $data = [])
    {
        $baseUrl = 'http://parentforum.lovetoons.org/api/';
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FAILONERROR => true
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            echo "API Error: $error\n";
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }

        echo "API returned HTTP $httpCode\n";
        return false;
    }


    public function onClose(ConnectionInterface $conn)
    {
        if (!isset($this->users[$conn->resourceId])) {
            $this->clients->detach($conn);
            return;
        }

        $user = $this->users[$conn->resourceId];
        $room_id = $user['room_id'];

        if (isset($this->rooms[$room_id])) {
            $this->rooms[$room_id]->detach($conn);

            $this->broadcastToRoom($room_id, json_encode([
                'type' => 'leave',
                'user' => $user['name'],
                'users' => $this->get_room_users($room_id)
            ]), $conn);
        }

        unset($this->users[$conn->resourceId]);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastToRoom($room_id, $message, $exclude = null)
    {
        if (!isset($this->rooms[$room_id])) {
            return;
        }

        foreach ($this->rooms[$room_id] as $client) {
            if ($client !== $exclude) {
                $client->send($message);
            }
        }
    }

    protected function get_room_users($room_id)
    {
        $users = [];
        if (isset($this->rooms[$room_id])) {
            foreach ($this->rooms[$room_id] as $client) {
                if (isset($this->users[$client->resourceId])) {
                    $users[] = $this->users[$client->resourceId]['name'];
                }
            }
        }
        return array_unique($users);
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

echo "Chat server running at ws://localhost:8080\n";
$server->run();
