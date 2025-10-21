<?php

namespace Hwkdo\BueLaravel;

use Illuminate\Support\Facades\DB;

class BueLaravel {

    public function getFachbereiche() {
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
}
