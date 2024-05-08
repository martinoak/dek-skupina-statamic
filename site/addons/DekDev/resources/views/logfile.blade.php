@extends('layout')

@section('content')

<div id="dek-dev-logs">
	<div class="flexy mb-24">
		<h1 class="fill">
			Logs
		</h1>
		<a class="btn" href="{{ route('dekdev.index') }}">
			Back
		</a>
	</div>

	<div class="card">
		<pre>
<?php echo $content; ?>
		</pr>
	</div>
</div>

@endsection
