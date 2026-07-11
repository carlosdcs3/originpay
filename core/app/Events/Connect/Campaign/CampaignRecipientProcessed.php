<?php
namespace App\Events\Connect\Campaign;
use Illuminate\Queue\SerializesModels;
class CampaignRecipientProcessed { use SerializesModels; public $execution; public function __construct($execution) { $this->execution = $execution; } }
