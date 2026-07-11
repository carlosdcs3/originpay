<?php
namespace App\Events\Connect\Campaign;
use Illuminate\Queue\SerializesModels;
class CampaignCancelled { use SerializesModels; public $campaign; public function __construct($campaign) { $this->campaign = $campaign; } }
