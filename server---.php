<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';
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
        $conn = get_db_connection();

        switch ($data['type']) {
            case 'join':
                // Join a room
                $this->users[$from->resourceId] = [
                    'id' => $data['user_id'],
                    'name' => $data['username'],
                    'room_id' => $data['room_id']
                ];

                if (!isset($this->rooms[$data['room_id']])) {
                    $this->rooms[$data['room_id']] = new \SplObjectStorage;
                }

                $this->rooms[$data['room_id']]->attach($from);

                // Load previous messages
                $messages = $this->get_room_messages($data['room_id']);
                $from->send(json_encode([
                    'type' => 'history',
                    'messages' => $messages
                ]));

                // Notify others in the room
                $this->broadcastToRoom($data['room_id'], json_encode([
                    'type' => 'join',
                    'user' => $data['username'],
                    'users' => $this->get_room_users($data['room_id'])
                ]), $from);
                break;

            case 'message':
                // Save message to database
                $user = $this->users[$from->resourceId];
                $message_text = mysqli_real_escape_string($conn, $data['text']);

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO messages (room_id, user_id, message) 
                     VALUES (?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "iis", $user['room_id'], $user['id'], $message_text);
                mysqli_stmt_execute($stmt);
                $message_id = mysqli_insert_id($conn);

                // Broadcast to room
                $this->broadcastToRoom($user['room_id'], json_encode([
                    'type' => 'message',
                    'id' => $message_id,
                    'user' => $user['name'],
                    'user_id' => $user['id'],
                    'text' => $data['text'],
                    'time' => date('H:i'),
                    'is_file' => false
                ]));
                break;

            case 'file':
                // File upload notification
                $user = $this->users[$from->resourceId];
                $file_message = get_file_message($data['message_id']);

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
                break;
        }

        mysqli_close($conn);
    }

    public function onClose(ConnectionInterface $conn)
    {
        if (isset($this->users[$conn->resourceId])) {
            $user = $this->users[$conn->resourceId];
            $room_id = $user['room_id'];

            if (isset($this->rooms[$room_id])) {
                $this->rooms[$room_id]->detach($conn);

                // Notify others in the room
                $this->broadcastToRoom($room_id, json_encode([
                    'type' => 'leave',
                    'user' => $user['name'],
                    'users' => $this->get_room_users($room_id)
                ]), $conn);
            }

            unset($this->users[$conn->resourceId]);
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastToRoom($room_id, $message, $exclude = null)
    {
        if (isset($this->rooms[$room_id])) {
            foreach ($this->rooms[$room_id] as $client) {
                if ($client !== $exclude) {
                    $client->send($message);
                }
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

    protected function get_room_messages($room_id, $limit = 50)
    {
        $conn = get_db_connection();
        $stmt = mysqli_prepare(
            $conn,
            "SELECT m.*, u.username 
             FROM messages m 
             JOIN users u ON m.user_id = u.id 
             WHERE m.room_id = ? 
             ORDER BY m.created_at DESC 
             LIMIT ?"
        );

        mysqli_stmt_bind_param($stmt, "ii", $room_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $messages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = [
                'id' => $row['id'],
                'user' => $row['username'],
                'user_id' => $row['user_id'],
                'text' => $row['message'],
                'time' => date('H:i', strtotime($row['created_at'])),
                'is_file' => (bool)$row['is_file'],
                'file_path' => $row['file_path']
            ];
        }

        mysqli_close($conn);
        return array_reverse($messages);
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
