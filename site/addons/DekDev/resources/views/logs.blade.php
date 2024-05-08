@extends('layout')

@section('content')

<div id="dek-dev-logs">
	<div class="flexy mb-24">
		<h1 class="fill">
			Logs
		</h1>
	</div>

	<div class="card">
		@foreach ($files as $file)
		<a href="?file={{ $file }}">{{ $file }}</a><br>
		@endforeach
	</div>
</div>

@endsection
