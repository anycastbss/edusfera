<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalHeading('Удалить пользователя навсегда?')
                ->modalDescription('Аккаунт, анкета репетитора, подписки и расписания будут полностью удалены из базы данных.')
                ->using(function ($record): bool {
                    $record->tutorProfile()?->delete();
                    $record->subscription()?->delete();
                    $record->availability()->delete();
                    $record->tutorBalance()?->delete();
                    $record->studentBalance()?->delete();

                    return (bool) $record->forceDelete();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['new_password'])) {
            $data['password'] = Hash::make($data['new_password']);
        }
        unset($data['new_password']);

        return $data;
    }
}
