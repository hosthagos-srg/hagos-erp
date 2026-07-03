<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditObserver
{
    /** Field yang tidak perlu dicatat di log. */
    private array $hidden = ['created_at', 'updated_at', 'remember_token', 'password'];

    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $this->clean($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->clean($model->getChanges());
        if (empty($changes)) {
            return; // tidak ada perubahan berarti (cuma timestamp)
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->write($model, 'updated', $old, $changes);
    }

    /** Pakai 'deleting' agar isi baris ter-snapshot SEBELUM hilang (tahan hard delete). */
    public function deleting(Model $model): void
    {
        $this->write($model, 'deleted', $this->clean($model->getAttributes()), null);
    }

    private function clean(array $attributes): array
    {
        return collect($attributes)->except($this->hidden)->all();
    }

    private function write(Model $model, string $action, ?array $old, ?array $new): void
    {
        $console = app()->runningInConsole();

        AuditLog::create([
            'user_id'        => Auth::id(),
            'user_name'      => Auth::user()?->name,
            'action'         => $action,
            'auditable_type' => $model::class,
            'auditable_id'   => (string) $model->getKey(),
            'old_values'     => $old,
            'new_values'     => $new,
            'url'            => $console ? null : Request::fullUrl(),
            'ip_address'     => $console ? null : Request::ip(),
            'created_at'     => now(),
        ]);
    }
}
