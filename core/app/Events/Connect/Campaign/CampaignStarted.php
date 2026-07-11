<?php
namespace App\Events\Connect\Campaign;
use Illuminate\Queue\SerializesModels;
class CampaignStarted { use SerializesModels; public $campaign; public function __construct($campaign) { $this->campaign = $campaign; } }
