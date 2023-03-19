<?php
namespace App\MessageHandler;

use App\Messages\SeriesWasCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LogNewSeriesHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SeriesWasCreated $message)
    {
        $this->logger->info("A new series was created", [ 
            "series" => [
                "seriesName" => $message->series->getName()
            ] 
        ]);
    }
}
