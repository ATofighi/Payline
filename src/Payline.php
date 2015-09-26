<?php
namespace ATofighi;

class Payline
{
	private $api;
	private $debug;

	/**
	 * @param string $api the API key
	 *
	 * @return null
	 */
	function __construct($api = '')
	{
		if ($api) {
			$this->api = $api;
			$this->debug = false;
		} else {
			$this->debug = true;
			$this->api = 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567';
		}
	}

	/**
	 * @return string the send URL
	 */
	private function sendUrl()
	{
		if ($this->debug) {
			return 'http://payline.ir/payment-test/gateway-send';
		}
		return 'http://payline.ir/payment/gateway-send';
	}

	/**
	 * @param $num
	 *
	 * @return string the gateway URL
	 */
	private function gatewayUrl($num)
	{
		if ($this->debug) {
			return 'http://payline.ir/payment-test/gateway-' . $num;
		}
		return 'http://payline.ir/payment/gateway-' . $num;
	}

	/**
	 * @return string the get URL
	 */
	private function getUrl()
	{
		if ($this->debug) {
			return 'http://payline.ir/payment-test/gateway-result-second';
		}
		return 'http://payline.ir/payment/gateway-result-second';
	}

	/**
	 * Fetch the contents of a remote file.
	 * Copied from MyBB
	 *
	 * @param $url       string The URL of the remote file
	 * @param $post_data array  The array of post data
	 *
	 * @return string The remote file contents.
	 */
	function fetch_remote_file($url, $post_data = array())
	{
		$post_body = '';
		if (!empty($post_data)) {
			foreach ($post_data as $key => $val) {
				$post_body .= '&' . urlencode($key) . '=' . urlencode($val);
			}
			$post_body = ltrim($post_body, '&');
		}

		if (function_exists("curl_init")) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if (!empty($post_body)) {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
			}
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;
		} else if (function_exists("fsockopen")) {
			$url = @parse_url($url);
			if (!$url['host']) {
				return false;
			}
			if (!$url['port']) {
				$url['port'] = 80;
			}
			if (!$url['path']) {
				$url['path'] = "/";
			}
			if ($url['query']) {
				$url['path'] .= "?{$url['query']}";
			}

			$scheme = '';

			if ($url['scheme'] == 'https') {
				$scheme = 'ssl://';
				if ($url['port'] == 80) {
					$url['port'] = 443;
				}
			}

			$fp = @fsockopen($scheme . $url['host'], $url['port'], $error_no, $error, 10);
			@stream_set_timeout($fp, 10);
			if (!$fp) {
				return false;
			}
			$headers = array();
			if (!empty($post_body)) {
				$headers[] = "POST {$url['path']} HTTP/1.0";
				$headers[] = "Content-Length: " . strlen($post_body);
				$headers[] = "Content-Type: application/x-www-form-urlencoded";
			} else {
				$headers[] = "GET {$url['path']} HTTP/1.0";
			}

			$headers[] = "Host: {$url['host']}";
			$headers[] = "Connection: Close";
			$headers[] = '';

			if (!empty($post_body)) {
				$headers[] = $post_body;
			} else {
				// If we have no post body, we need to add an empty element to make sure we've got \r\n\r\n before the (non-existent) body starts
				$headers[] = '';
			}

			$headers = implode("\r\n", $headers);
			if (!@fwrite($fp, $headers)) {
				return false;
			}
			$data = '';
			while (!feof($fp)) {
				$data .= fgets($fp, 12800);
			}
			fclose($fp);
			$data = explode("\r\n\r\n", $data, 2);
			return $data[1];
		} else if (empty($post_data)) {
			return @implode("", @file($url));
		} else {
			return false;
		}
	}

	/**
	 * @param $amount
	 * @param $redirect
	 *
	 * @return string
	 */
	public function send($amount, $redirect)
	{
		return $this->fetch_remote_file($this->sendUrl(), array(
			'api' => $this->api,
			'amount' => $amount,
			'redirect' => $redirect
		));
	}

	/**
	 * @param $result
	 */
	public function go($result)
	{
		$result = (int)$result;
		$go = $this->gatewayURL($result);
		header("Location: $go");
		exit;
	}

	/**
	 * @param $trans_id
	 * @param $id_get
	 *
	 * @return string
	 */
	public function get($trans_id, $id_get)
	{
		$trans_id = (int)$trans_id;
		$id_get = (int)$id_get;
		return $this->fetch_remote_file($this->getUrl(), array(
			'api' => $this->api,
			'id_get' => $id_get,
			'trans_id' => $trans_id
		));
	}

	/**
	 * @param $error int the error code
	 *
	 * @return string the error message
	 */
	public function getError($error)
	{
		switch ($error) {
			case '-1':
				return 'api ارسالی با نوع api تعریف شده در payline .سازگار نیست';
				break;
			case '-2':
				return ' مقدار amount .داده عددي نمی باشد و یا کمتر از 1000 ریال است';
				break;
			case '-3':
				return ' مقدار redirect رشته null .است';
				break;
			case '-4':
				return 'درگاهی با اطلاعات ارسالی شما یافت نشده و یا در حالت انتظار می باشد.';
				break;
		}
		return '';
	}
}
