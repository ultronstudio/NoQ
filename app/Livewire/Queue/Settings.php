<?php

namespace App\Livewire\Queue;

use App\Models\VirtualQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

/**
 * Modal form letting a queue supervisor (or super admin) edit a
 * VirtualQueue's own settings: name, description, and an optional cap on
 * how many entries may wait at once (enforced by App\Actions\Queue\JoinQueue).
 */
class Settings extends Component
{
    public int $virtualQueueId;

    public string $name = '';

    public string $description = '';

    public ?int $maxWaiting = null;

    public function mount(VirtualQueue $virtualQueue): void
    {
        $this->authorize('update', $virtualQueue);

        $this->virtualQueueId = $virtualQueue->id;
        $this->name = $virtualQueue->name;
        $this->description = $virtualQueue->description ?? '';
        $this->maxWaiting = $virtualQueue->max_waiting;
    }

    public function save(): RedirectResponse
    {
        $virtualQueue = $this->virtualQueue();

        $this->authorize('update', $virtualQueue);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'maxWaiting' => ['nullable', 'integer', 'min:1'],
        ], [
            'name.required' => 'Zadejte název fronty.',
            'name.max' => 'Název smí mít nejvýše 255 znaků.',
            'description.max' => 'Popis smí mít nejvýše 1000 znaků.',
            'maxWaiting.integer' => 'Maximální počet čekajících musí být celé číslo.',
            'maxWaiting.min' => 'Maximální počet čekajících musí být alespoň 1.',
        ]);

        $virtualQueue->update([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'max_waiting' => $data['maxWaiting'],
        ]);

        return redirect()->route('queue.show', $virtualQueue);
    }

    private function virtualQueue(): VirtualQueue
    {
        return VirtualQueue::query()->findOrFail($this->virtualQueueId);
    }

    public function render(): View
    {
        return view('livewire.queue.settings');
    }
}
