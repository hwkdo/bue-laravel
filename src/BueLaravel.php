<?php

declare(strict_types=1);

namespace Hwkdo\BueLaravel;

use Hwkdo\BueLaravel\Support\FormwerkVorgangsnummerResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BueLaravel
{
    public function __construct(
        private readonly FormwerkVorgangsnummerResolver $vorgangsnummerResolver = new FormwerkVorgangsnummerResolver,
    ) {}

    public function getFachbereiche()
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.v_fachbereiche')
            ->select('*')
            ->get();
    }

    public function getGewerke()
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.v_gewerbe')
            ->select('*')
            ->get();
    }

    public function getEintragungsvorraussetzungen()
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.v_eintragungsvoraussetzung')
            ->select('*')
            ->get();
    }

    public function getRechtsformen()
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.v_rechtsform')
            ->select('*')
            ->get();
    }

    public function getBetriebe()
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.betr_stamm')
            ->select('*')
            ->get();
    }

    public function getBetriebByBetriebsnr($betriebsnr)
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.betr_stamm')
            ->select('*')
            ->where('bnr', $betriebsnr)
            ->first();
    }

    public function getBetriebsnrByVorgangsnummer(int|string $vorgangsnummer): int|string|null
    {
        $connection = config('bue-laravel.database.connection');

        $legacyMatch = DB::connection($connection)
            ->table('intranet.betr_stamm')
            ->select('bnr', 'gewerbeamtuuid')
            ->where('gewerbeamtuuid', $vorgangsnummer)
            ->first();

        if ($legacyMatch !== null && $this->vorgangsnummerResolver->isVorgangsnummer($legacyMatch->gewerbeamtuuid)) {
            return $legacyMatch->bnr;
        }

        $formwerkMatch = DB::connection($connection)
            ->table('intranet.betr_stamm')
            ->select('bnr')
            ->where('formwerkvgn', $vorgangsnummer)
            ->first();

        return $formwerkMatch?->bnr;
    }

    public function getVorgangsnummerByBetriebsnr(int|string $betriebsnr): ?string
    {
        $data = DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.betr_stamm')
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
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('intranet.v_raumliste')
            ->select('*')
            ->where('id', $id)
            ->first();
    }

    public function getLieferantByNummer(string $nummer): ?object
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('Intranet.MV_HWKDO_Lieferanten')
            ->select('lieferantenname', 'lieferantennummer')
            ->where('lieferantennummer', $nummer)
            ->first();
    }

    public function getLieferanten(string $search = ''): Collection
    {
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('Intranet.MV_HWKDO_Lieferanten')
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
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('Intranet.MV_HWKDO_Lieferanten')
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
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('Intranet.HWKDO_Kostenstellen')
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
        return DB::connection(config('bue-laravel.database.connection'))
            ->table('Intranet.HWKDO_Kostenstellen')
            ->select('*')
            ->where('kostenstelle', $nummer)
            ->first();
    }
}
