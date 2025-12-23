<?php

namespace Arbify\Logging;

use GuzzleHttp\Client;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class PapertrailHttpHandler extends AbstractProcessingHandler
{
    protected $client;
    protected $url;
    protected $token;

    public function __construct($url, $token, $level = \Monolog\Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        
        $this->url = $url;
        $this->token = $token;
        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 2,
        ]);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $this->client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'application/octet-stream',
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'body' => $this->formatMessage($record),
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
            // You can optionally log to local file here
        }
    }

    protected function formatMessage(LogRecord $record): string
    {
        $timestamp = $record->datetime->format('Y-m-d H:i:s');
        $level = $record->level->getName();
        $message = $record->message;
        
        // Include context if available
        $context = !empty($record->context) ? json_encode($record->context) : '';
        
        return sprintf(
            "[%s] %s.%s: %s %s",
            $timestamp,
            config('app.name'),
            $level,
            $message,
            $context
        );
    }
}
