<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use App\Services\InstructionProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructionController extends Controller
{
    public function store(Request $request, InstructionProcessor $processor): RedirectResponse
    {
        $data = $request->validate([
            'raw_text' => ['required', 'string', 'max:5000'],
        ]);

        $instruction = Instruction::create([
            'source' => 'web',
            'platform' => 'web',
            'raw_text' => $data['raw_text'],
            'status' => Instruction::STATUS_PROCESSING,
        ]);

        $instruction = $processor->process($instruction);

        return redirect()
            ->route('instructions.show', $instruction)
            ->with('status', $instruction->status === Instruction::STATUS_SUCCESS
                ? 'Instruksi berhasil diproses.'
                : 'Instruksi gagal diproses. Cek detail error di bawah.')
            ->with('status_variant', $instruction->status === Instruction::STATUS_SUCCESS ? 'success' : 'danger');
    }

    public function storeVoice(Request $request, InstructionProcessor $processor): RedirectResponse
    {
        $data = $request->validate([
            'voice' => ['required', 'file', 'max:15360', 'mimes:wav,mp3,m4a,ogg,flac,aac,webm'],
        ]);

        $audio = $data['voice'];

        $instruction = Instruction::create([
            'source' => 'web_voice',
            'platform' => 'web',
            'raw_text' => 'Voice input: '.$audio->getClientOriginalName(),
            'status' => Instruction::STATUS_PROCESSING,
        ]);

        $instruction = $processor->processAudio($instruction, $audio);

        return redirect()
            ->route('instructions.show', $instruction)
            ->with('status', $instruction->status === Instruction::STATUS_SUCCESS
                ? 'Instruksi suara berhasil diproses.'
                : 'Instruksi suara gagal diproses. Cek detail error di bawah.')
            ->with('status_variant', $instruction->status === Instruction::STATUS_SUCCESS ? 'success' : 'danger');
    }

    public function show(Instruction $instruction): View
    {
        return view('instructions.show', [
            'instruction' => $instruction->load('transaction.customer'),
        ]);
    }

    public function retry(Instruction $instruction, InstructionProcessor $processor): RedirectResponse
    {
        abort_unless($instruction->status === Instruction::STATUS_FAILED, 409);

        $instruction->update([
            'status' => Instruction::STATUS_PROCESSING,
            'error_message' => null,
            'processed_at' => null,
        ]);

        $instruction = $processor->process($instruction);

        return redirect()
            ->route('instructions.show', $instruction)
            ->with('status', $instruction->status === Instruction::STATUS_SUCCESS
                ? 'Retry berhasil diproses.'
                : 'Retry gagal. Cek detail error terbaru.')
            ->with('status_variant', $instruction->status === Instruction::STATUS_SUCCESS ? 'success' : 'danger');
    }
}
