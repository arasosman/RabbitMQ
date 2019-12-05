<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publisher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbit:publish';

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
        $msg = new AMQPMessage('Hello World!');
        $channel->basic_publish($msg, '', 'hello');

        $job_id=0;
        while (true)
        {
            $jobArray = array(
                'id' => $job_id++,
                'task' => 'sleep',
                'sleep_period' => rand(0, 3)
            );

            $msg = new AMQPMessage(
                json_encode($jobArray, JSON_UNESCAPED_SLASHES),
                array('delivery_mode' => 2) # make message persistent
            );

            $channel->basic_publish($msg, '', 'hello');
            print 'Job created' . PHP_EOL;
            usleep(1000000);
        }


        $channel->close();
        $connection->close();
    }
}
