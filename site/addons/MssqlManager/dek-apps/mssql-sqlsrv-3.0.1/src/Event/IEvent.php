<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DekApps\MssqlProcedure\Event;


interface IEvent
{
    //connection
    const ON_INSTANCE = 'on_instance';
    const ON_KILL = 'on_kill';
    const ON_BEFORE_CONN = 'on_before_conn';
    const ON_AFTER_CONN = 'on_after_conn';
    const ON_ERROR_CONN = 'on_error_conn';
    const ON_BEFORE_QUERY = 'on_before_query';
    const ON_AFTER_QUERY = 'on_after_query';
    const ON_ERROR_QUERY = 'on_error_query';
    const ON_FETCH_QUERY = 'on_fetch_query';
    const ON_BEGIN_TRANS = 'on_begin_trans';
    const ON_BEGIN_TRANS_ERROR = 'on_begin_trans_error';
    const ON_ROLLBACK = 'on_rollback';
    const ON_COMMIT = 'on_commit';
    //procedure
    const ON_PROC_CREATED = 'on_proc_created';
    const ON_BEFORE_PROC_EXECUTE = 'on_before_proc_execute';
    const ON_AFTER_PROC_EXECUTE = 'on_after_proc_execute';
    const ON_ERROR_PROC_EXECUTE = 'on_error_proc_execute';

    
    
    
    
}
