<?php

declare(strict_types=1);

namespace Hwkdo\BueLaravel;

use Hwkdo\BueLaravel\Support\FormwerkVorgangsnummerResolver;
use Carbon\Carbon;
use DateTimeInterface;
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

    public function adminConnection(): string
    {
        return config('bue-laravel.database.admin_connection');
    }

    public function table(string $table): Builder
    {
        return DB::connection($this->connection())->table($table);
    }

    public function adminTable(string $table): Builder
    {
        return DB::connection($this->adminConnection())->table($table);
    }

    public function adminStatement(string $sql): bool
    {
        return DB::connection($this->adminConnection())->statement($sql);
    }

    /**
     * Vergibt einem BUE-User eine Oracle-Rolle (GRANT role TO user).
     */
    public function grantBueRole(string $username, string $rolename): bool
    {
        $username = $this->assertOracleIdentifier($username, 'username');
        $rolename = $this->assertOracleIdentifier($rolename, 'rolename');

        return $this->adminStatement('grant '.$rolename.' to '.$username);
    }

    /**
     * Entzieht einem BUE-User eine Oracle-Rolle (REVOKE role FROM user).
     */
    public function revokeBueRole(string $username, string $rolename): bool
    {
        $username = $this->assertOracleIdentifier($username, 'username');
        $rolename = $this->assertOracleIdentifier($rolename, 'rolename');

        return $this->adminStatement('revoke '.$rolename.' from '.$username);
    }

    /**
     * Sperrt einen BUE-User (ALTER USER … ACCOUNT LOCK).
     */
    public function disableBueUser(string $username): bool
    {
        $username = $this->assertOracleIdentifier($username, 'username');

        return $this->adminStatement('alter user '.$username.' account lock');
    }

    /**
     * Entsperrt einen BUE-User (ALTER USER … ACCOUNT UNLOCK).
     */
    public function enableBueUser(string $username): bool
    {
        $username = $this->assertOracleIdentifier($username, 'username');

        return $this->adminStatement('alter user '.$username.' account unlock');
    }

    /**
     * Liefert die einem BUE-User gewährten Oracle-Rollen aus DBA_ROLE_PRIVS.
     *
     * @return Collection<int, string>
     */
    public function getBueRoles(string $username): Collection
    {
        $username = $this->assertOracleIdentifier($username, 'username');

        return $this->adminTable('DBA_ROLE_PRIVS')
            ->select('granted_role')
            ->where('grantee', $username)
            ->get()
            ->pluck('granted_role');
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function assertOracleIdentifier(string $value, string $field): string
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_#$]*$/', $value) !== 1) {
            throw new \InvalidArgumentException("Invalid Oracle identifier for {$field}.");
        }

        return $value;
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

    /**
     * Sucht Betriebe über Betriebsnummer, Name, Anschrift oder Personen (Name/Geburtsdatum/Anschrift).
     *
     * @return Collection<int, object{
     *     bnr: mixed,
     *     name: string|null,
     *     betriebsanschrift: string|null,
     *     strasse: string|null,
     *     hausnummer: string|null,
     *     betr_plz: string|null,
     *     betr_ort: string|null,
     *     betriebsart: string|null,
     *     rechtsform: string|null,
     *     edat: string|null,
     *     betr_email: string|null,
     *     betr_telefon: string|null,
     *     matched_on: list<string>
     * }>
     */
    public function searchBetriebe(string $query, int $limit = 50): Collection
    {
        $query = trim($query);

        if ($query === '' || mb_strlen($query) < 2) {
            return collect();
        }

        $like = '%'.mb_strtolower($query).'%';

        $rows = $this->table('intranet.betr_stamm as s')
            ->select([
                's.bnr',
                's.name',
                's.betriebsanschrift',
                's.strasse',
                's.hausnummer',
                's.betr_plz',
                's.betr_ort',
                's.betriebsart',
                's.rechtsform',
                's.edat',
                's.betr_email',
                's.betr_telefon',
            ])
            ->where(function (Builder $q) use ($like): void {
                $q->whereRaw('LOWER(s.bnr) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(s.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(s.betriebsanschrift) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(s.strasse) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(s.betr_plz) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(s.betr_ort) LIKE ?', [$like])
                    ->orWhereExists(function (Builder $sub) use ($like): void {
                        $sub->select(DB::raw('1'))
                            ->from('intranet.betr_personen as p')
                            ->whereColumn('p.betriebsnummer', 's.bnr')
                            ->where(function (Builder $pq) use ($like): void {
                                $pq->whereRaw('LOWER(p.name) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(p.vorname) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(p.geburtsdatum) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(p.strasse) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(p.plz) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(p.ort) LIKE ?', [$like]);
                            });
                    });
            })
            ->orderBy('s.name')
            ->limit($limit)
            ->get();

        return $rows->map(function (object $row) use ($query): object {
            $row->matched_on = $this->resolveBetriebSearchMatches($row, $query);

            return $row;
        });
    }

    /**
     * @return list<string>
     */
    private function resolveBetriebSearchMatches(object $row, string $query): array
    {
        $needle = mb_strtolower($query);
        $matches = [];

        if (str_contains(mb_strtolower((string) $row->bnr), $needle)) {
            $matches[] = 'bnr';
        }

        if (str_contains(mb_strtolower((string) ($row->name ?? '')), $needle)) {
            $matches[] = 'name';
        }

        $anschrift = mb_strtolower(implode(' ', array_filter([
            $row->betriebsanschrift ?? null,
            $row->strasse ?? null,
            $row->hausnummer ?? null,
            $row->betr_plz ?? null,
            $row->betr_ort ?? null,
        ], fn ($value) => $value !== null && $value !== '')));

        if (str_contains($anschrift, $needle)) {
            $matches[] = 'anschrift';
        }

        if ($matches === []) {
            $matches[] = 'person';
        }

        return $matches;
    }

    /**
     * @return Collection<int, object>
     */
    public function getBetriebGewerbeByBetriebsnr(int|string $betriebsnr): Collection
    {
        return $this->table('intranet.betr_gewerbe')
            ->select([
                'betriebsnummer',
                'betriebsart',
                'gewerbe',
                'gewerbename',
                'spg',
                'eintragungsdatum',
                'teiltaetigkeit',
                'eintragungsvoraussetzung',
            ])
            ->where('betriebsnummer', $betriebsnr)
            ->orderBy('eintragungsdatum')
            ->orderBy('gewerbename')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function getBetriebPersonenByBetriebsnr(int|string $betriebsnr): Collection
    {
        return $this->table('intranet.betr_personen')
            ->select([
                'betriebsnummer',
                'personennummer',
                'name',
                'vorname',
                'geburtsdatum',
                'strasse',
                'plz',
                'ort',
                'personhatstellung',
                'geschlecht',
                'anredekennung',
            ])
            ->where('betriebsnummer', $betriebsnr)
            ->orderBy('name')
            ->orderBy('vorname')
            ->get();
    }

    /**
     * Stammdaten inkl. Gewerbe- und Personenlisten.
     *
     * @return object{gewerbe: Collection, personen: Collection}|null
     */
    public function getBetriebDetailByBetriebsnr(int|string $betriebsnr): ?object
    {
        $stamm = $this->getBetriebByBetriebsnr($betriebsnr);

        if ($stamm === null) {
            return null;
        }

        $stamm->gewerbe = $this->getBetriebGewerbeByBetriebsnr($betriebsnr);
        $stamm->personen = $this->getBetriebPersonenByBetriebsnr($betriebsnr);

        return $stamm;
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

    /**
     * Liefert einen OLV-Datensatz anhand der Online-ID aus intranet.OLV_N8N (oder null).
     */
    public function getOlvDataByOnlineId(int|string $onlineId): ?object
    {
        return $this->table('intranet.OLV_N8N')
            ->select('*')
            ->where('onlineid', $onlineId)
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

    /**
     * Liefert die Teilnehmerliste einer Prüfung (distinct) anhand der Prüfungs-ID (mp.id).
     *
     * @return Collection<int, object{test: int|string, mpname: string, mpvname: string, mpgebdat: string|null, gewerbe: string|null, fachbereich: string|null}>
     */
    public function getPruefungsteilnehmerliste(int $pruefungId): Collection
    {
        return $this->table('MP.PRUEFLING_MP as p')
            ->join('mp.handwerk as h', 'h.id', '=', 'p.handwerk_id')
            ->join('mp.pruefling_zu_prueftermin as mpp', 'mpp.pruefling_id', '=', 'p.id')
            ->join('mp.pruefung as mp', 'mp.id', '=', 'mpp.pruefung_id')
            ->join('hwk.person as hp', 'hp.id', '=', 'p.person_id')
            ->join('mp.ordnung as o', 'o.id', '=', 'mp.ordnung_id')
            ->where('mp.id', $pruefungId)
            ->distinct()
            ->select([
                'mp.id as test',
                'hp.name as mpname',
                'hp.vorname as mpvname',
                'hp.geburtsdatum as mpgebdat',
                'h.gewerbe as gewerbe',
                'o.fachbereich as fachbereich',
            ])
            ->get();
    }

    /**
     * Liefert Prüfungen mit Terminen im angegebenen Zeitraum, gefiltert nach Gewerk (Teilstring, case-insensitive).
     *
     * @return Collection<int, object{id: int|string, bezeichnung: string|null, termin_von: string, termin_bis: string|null, termin_bezeichnung: string|null, gewerbe: string|null, fachbereich: string|null}>
     */
    public function getPruefungsuebersicht(string $gewerbe, DateTimeInterface|string $von, DateTimeInterface|string $bis): Collection
    {
        $vonDate = Carbon::parse($von)->startOfDay()->format('Y-m-d H:i:s');
        $bisDate = Carbon::parse($bis)->endOfDay()->format('Y-m-d H:i:s');

        return $this->table('mp.pruefung as mp')
            ->join('mp.pruefungstermin as pt', 'pt.pruefung_id', '=', 'mp.id')
            ->join('mp.ordnung as o', 'o.id', '=', 'mp.ordnung_id')
            ->join('mp.handwerk as h', 'h.id', '=', 'o.handwerk_id')
            ->whereRaw('LOWER(h.gewerbe) LIKE ?', ['%'.strtolower($gewerbe).'%'])
            ->whereBetween('pt.von', [$vonDate, $bisDate])
            ->orderBy('pt.von')
            ->orderBy('mp.id')
            ->select([
                'mp.id',
                'mp.bezeichnung',
                'pt.von as termin_von',
                'pt.bis as termin_bis',
                'pt.bezeichnung as termin_bezeichnung',
                'h.gewerbe as gewerbe',
                'o.fachbereich as fachbereich',
            ])
            ->get();
    }

    /**
     * Liefert alle Termine einer Prüfung anhand der Prüfungs-ID (mp.id).
     *
     * @return Collection<int, object{id: int|string, bezeichnung: string|null, termin_von: string, termin_bis: string|null, termin_bezeichnung: string|null, gewerbe: string|null, fachbereich: string|null}>
     */
    public function getPruefungById(int|string $pruefungId): Collection
    {
        return $this->table('mp.pruefung as mp')
            ->join('mp.pruefungstermin as pt', 'pt.pruefung_id', '=', 'mp.id')
            ->join('mp.ordnung as o', 'o.id', '=', 'mp.ordnung_id')
            ->join('mp.handwerk as h', 'h.id', '=', 'o.handwerk_id')
            ->where('mp.id', $pruefungId)
            ->orderBy('pt.von')
            ->orderBy('mp.id')
            ->select([
                'mp.id',
                'mp.bezeichnung',
                'pt.von as termin_von',
                'pt.bis as termin_bis',
                'pt.bezeichnung as termin_bezeichnung',
                'h.gewerbe as gewerbe',
                'o.fachbereich as fachbereich',
            ])
            ->get();
    }

    /**
     * Liefert alle Gewerke aus mp.ordnung → mp.handwerk (distinct, sortiert).
     * Für Dropdown-Filter zu getPruefungsuebersicht(): value und label = gewerbe.
     *
     * @return Collection<int, object{id: int|string, gewerbe: string}>
     */
    public function getPruefungsGewerke(): Collection
    {
        return $this->table('mp.ordnung as o')
            ->join('mp.handwerk as h', 'h.id', '=', 'o.handwerk_id')
            ->whereNotNull('h.gewerbe')
            ->distinct()
            ->orderBy('h.gewerbe')
            ->select([
                'h.id',
                'h.gewerbe',
            ])
            ->get();
    }
}
