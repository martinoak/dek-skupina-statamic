<?php

namespace Statamic\Addons\DekDev;

use Statamic\API\Nav;
use Statamic\Extend\Listener;
use Illuminate\Support\Facades\Event as CoreEvent;

class DekDevListener extends Listener
{
    public $events = [
        'cp.add_to_head' => 'addToHead',
        'cp.nav.created' => 'addNavItem',

        // Laravel Events
        'auth.login' => 'record',
        'auth.logout' => 'record',
    ];

    /** @var LoginManager */
	private $manager;

	public function __construct()
    {
		parent::__construct();
        $this->manager = new LoginManager();
    }

    public function addToHead()
    {
        return $this->css->tag('dek-dev').$this->js->tag('dek-dev');
    }

    public function addNavItem($nav)
    {
		if (auth()->user()->isSuper()) {
			$item = Nav::item('DEK developer')->route('dekdev.index')->icon('users');

			$item->add(function ($i) {
				$i->add(Nav::item('Online users')->route('dekdev.online-users'));
				$i->add(Nav::item('Deploy message')->route('dekdev.edit'));
				$i->add(Nav::item('GIT')->route('dekdev.git'));
				$i->add(Nav::item('Maintenance mode')->route('dekdev.maintenance'));
				$i->add(Nav::item('Logs')->route('dekdev.logs'));
			});

			$nav->addTo('tools', $item);
		}
    }

    public function record()
    {
		$user = auth()->user();
		$ip = request()->ip();
		$token = session('_token');

		if (!$user) {
			return;
		}

		$login = $this->manager->getLogin($user, $ip, $token);

		if (!$login) {
			$login = new Login;
			$login->setIp($ip);
			$login->setUser($user);
			$login->setToken($token);
		}
		
		if (CoreEvent::firing() === 'auth.login') {
			$login->login();
		} elseif (CoreEvent::firing() === 'auth.logout') {
			$login->logout();
		}

		$this->manager->save($login);
    }

}
