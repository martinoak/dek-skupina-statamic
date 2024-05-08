<?php

namespace Statamic\Addons\DekDev;

use Statamic\Extend\Controller;
use Statamic\API\YAML;
use Statamic\CP\Publish\ProcessesFields;
use Statamic\API\Fieldset;
use Statamic\API\File;
use Illuminate\Http\Request;


class DekDevController extends Controller
{

	use ProcessesFields;

	/** @var LoginManager */
	private $manager;

	/** @var string */
	private $messageFilename;

	/** @var string */
	private $maintenanceFilename;

	/** @var string */
	private $deployMessageFilename;

	/** @var string */
	private $logsDir;

	public function __construct()
    {
		parent::__construct();
        $this->manager = new LoginManager();

		$tempPath = STATAMIC_ROOT . '/' . trim($this->getConfig('temp_path'), '/');
		$this->messageFilename = $tempPath . '/message.yaml';
		$this->maintenanceFilename = $tempPath . '/maintenance';
		$this->deployMessageFilename = STATAMIC_ROOT . '/' . trim($this->getConfig('readme_deploy'), '/');
		$this->logsDir = STATAMIC_ROOT . '/' . trim($this->getConfig('log_path'), '/');
    }

	/*** INDEX ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View
	 */
	public function index(Request $request)
    {
		$content = file_exists($this->deployMessageFilename)
				? file_get_contents($this->deployMessageFilename)
				: ('Chybí soubor '.$this->deployMessageFilename);
		
		return $this->view('index', [
			'readme' => $content
		]);
	}

	/*** ONLINE USERS ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View
	 */
	public function onlineUsers(Request $request)
    {
		$this->manager->deleteOld(5);
		return $this->view('online_users', [
			'logins' => $this->manager->getLogins()
        ]);
    }

	/**
	 * Ajax
	 * @return \Illuminate\Http\Response
	 */
	public function getLogActivity()
	{
		$user = auth()->user();
		$ip = request()->ip();
		$token = session('_token');

		if (!$user || !$this->isIdle()) {
			return $this->textResponse('error');
		}

		$login = $this->manager->getLogin($user, $ip, $token);

		if (!$login) {
			$login = new Login;
			$login->setIp($ip);
			$login->setUser($user);
			$login->setToken($token);
		}

		$login->online();
		$this->manager->save($login);

		return $this->textResponse();
	}

	/**
	 * @return boolean
	 */
	private function isIdle()
	{
		$last = session('dekdev-idle');

		if (time() > $last + 60) {
			session(['dekdev-idle' => time()]);
			return true;
		} else {
			return false;
		}
	}

	/*** DEPLOY MESSAGE ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View
	 */
	public function edit(Request $request)
    {
		if (!auth()->user()->isSuper()) {
			return redirect('/cp');
		}

		if (file_exists($this->messageFilename)) {
			$msg = YAML::parse(File::get($this->messageFilename));
		} else {
			$msg = [];
		}

		$data = $this->preProcessWithBlankFields(Fieldset::get('deploy_message'), $msg);

        return $this->view('msgform', [
            'data' => $data,
            'submitUrl' => route('dekdev.update')
        ]);
    }

	/**
	 * Form request
	 * @param Request $request
	 * @return array
	 */
	public function update(Request $request)
    {
		if (!auth()->user()->isSuper()) {
			return redirect('/cp');
		}

        $data = $this->processFields(Fieldset::get($request->fieldset), $request->fields);
		$data['id'] = md5($data['headline'].$data['message']);
		
		File::put($this->messageFilename, YAML::dump($data));

        $message = 'Entry saved';

        if (! request()->continue || request()->new) {
            $this->success($message);
        }

        return [
            'success'  => true,
            'redirect' => route('dekdev.edit'),
            'message' => $message
        ];
    }

	/**
	 * Ajax
	 * @return \Illuminate\View\View | \Illuminate\Http\Response
	 */
	public function getMessage()
	{
		if (!file_exists($this->messageFilename)) {
			return $this->textResponse();
		}
		
		$message = YAML::parse(File::get($this->messageFilename));

		$min = (isset($_COOKIE['deploy_message_minimized']) && $_COOKIE['deploy_message_minimized'] === $message['id']) ? true : false;

		$begin = empty($message['begin']) || strtotime($message['begin']) < time();
		$end = empty($message['end']) || strtotime($message['end']) > time();
		if (!empty($message['show']) && $begin && $end) {
			return $this->view($min ? 'ajax_message_line' : 'ajax_message_popup', [
				'id' => $message['id'],
				'title' => isset($message['headline']) ? $message['headline'] : null,
				'message' => $message['message'],
				'style' => $message['style']
			]);
		} else {
			return $this->textResponse();
		}
	}

