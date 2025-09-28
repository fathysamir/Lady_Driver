<?php
namespace App\Console\Commands;

use App\WebSockets\Chat;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
//use MyApp\Chat;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebSocketServer extends Command
{
    protected $signature   = 'websockets:init';
    protected $description = 'Start the WebSocket server';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {$loop = Loop::get();

        $chat = new Chat($loop);

        $socket = new SocketServer('0.0.0.0:8080', [], $loop);

        $server = new IoServer(
            new HttpServer(
                new WsServer($chat)
            ),
            $socket,
            $loop
        );

        $this->info('🚀 WebSocket server started on port 8080');

        $loop->run();}
}
