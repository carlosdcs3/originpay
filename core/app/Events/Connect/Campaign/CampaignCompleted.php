<?php
namespace App\Events\Connect\Campaign;
use Illuminate\Queue\SerializesModels;
class CampaignCompleted { use SerializesModels; public $campaign; public function __construct($campaign) { $this->campaign = $campaign; } }
