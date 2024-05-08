<?php

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
    const ON_FAKE_DB = 'on_fake_db';
    //procedure
    const ON_PROC_CREATED = 'on_proc_created';
    const ON_BEFORE_PROC_EXECUTE = 'on_before_proc_execute';
    const ON_AFTER_PROC_EXECUTE = 'on_after_proc_execute';
    const ON_ERROR_PROC_EXECUTE = 'on_error_proc_execute';
    const ON_ERROR_PROC_EXECUTE_WITH_CACHE = 'on_error_proc_execute_with_cache';
    const ON_RESULT_BIND = 'on_result_bind';
    const ON_RESULT_CREATED = 'on_result_created';
    //cache
    const ON_BEFORE_PROC_GET_CACHE = 'on_before_proc_get_cache';
    const ON_AFTER_PROC_CACHE_GET = 'on_after_proc_cache_get';
    const ON_AFTER_PROC_CACHE_SET = 'on_after_proc_cache_set';
    const ON_AFTER_PROC_CACHE_OUTPUTS_SET = 'on_after_proc_cache_outputs_set';
    const ON_AFTER_PROC_CACHE_NEED_REFRESH = 'on_after_proc_cache_need_refresh';
    const ON_CACHE_EXECUTE = 'on_cache_execute';
    const ON_CACHE_REFRESH = 'on_cache_refresh';
    //selenium
    const ON_FAKE_RESULT = 'on_fake_result';

    public function trigger(string $event, ...$args): bool;

    public function bind(string $event, \Closure $clsr): self;

    public function unBind(string $event): self;
}
