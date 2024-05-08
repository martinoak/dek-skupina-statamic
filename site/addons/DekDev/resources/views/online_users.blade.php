@extends('layout')

@section('content')

<div id="dek-dev">
	<div class="flexy mb-24">
		<h1 class="fill">
			Online users
		</h1>
	</div>

	<div class="logins-list">
		<div class="card flush">
			<table class="dossier">
				<thead>
					<tr>
						<th>User</th>
						<th title="SESSION unique key">Browser token</th>
						<th>IP</th>						
						<th>Last action</th>
						<th>Last login</th>
					</tr>
				</thead>
				<tbody>
					
						@foreach($logins as $login)
						<tr>
							<td style="max-width: 325px;">
								{{ $login->getUser()->username() }}
							</td>

							<td>
								{{ $login->getToken() }}
							</td>
							
							<td>
								{{ $login->getIp() }}
							</td>
							
							<td @if($login->isLoggedOut() || $login->isIddle(86400))style="color:red" @elseif($login->isIddle(600)) style="color:orange"@endif>
								@if($login->isLoggedOut()) Offline
								@elseif($login->isIddle(3600 * 24)) > 1 day
								@elseif($login->isIddle(3600 * 12)) > 12 hours
								@elseif($login->isIddle(3600 * 6)) > 6 hours
								@elseif($login->isIddle(3600 * 3)) > 3 hours
								@elseif($login->isIddle(3600)) > 1 hour
								@else {{ floor($login->getIddleTime() / 60) }} min
								@endif
							</td>
							<td>
								@if($login->getLogin()) {{ $login->getLogin()->toDateTimeString() }} @endif
							</td>
						</tr>
						@endforeach
					
				</tbody>
			</table>
		</div>
	</div>
</div>

@endsection
