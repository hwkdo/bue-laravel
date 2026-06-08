<?php

declare(strict_types=1);

use Hwkdo\BueLaravel\Enums\FormwerkVorgangFieldState;
use Hwkdo\BueLaravel\Support\FormwerkVorgangsnummerResolver;

beforeEach(function () {
    $this->resolver = new FormwerkVorgangsnummerResolver;
});

it('classifies empty values', function (mixed $value) {
    expect($this->resolver->classify($value))->toBe(FormwerkVorgangFieldState::Empty);
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace' => ['   '],
]);

it('classifies gewerbeamt uuids', function (string $uuid) {
    expect($this->resolver->classify($uuid))->toBe(FormwerkVorgangFieldState::GewerbeamtUuid)
        ->and($this->resolver->isVorgangsnummer($uuid))->toBeFalse();
})->with([
    'lowercase' => ['550e8400-e29b-41d4-a716-446655440000'],
    'uppercase' => ['550E8400-E29B-41D4-A716-446655440000'],
]);

it('classifies numeric vorgangsnummern', function (mixed $vorgangsnummer) {
    expect($this->resolver->classify($vorgangsnummer))->toBe(FormwerkVorgangFieldState::Vorgangsnummer)
        ->and($this->resolver->isVorgangsnummer($vorgangsnummer))->toBeTrue();
})->with([
    'string' => ['1234567'],
    'integer' => [1234567],
]);

it('resolves vorgangsnummern with legacy priority', function (
    mixed $gewerbeamtuuid,
    mixed $formwerkvgn,
    ?string $expected,
) {
    expect($this->resolver->resolve($gewerbeamtuuid, $formwerkvgn))->toBe($expected);
})->with([
    'legacy only' => ['1234567', null, '1234567'],
    'formwerk only' => [null, '7654321', '7654321'],
    'legacy before formwerk' => ['1234567', '7654321', '1234567'],
    'uuid with formwerk fallback' => ['550e8400-e29b-41d4-a716-446655440000', '7654321', '7654321'],
    'uuid without formwerk' => ['550e8400-e29b-41d4-a716-446655440000', null, null],
    'both empty' => [null, null, null],
]);
