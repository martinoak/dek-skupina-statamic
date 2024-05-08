@extends('layout')

@section('content')

<div id="maintenance">
	<div class="flexy mb-24">
		<h1 class="fill">
			Maintenance mode
		</h1>
	</div>

	<div class="card">
		Switch control panel to maintenance mode
		<a class="btn btn-danger" href="?switch={{ $token }}">
			Maintenance mode
		</a>
		
	</div>
</div>

@endsection
