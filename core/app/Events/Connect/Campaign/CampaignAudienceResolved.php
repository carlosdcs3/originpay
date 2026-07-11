<?php
namespace App\Events\Connect\Campaign;
use Illuminate\Queue\SerializesModels;
class CampaignAudienceResolved { use SerializesModels; public $execution; public function __construct($execution) { $this->execution = $execution; } }
