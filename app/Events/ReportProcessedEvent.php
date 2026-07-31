<?php

namespace App\Events;

use App\Models\ProcessedFile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportProcessedEvent {
    use Dispatchable, SerializesModels;
    public $processedFile;

    public function __construct(ProcessedFile $processedFile) {
        $this->processedFile = $processedFile;
    }
}