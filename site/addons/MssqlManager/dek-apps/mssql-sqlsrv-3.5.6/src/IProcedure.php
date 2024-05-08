<?php

namespace DekApps\MssqlProcedure;

interface IProcedure
{

    // crypto defuse key
    const DEFUSE_KEY = "def00000fdc0423fcb8dcdd9b728238bcf021a11956135c2455e60668ad8239cf2a77a56ec7cd52477ed9190cf036e2c1d73a3a0b4ee94f5bf49245c9d8205883d721163";
    const NO_ERROR = 0;
    const REPLICATOR = 'REP';
    const RESULTS = 'RES';
    const OUTPUTS = 'OUT';
    const LOCK = 'LCK';
    const ERROR = 'ERR';
    const META = 'META';
    const ERROR_CREATED = 'ERRCREATED';
    const ERROR_SEND = 'ERRSEND';
    const SELENIUM = 'selenium';
    /*
     * Timeout on the first occurrence of error (in minutes)
     */
    const ERROR_TIMEOUT = 15;
    
    const INCUBATOR_QUERY_TIMEOUT = 90;
    
    const QUEUEFILE = 'queue';

    public function fetchArray($id = null);

    public function fetch();

    public function getOutputs(string $param_name = null);
}
