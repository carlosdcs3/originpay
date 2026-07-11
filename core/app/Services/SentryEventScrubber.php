<?php

namespace App\Services;

use App\Helpers\MaskHelper;

class SentryEventScrubber
{
    /**
     * Applies masking to the Sentry Event before it is sent to the network.
     */
    public static function scrub(\Sentry\Event $event): ?\Sentry\Event
    {
        $request = $event->getRequest();

        if ($request) {
            $data = $request['data'] ?? [];
            if (is_array($data)) {
                $request['data'] = MaskHelper::maskSensitiveData($data);
            } elseif (is_string($data)) {
                $request['data'] = MaskHelper::maskString($data);
            }

            $headers = $request['headers'] ?? [];
            if (is_array($headers)) {
                $request['headers'] = MaskHelper::maskSensitiveData($headers);
            }

            $event->setRequest($request);
        }

        return $event;
    }
}
