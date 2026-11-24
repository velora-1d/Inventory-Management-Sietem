<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingForm extends Component
{
    use WithFileUploads;

    public ?string $key = null;
    public ?string $value = null;
    public string $label = '';
    public $logoUpload = null;

    public function rules(): array
    {
        if ($this->key === 'brand_logo') {
            return [
                'logoUpload' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
                'value' => ['nullable', 'string'],
            ];
        }

        return [
            'value' => ['nullable', 'string'],
        ];
    }

    #[On('edit-setting')]
    public function edit(string $key): void
    {
        $this->resetValidation();
        $this->reset('logoUpload');

        $setting = Setting::findOrFail($key);

        $this->key = $setting->key;
        $this->value = $setting->value;
        $this->label = Str::title(str_replace('_', ' ', $setting->key));

        $this->dispatch('open-modal', name: 'setting-form-modal');
    }

    public function resetLogo(AuditService $auditService): void
    {
        if ($this->key !== 'brand_logo') {
            return;
        }

        $oldValue = Setting::get('brand_logo');
        if ($oldValue && !str_starts_with($oldValue, 'http') && Storage::disk('public')->exists($oldValue)) {
            Storage::disk('public')->delete($oldValue);
        }

        Setting::set('brand_logo', '');
        $this->value = '';
        $this->reset('logoUpload');

        $auditService->log(
            action: 'setting.updated',
            before: ['key' => 'brand_logo', 'value' => $oldValue],
            after: ['key' => 'brand_logo', 'value' => ''],
            reason: 'Reset brand logo to default SVG'
        );

        $this->dispatch('close-modal', name: 'setting-form-modal');
        $this->dispatch('pg:eventRefresh-setting-table');
        $this->dispatch('toast', message: 'Logo berhasil dikembalikan ke default.', type: 'success');
    }

    public function save(AuditService $auditService): void
    {
        $this->validate();

        try {
            $setting = Setting::find($this->key);
            $oldValue = $setting?->value;
            $newValue = $this->value;

            if ($this->key === 'brand_logo' && $this->logoUpload) {
                if ($oldValue && !str_starts_with($oldValue, 'http') && Storage::disk('public')->exists($oldValue)) {
                    Storage::disk('public')->delete($oldValue);
                }

                $newValue = $this->logoUpload->store('branding', 'public');
            }

            Setting::set((string) $this->key, $newValue);

            $isSecret = Str::endsWith((string) $this->key, ['_key', '_secret', '_token']);
            $auditService->log(
                action: 'setting.updated',
                entity: $setting,
                before: ['key' => $this->key, 'value' => $isSecret && $oldValue ? '[REDACTED]' : $oldValue],
                after: ['key' => $this->key, 'value' => $isSecret && $newValue ? '[REDACTED]' : $newValue],
            );

            $this->reset('logoUpload');
            $this->dispatch('close-modal', name: 'setting-form-modal');
            $this->dispatch('pg:eventRefresh-setting-table');
            $this->dispatch('toast', message: 'Setting updated successfully.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to update setting: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.settings.setting-form');
    }
}
