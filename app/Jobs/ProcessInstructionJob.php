<?php

namespace App\Jobs;

use App\Models\Instruction;
use App\Services\InstructionProcessor;
use App\Services\OpenRouterParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessInstructionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $instructionId)
    {
    }

    public function handle(OpenRouterParser $parser): void
    {
        $instruction = Instruction::findOrFail($this->instructionId);

        app(InstructionProcessor::class)->process($instruction);
    }
}
