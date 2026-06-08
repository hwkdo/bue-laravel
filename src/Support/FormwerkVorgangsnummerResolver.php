<?php

declare(strict_types=1);

namespace Hwkdo\BueLaravel\Support;

use Hwkdo\BueLaravel\Enums\FormwerkVorgangFieldState;

final class FormwerkVorgangsnummerResolver
{
    public function classify(mixed $value): FormwerkVorgangFieldState
    {
        if ($value === null || trim((string) $value) === '') {
            return FormwerkVorgangFieldState::Empty;
        }

        if ($this->isGewerbeamtUuid((string) $value)) {
            return FormwerkVorgangFieldState::GewerbeamtUuid;
        }

        return FormwerkVorgangFieldState::Vorgangsnummer;
    }

    public function isVorgangsnummer(mixed $value): bool
    {
        return $this->classify($value) === FormwerkVorgangFieldState::Vorgangsnummer;
    }

    public function resolve(mixed $gewerbeamtuuid, mixed $formwerkvgn): ?string
    {
        if ($this->isVorgangsnummer($gewerbeamtuuid)) {
            return trim((string) $gewerbeamtuuid);
        }

        if ($this->isVorgangsnummer($formwerkvgn)) {
            return trim((string) $formwerkvgn);
        }

        return null;
    }

    private function isGewerbeamtUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            trim($value)
        ) === 1;
    }
}
