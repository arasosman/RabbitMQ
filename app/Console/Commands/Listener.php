<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Listener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbit:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ErrorException
     * @throws \Exception
     */
    public function handle()
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'admin', '123456');
        $channel = $connection->channel();
        $channel->queue_declare(
            $queue = 'hello',
            $passive = false,
            $durable = true,
            $exclusive = false,
            $auto_delete = false,
            $nowait = false,
            $arguments = null,
            $ticket = null
        );

        $channel->basic_qos(null, 1, null);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $channel->basic_consume(
            $queue = 'hello',
            $consumer_tag = '',
            $no_local = false,
            $no_ack = false,
            $exclusive = false,
            $nowait = false,
            function ($msg) {
                echo " [x] Received ", $msg->body, "\n";
                $job = json_decode($msg->body, $assocForm = true);
                dump($job);
                sleep($job['sleep_period']);
                echo " [x] Done", "\n";
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );


        while (count($channel->callbacks))
        {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
