<?php

namespace App\Livewire\Admin;

use App\Models\Auditoria;
use Livewire\Component;

class AuditoriaModal extends Component
{
    public $show = false;
    public $log = null;

    protected $listeners = ['openAuditoriaModal'];

    public function openAuditoriaModal(int $logId): void
    {
        $this->show = true;

        $log = Auditoria::with('usuario')->find($logId);

        $this->log = [
            'id'      => $log->id,
            'usuario' => $log->usuario->name ?? 'Sistema',
            'tabla'   => $log->tabla,
            'accion'  => $log->accion,
            'fecha'   => \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
            'cambios' => $log->cambios,
        ];
    }

    public function close(): void
    {
        $this->reset(['show', 'log']);
    }

    public function render()
    {
        return view('livewire.admin.auditoria-modal');
    }
}
