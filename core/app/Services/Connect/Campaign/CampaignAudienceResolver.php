<?php
namespace App\Services\Connect\Campaign;

use App\Models\Connect\ConnectSegment;
use App\Services\Connect\SegmentEngine;

class CampaignAudienceResolver
{
    protected $segmentEngine;

    public function __construct(SegmentEngine $segmentEngine)
    {
        $this->segmentEngine = $segmentEngine;
    }

    public function getQuery(ConnectSegment $segment)
    {
        return $this->segmentEngine->buildQuery($segment->merchant_id, $segment->rules);
    }

    public function getChunked(ConnectSegment $segment, int $chunkSize = 1000)
    {
        $query = $this->getQuery($segment);
        // Uses lazyById for high performance iterating over millions of primary keys safely
        return $query->lazyById($chunkSize);
    }
}