	/*** GIT ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View | \Illuminate\Http\RedirectResponse
	 */
	public function git(Request $request)
	{
		if (!auth()->user()->isSuper()) {
			return redirect('/cp');
		}

		$command = isset($_GET['command']) ? $_GET['command'] : false;
		$auth = $this->authToken('git', isset($_GET['token']) ? $_GET['token'] : false, $token);

		if ($command && $auth) {
			$cmd = 'git '.$command;

            if ($command === 'log') {
				$cmd = "git log --all --graph --abbrev-commit --decorate --since='".date('Y-m-d H:i:s', time() - 30*24*3600)."' --format=format:'<magenta>%h</magenta> - <cyan>%aD</cyan> <green>(%ar)</green><yellow>%d</yellow>%n''          <grey>%an</grey>: %s'";
                $htmlFormated = true;
            }

            if ($command === 'commit') {
				$cmd = 'git add -A && git commit -m "DekDev commit"';
			}

            if ($command === 'revert') {
				$cmd = 'git checkout ."';
			}

			$outputs[] = 'git fetch';
			exec('git fetch', $outputs, $freturn);
			$outputs[] = $cmd;
			exec($cmd, $outputs, $returnVar);

			session(['dekdev-git-command' => $outputs, 'dekdev-git-html' => $htmlFormated ?? false]);
			return redirect()->route('dekdev.git');
		}

		$outputs = session('dekdev-git-command') ?: [];
        $htmlFormated = session('dekdev-git-html') ?: false;
		session(['dekdev-git-command' => null, 'dekdev-git-html' => null]);

		return $this->view('git', [
			'bash_user' => auth()->user()->username(),
			'bash_root' => realpath(__DIR__),
			'bash_branch' => $this->gitBranch(),
			'outputs' => $outputs,
            'html_formated' => $htmlFormated,
			'token' => $token
        ]);
	}

	/**
	 * @return string
	 */
	private function gitBranch()
	{
		$branch = null;
		exec('git branch', $outputs, $return);
		foreach ($outputs as $row) {
			if (strpos($row, '*') === 0) {
				$branch = ltrim($row, '* ');
				break;
			}
		}
		return $branch;
	}

	/*** MAINTENANCE MODE ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View | \Illuminate\Http\RedirectResponse
	 */
	public function maintenance(Request $request)
	{
		if (!auth()->user()->isSuper()) {
			return redirect('/cp');
		}
		
		$auth = $this->authToken('maintenance', isset($_GET['switch']) ? $_GET['switch'] : false, $token);
		if ($auth) {
			File::put($this->maintenanceFilename, auth()->user()->id());
			return redirect()->route('dekdev.maintenance');
		}
		return $this->view('maintenance', [
			'token' => $token
        ]);
	}

	/**
	 * Ajax
	 * @return \Illuminate\View\View | \Illuminate\Http\Response
	 */
	public function getMaintenanceMode()
	{
		if (file_exists($this->maintenanceFilename)) {
			$maintenance = true;
			$userId = File::get($this->maintenanceFilename);
		} else {
			$maintenance = $userId = false;
		}

		if ($maintenance && $userId !== auth()->user()->id()) {
			return $this->view('ajax_maintenance', [
				'message' => $this->getConfig('maintenance_mode')
			]);
		} elseif ($maintenance) {
			return $this->textResponse('2');
		} else {
			return $this->textResponse('0');
		}
	}

	/*** LOGS ***/

	/**
	 * View
	 * @param Request $request
	 * @return \Illuminate\View\View | \Illuminate\Http\Response
	 */
	public function logs(Request $request)
    {
		$file = isset($_GET['file']) ? $_GET['file'] : null;
		$raw = true;
		
		if ($file) {
			return $this->readFile($file, $raw);
		} else {
			return $this->listDir();
		}
	}

	/**
	 * @return \Illuminate\View\View
	 */
	private function listDir()
	{
		$files = [];
		if (is_dir($this->logsDir)) {
			$dir = dir($this->logsDir);
			while (($file = $dir->read()) !== false) {
				if ($file !== '.' && $file !== '..') {
					$files[] = $file;
				}
			}
            rsort($files, SORT_STRING);
		}

		return $this->view('logs', [
			'files' => $files
		]);
	}

	/**
	 * @param string $file
	 * @return \Illuminate\View\View | \Illuminate\Http\Response
	 */
	private function readFile($file)
	{
		$content = 'Soubor neexistuje';
		if (file_exists($this->logsDir.'/'.$file)) {
			$content = file_get_contents($this->logsDir.'/'.$file);
		}
		return $this->textResponse($content);
	}


	/*** INTERNAL ***/

	/**
	 * @param string $text
	 * @return \Illuminate\Http\Response
	 */
	private function textResponse($text = '')
	{
		return response((string) $text, 200)
				->header('Content-Type', 'text/plain');
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $token New token
	 * @return boolean
	 */
	private function authToken($name, $value, &$token)
	{
		if (is_string($value) && is_string(session('dekdev-token-'.$name)) && session('dekdev-token-'.$name) === sha1($value)) {
			session(['dekdev-token-'.$name => null]);
			return true;
		} else {
			$token = str_random(10);
			session(['dekdev-token-'.$name => sha1($token)]);
			return false;
		}
	}

}
