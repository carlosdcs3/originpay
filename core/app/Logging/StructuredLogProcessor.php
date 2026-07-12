<?php

namespace App\Logging;

use App\Support\Observability\LogRedactor;
use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Monolog\LogRecord;

class StructuredLogProcessor
{
    public function __construct(private readonly LogRedactor $redactor) {}

    public function __invoke(Logger|LogManager $logger): void
    {
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            $context = $record->context;

            $extra = $record->extra;

            foreach (Context::all() as $key => $value) {
                unset($extra[$key]);

                if ($value !== null && $value !== '' && ! array_key_exists($key, $context)) {
                    $context[$key] = $value;
                }
            }

            $context['timestamp'] ??= $record->datetime->format(DATE_ATOM);

            return $record->with(
                context: $this->redactor->redact($context),
                extra: $this->redactor->redact($extra),
            );
        });
    }
}
