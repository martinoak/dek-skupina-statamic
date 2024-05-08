@extends('layout')

@section('content')

<div id="dek-dev">
	<div class="flexy mb-24">
		<h1 class="fill">
			DEK Developer Tools
		</h1>
	</div>

	<div class="readme-deploy mb-120">
		<?php echo (\Michelf\MarkdownExtra::defaultTransform($readme)); ?>
	</div>
</div>

@endsection
