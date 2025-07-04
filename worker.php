<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Services\TelegramService;

new Application();

$db = Database::getInstance();

if (!$db) {
    echo "Failed to connect to the database. Exiting worker.\n";
    exit(1);
}

$jobRepo = new JobRepository($db);
$userRepo = new UserRepository($db);
$telegram = new TelegramService($_ENV['BOT_TOKEN']);

echo "Worker started successfully. Waiting for jobs...\n";

while (true) {
    $job = $jobRepo->getNextPendingJob();

    if ($job) {
        echo "Processing job ID: {$job['id']} of type '{$job['job_type']}'\n";
        $jobRepo->updateStatus($job['id'], 'processing');

        try {
            if ($job['job_type'] === 'broadcast_message') {
                $payload = json_decode($job['payload'], true);
                $userIds = $userRepo->getAllUserIds();

                foreach ($userIds as $userId) {
                    $telegram->sendMessage((int) $userId, $payload['text']);
                    usleep(50000); 
                }
            }

            $jobRepo->updateStatus($job['id'], 'completed');
            echo "Job ID: {$job['id']} completed successfully.\n";
        } catch (Throwable $e) {
            $jobRepo->updateStatus($job['id'], 'failed');
            error_log("Job ID {$job['id']} failed: " . $e->getMessage());
        }
    } else {
        sleep(5);
    }
}