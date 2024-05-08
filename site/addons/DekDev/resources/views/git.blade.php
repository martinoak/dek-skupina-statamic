@extends('layout')

@section('content')

<div id="git-status">
	<div class="flexy mb-24">
		<h1 class="fill">
			GIT
		</h1>
	</div>

	<div class="card">
		<div class="mb">
			<a class="btn" href="{{ route('dekdev.git', ['command' => 'status', 'token' => $token]) }}">
				Status
			</a>
			<a class="btn" href="{{ route('dekdev.git', ['command' => 'branch', 'token' => $token]) }}">
				Branch
			</a>
            <a class="btn" href="{{ route('dekdev.git', ['command' => 'log', 'token' => $token]) }}">
				Log
			</a>
			<a class="btn" href="{{ route('dekdev.git', ['command' => 'diff', 'token' => $token]) }}">
				Diff
			</a>
			<a class="btn" href="{{ route('dekdev.git', ['command' => 'pull', 'token' => $token]) }}" onclick="return confirm('Are you sure? Command `pull` can break production code!');">
				Pull
			</a>
			<a class="btn" href="{{ route('dekdev.git', ['command' => 'push', 'token' => $token]) }}" onclick="return confirm('Are you sure?');">
				Push
			</a>
            <a class="btn" href="{{ route('dekdev.git', ['command' => 'commit', 'token' => $token]) }}" onclick="return confirm('Are you sure?');">
				Commit all
			</a>
            <a class="btn" href="{{ route('dekdev.git', ['command' => 'revert', 'token' => $token]) }}" onclick="return confirm('Are you sure?');">
				Revert all
			</a>
		</div>
		<p>
			<strong>Output:</strong>
		</p>

		<pre class="cli">
<green>{{ $bash_user }}</green> @if($bash_root)<yellow>{{ $bash_root }}</yellow>@endif @if($bash_branch)<cyan>({{ $bash_branch }})</cyan>@endif

@if ($outputs)
@foreach($outputs as $row)
<?php echo ($html_formated ? $row : htmlspecialchars($row)) . PHP_EOL; ?>
@endforeach
@else
$
@endif
		</pre>
	</div>
</div>

@endsection
