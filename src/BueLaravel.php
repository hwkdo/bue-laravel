<?php

declare(strict_types=1);

namespace Hwkdo\BueLaravel;

use Hwkdo\BueLaravel\Support\FormwerkVorgangsnummerResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BueLaravel
{
    public function __construct(
        private readonly ?string $connectionName = null,
        private readonly FormwerkVorgangsnummerResolver $vorgangsnummerResolver = new FormwerkVorgangsnummerResolver,
    ) {}

    public function using(string $connectionName): self
    {
        return new self($connectionName, $this->vorgangsnummerResolver);
    }

    public function connection(): string
    {
        return $this->connectionName ?? config('bue-laravel.database.connection');
    }

    public function table(string $table): Builder
    {
        return DB::connection($this->connection())->table($table);
    }

    public function getFachbereiche()
    {
        return $this->table('intranet.v_fachbereiche')
            ->select('*')
            ->get();
    }

    public function getGewerke()
    {
        return $this->table('intranet.v_gewerbe')
            ->select('*')
            ->get();
    }

    public function getEintragungsvorraussetzungen()
    {
        return $this->table('intranet.v_eintragungsvoraussetzung')
            ->select('*')
            ->get();
    }

    public function getRechtsformen()
    {
        return $this->table('intranet.v_rechtsform')
            ->select('*')
            ->get();
    }

    public function getBetriebe()
    {
        return $this->table('intranet.betr_stamm')
            ->select('*')
            ->get();
    }

    public function getBetriebByBetriebsnr($betriebsnr)
    {
        return $this->table('intranet.betr_stamm')
            ->select('*')
            ->where('bnr', $betriebsnr)
            ->first();
    }

    public function getBetriebsnrByVorgangsnummer(int|string $vorgangsnummer): int|string|null
    {
        $legacyMatch = $this->table('intranet.betr_stamm')
            ->select('bnr', 'gewerbeamtuuid')
            ->where('gewerbeamtuuid', $vorgangsnummer)
            ->first();

        if ($legacyMatch !== null && $this->vorgangsnummerResolver->isVorgangsnummer($legacyMatch->gewerbeamtuuid)) {
            return $legacyMatch->bnr;
        }

        $formwerkMatch = $this->table('intranet.betr_stamm')
            ->select('bnr')
            ->where('formwerkvgn', $vorgangsnummer)
            ->first();

        return $formwerkMatch?->bnr;
    }

    public function getVorgangsnummerByBetriebsnr(int|string $betriebsnr): ?string
    {
        $data = $this->table('intranet.betr_stamm')
            ->select('gewerbeamtuuid', 'formwerkvgn')
            ->where('bnr', $betriebsnr)
            ->first();

        if ($data === null) {
            return null;
        }

        return $this->vorgangsnummerResolver->resolve($data->gewerbeamtuuid, $data->formwerkvgn);
    }

    public function getRaumById($id)
    {
        return $this->table('intranet.v_raumliste')
            ->select('*')
            ->where('id', $id)
            ->first();
    }

    public function getLieferantByNummer(string $nummer): ?object
    {
        return $this->table('Intranet.MV_HWKDO_Lieferanten')
            ->select('lieferantenname', 'lieferantennummer')
            ->where('lieferantennummer', $nummer)
            ->first();
    }

    public function getLieferanten(string $search = ''): Collection
    {
        return $this->table('Intranet.MV_HWKDO_Lieferanten')
            ->select('lieferantenname', 'lieferantennummer')
            ->when($search, fn ($q) => $q->whereRaw('LOWER(lieferantenname) LIKE ?', ['%'.strtolower($search).'%']))
            ->distinct()
            ->orderBy('lieferantenname')
            ->limit(100)
            ->get();
    }

    /**
     * Liefert alle Lieferanten mit allen verfügbaren Stammdaten-Spalten
     * (lieferantenstrasse, lieferantenhausnummer, lieferantenplz, lieferantenort, lieferanteniban …).
     * Für Sync-Jobs gedacht (kein Limit).
     */
    public function getAllLieferanten(): Collection
    {
        return $this->table('Intranet.MV_HWKDO_Lieferanten')
            ->select('*')
            ->orderBy('lieferantenname')
            ->get();
    }

    /**
     * Liefert alle Kostenstellen (read-only) aus der bue-laravel-Connection.
     * Spalten in der Quelle: kostenstelle, kobe (= Bezeichnung) u. a.
     */
    public function getKostenstellen(string $search = ''): Collection
    {
        return $this->table('Intranet.HWKDO_Kostenstellen')
            ->select('*')
            ->when($search, fn ($q) => $q->whereRaw('LOWER(kobe) LIKE ?', ['%'.strtolower($search).'%']))
            ->orderBy('kostenstelle')
            ->get();
    }

    /**
     * Liefert eine Kostenstelle anhand der Nummer (oder null, falls nicht vorhanden).
     */
    public function getKostenstelleByNummer(string $nummer): ?object
    {
        return $this->table('Intranet.HWKDO_Kostenstellen')
            ->select('*')
            ->where('kostenstelle', $nummer)
            ->first();
    }
}
