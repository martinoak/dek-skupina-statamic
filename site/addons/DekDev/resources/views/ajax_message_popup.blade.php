<div id="dekdev-deploy-message" class="message-popup">
	<div class="{{ $style }}" onclick="var d = new Date();d.setTime(d.getTime() + (3600*1000));var expires = 'expires='+ d.toUTCString();document.cookie = 'deploy_message_minimized={{ $id }};'+expires+';path=/';this.style.display = 'none';">
		<div class="head">
			{{ $title }}
		</div>
		<div class="body">
			{{ $message }}
		</div>
	</div>
</div>
