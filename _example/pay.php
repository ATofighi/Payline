<?php
error_reporting(E_ALL ^ E_NOTICE);
$input = array_merge($_REQUEST, $_GET, $_POST);

$url = 'http://localhost/payline/_example/pay.php';
$api = '';

require_once '../src/Payline.php';

$pay = new ATofighi\Payline($api);
$transactions = new TransactionManager;
$views = new ViewManager;

$views->globalData['input_amount'] = $input['amount'];
$views->globalData['url'] = $url;

switch ($input['action']) {
	case 'check':
		$r = $pay->get($input['trans_id'], $input['id_get']);
		if ($r == 1) {
			$tr = $transactions->get($input['id_get']);
			if ($tr) {
				if ($tr['status'] == 1) {
					$views->display('layout', ['title' => 'خطا', 'error' => 'این تراکنش قبلا انجام شده بود!']);
				} else {
					$tr['status'] = 1;
					$tr['trans'] = $input['trans_id'];
					$transactions->put($input['id_get'], $tr);
					$views->display('layout', ['title' => 'سپاس', 'ok' => 'تراکنش با موفقیت انجام شد!']);
				}
			} else {
				$views->display('layout', ['title' => 'خطا', 'error' => 'تراکنش منقضی شده است.']);
			}
		} else {
			$views->display('layout', ['title' => 'خطا', 'error' => $pay->getError($r)]);
		}
		break;
	default:
		if ($input['submit']) {
			$r = $pay->send($input['amount'], $url . '?action=check');
			if ($r > 0) {
				$transactions->put($r, ['amount' => $input['amount'], 'status' => '0']);
				$pay->go($r);
			}
			$views->display('layout', ['title' => 'خطا', 'error' => $pay->getError($r)]);
		} else {
			$views->display('layout', ['title' => 'خانه']);
		}
}

class TransactionManager
{
	protected $path = './transactions';

	/**
	 * @param $id
	 *
	 * @return bool|mixed
	 */
	public function get($id)
	{
		$id = (int)$id;
		if (file_exists($this->path . "/_{$id}.json")) {
			return json_decode(@file_get_contents($this->path . "/_{$id}.json"), true);
		}
		return false;
	}

	/**
	 * @param       $id
	 * @param array $data
	 *
	 * @return int
	 */
	public function put($id, array $data)
	{
		$id = (int)$id;
		if ($transaction = $this->get($id)) {
			$data = array_merge($transaction, $data);
		}

		return file_put_contents($this->path . "/_{$id}.json", json_encode($data, true));
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}
}

class ViewManager
{
	protected $path = './views';
	public $globalData = [];

	private function fixName($name)
	{
		return preg_replace("#([^a-z_]+)#si", '', $name);
	}

	public function get($name, array $data = [])
	{
		$data = array_merge($this->globalData, $data);
		$name = $this->fixName($name);
		if (file_exists("{$this->path}/{$name}.html")) {
			$template = @file_get_contents("{$this->path}/{$name}.html");
			$template = preg_replace_callback("#\{\{\{([ \t\n\r]+)([a-zA-Z_]+)([ \t\n\r]+)\}\}\}#si", function ($match) use ($data) {
				return $data[$match[2]];
			}, $template);
			$template = preg_replace_callback("#\{\{([ \t\n\r]+)([a-zA-Z_]+)([ \t\n\r]+)\}\}#si", function ($match) use ($data) {
				return htmlspecialchars($data[$match[2]], ENT_COMPAT, 'UTF-8');
			}, $template);
			return $template;
		}
		return false;
	}

	public function display($name, array $data = [])
	{
		echo $this->get($name, $data);
	}

}