<?php
	/**
	 * \file
	 * Implement network coroutine execution.
	 */
	require_once 'utils/utils.php';

	/**
	 * Runs a coroutine with network yielding
	 *
	 * @template TReturn
	 */
	class FetchingCoroutine {
		/**
		 * @var Generator<string,CurlHandle,CurlHandle|false,TReturn> $generator
		 */
		private Generator $generator;

		private bool $consumed = false;

		/**
		 * @var Closure():bool $response_filter
		 */
		private Closure $response_filter;

		/**
		 * Creates a new FetchingCouroutine instance.
		 * @param Generator<string,CurlHandle,CurlHandle|false,TReturn> $generator
		 * An instantiated generator yielding `string => CurlHandle` pairs.
		 */
		public function __construct(Generator $generator) {
			$this->generator = $generator;
			$this->response_filter = function(CurlHandle $handle): bool {
				$code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
				$url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
				log_debug("Got code $code for $url in default request arbitrator.");
				return $code < 300 && $code != 0;
			};
		}

		/**
		 * Create a new FetchingCoroutine to fetch the contents of a URL.
		 * @param string $url URL to fetch.
		 * @param array $curlopts Addition cURL options.
		 * @return FetchingCoroutine<CurlHandle|false>
		 * Coroutine returning 1) fulfilled cURL handle, or 2) false in case of failure.
		 */
		public static function from_url(string $url, array $curlopts = []): FetchingCoroutine {
			/**
			 * @var Generator<string,CurlHandle,CurlHandle|false,CurlHandle|false> $oneshot
			 */
			$oneshot = (function() use ($url, $curlopts) {
				return yield make_curl_handle($url, $curlopts);
			})();
			return new FetchingCoroutine($oneshot);
		}

		/**
		 * Set a callback to decide successful responses.
		 * @param Closure $response_filter Predicate on a processed CurlHandle.
		 * @return FetchingCoroutine Return self.
		 */
		public function set_response_filter(Closure $response_filter): FetchingCoroutine {
			$this->response_filter = $response_filter;
			return $this;
		}

		private function assert_not_consumed() {
			if ($this->consumed) {
				throw new Error("This FetchingCoroutine has been used up by a transforming call");
			}
		}

		private function consume() {
			$this->assert_not_consumed();
			$this->consumed = true;
		}

		/**
		 * Produce a derived coroutine that halts on failed fetches. Consumes current coroutine.
		 * Resulting coroutine will not produce further fetches after failure.
		 * @return FetchingCoroutine<TReturn|null> New FetchingCoroutine instance.
		 */
		public function stopping_on_failure(): FetchingCoroutine {
			$this->consume();
			$haltable = function () {
				foreach ($this->generator as $id => $handle) {
					if (!(yield $id => $handle)) {
						return;
					}
				}
				return $this->generator->getReturn();
			};
			return $this->project_coroutine_parameters(new FetchingCoroutine($haltable()));
		}

		/**
		 * Produce a derived coroutine that retries failed fetches a given number of times.
		 * Consumes current coroutine.
		 * @param int $retries Number of additional retries made for curl handles returned.
		 * @param bool $tallied_retries If true, the retry count applies to the whole coroutine.
		 * If false, each request is afforded the given retries.
		 * @return FetchingCoroutine<TReturn> New FetchingCoroutine instance.
		 */
		public function retryable(int $retries, bool $tallied_retries = true): FetchingCoroutine {
			$this->consume();
			$coroutine = $this;
			$retryable = function () use ($retries, $coroutine, $tallied_retries) {
				processing_new_request:
				while ($coroutine->valid()) {
					$retries_current = $retries;
					$id = $coroutine->current_key();
					$handle = $coroutine->current_request();
					$attempt_no = 1;
					do {
						if (!($attempt_handle = curl_copy_handle($handle))) {
							log_error("Failed to clone cURL handle");
							$coroutine->send(false);
							goto processing_new_request;
						}

						/** @var CurlHandle|false $response_handle */
						$response_handle = yield $id => $attempt_handle;
						$url = curl_getinfo($attempt_handle, CURLINFO_EFFECTIVE_URL);

						if ($response_handle) {
							$retcode = curl_getinfo($response_handle, CURLINFO_HTTP_CODE);
							$url = curl_getinfo($response_handle, CURLINFO_EFFECTIVE_URL) ?? $url;
							log_debug("Attempt #$attempt_no for $url returned code $retcode.");
							$coroutine->send($response_handle);
							goto processing_new_request;
						}

						log_debug("Attempt #$attempt_no for $url failed or was rejected upstream.");

						$attempt_no++;
					} while ($retries_current-- > 0);

					// failed to fetch handle
					$coroutine->send(false);

					// decrease the remaining retries
					if ($tallied_retries) {
						$retries = $retries_current;
					}
				}
				return $coroutine->return_value();
			};
			return $this->project_coroutine_parameters(new FetchingCoroutine($retryable()));
		}

		/**
		 * Produces a derivedcoroutine that attempts HTTP downgrade on fetch failure.
		 * Consumes current coroutine.
		 * @param bool $did_downgrade Set to true if a downgrade to HTTP has taken place.
		 * @return FetchingCoroutine<TReturn> New FetchingCoroutine instance.
		 */
		public function downgradeable(mixed &$did_downgrade = NULL): FetchingCoroutine {
			$this->consume();
			$coroutine = $this;
			$has_downgrade_ref = func_num_args() >= 1;
			if ($has_downgrade_ref) $did_downgrade = false;
			$downgradeable = function () use ($coroutine, &$did_downgrade, $has_downgrade_ref) {
				while ($coroutine->valid()) {
					$id = $coroutine->current_key();
					$handle = $coroutine->current_request();
					$handle_downgraded = curl_handle_downgrade($handle);
					// Try HTTPS first
					if ($handle_downgraded) {
						// Skip to next handle on success
						if ($coroutine->send(yield $id => $handle)) {
							continue;
						}

						if ($has_downgrade_ref) $did_downgrade = true;
						$handle = $handle_downgraded;
					}

					// Use HTTP
					$coroutine->send(yield $id => $handle);
				}
				return $coroutine->return_value();
			};
			return $this->project_coroutine_parameters(new FetchingCoroutine($downgradeable()));
		}

		/**
		 * Assign non-generator parameters to given FetchingCoroutine.
		 */
		private function project_coroutine_parameters(FetchingCoroutine $coroutine): FetchingCoroutine {
			return $coroutine->set_response_filter($this->response_filter);
		}

		private function is_valid_response(CurlHandle $handle) {
			$response_filter = $this->response_filter;
			return $response_filter($handle);
		}

		/**
		 * Get the key yielded with the latest cURL handle by the coroutine, if applicable.
		 */
		public function current_key(): string {
			return $this->generator->key();
		}

		/**
		 * Get the cURL handle yielded by the coroutine, if applicable.
		 */
		public function current_request(): CurlHandle|null {
			return $this->generator->current();
		}

		private function valid(): bool {
			return $this->generator->valid();
		}

		/**
		 * Invoke the coroutine and yield all resulting requests. Consumes coroutine.
		 * @return Generator<string,CurlHandle,CurlHandle|false,TReturn>
		 */
		public function run() {
			$this->consume();
			// passthrough
			return yield from $this->generator;
		}

		/**
		 * Get the return value of the coroutine once finished.
		 * @return TReturn
		 */
		public function return_value(): mixed {
			return $this->generator->getReturn();
		}

		/**
		 * Step coroutine with network result until next yield point.
		 * Coroutine must not have been consumed by any transformations.
		 * @param CurlHandle|false $response_handle
		 * cURL handle containing fetch result or false in case of failure.
		 * @return bool True if response was accepted by coroutine, false otherwise.
		 */
		public function advance(CurlHandle | false $response_handle): bool {
			$this->assert_not_consumed();
			return $this->send($response_handle);
		}

		private function send(CurlHandle|false $handle): bool {
			if ($handle && $this->is_valid_response($handle)) {
				$this->generator->send($handle);
				return true;
			} else {
				$this->generator->send(false);
				return false;
			}
		}
	}

	/**
	 * Runs multiple coroutines with network yielding
	 */
	class FetchingCoroutineRunner {
		/**
		 * Collection of enroled transfers.
		 */
		private CurlMultiHandle $transfers;

		/**
		 * Coroutines executed by runner.
		 * @var FetchingCoroutine[] $coroutines
		 */
		private array $coroutines;

		/**
		 * Create a new FetchingCoroutineRunner instance on the given coroutines.
		 * @param FetchingCoroutine[] $coroutines Coroutines to run.
		 */
		public function __construct(array $coroutines = []) {
			$this->coroutines = $coroutines;

			$this->initialize_coroutines();
		}

		/**
		 * Launches all coroutines simultaneously.
		 * @return int CURLM_OK, or another curl_multi status in case of failure.
		 */
		public function run_all(): int {
			do {
				$curlm_status = curl_multi_exec($this->transfers, $curlm_active_transfer);
				if ($curlm_active_transfer) {
					// Block 1 second for active transfers
					curl_multi_select($this->transfers, timeout: 1.0);
				}
				$activity = $this->process_curl_activity();
			} while (($activity || $curlm_active_transfer) && $curlm_status == CURLM_OK);

			return $curlm_status;
		}

		/**
		 * Enrol initial transfers from all coroutines.
		 */
		private function initialize_coroutines() {
			$this->transfers = curl_multi_init();

			foreach ($this->coroutines as $id => $coroutine) {
				$this->poll_coroutine_for_transfer($id);
			}
		}

		/**
		 * Enrol latest transfer from coroutine with given id.
		 */
		private function poll_coroutine_for_transfer(int $id) {
			$coroutine = $this->coroutines[$id];
			$handle = $coroutine->current_request();
			if (!$handle) return;
			curl_setopt($handle, CURLOPT_PRIVATE, $id);
			curl_multi_add_handle($this->transfers, $handle);
		}

		/**
		 * Respond to new activity on enroled transfers.
		 */
		private function process_curl_activity() {
			$activity = 0;
			while (false !== ($info = curl_multi_info_read($this->transfers))) {
				if ($info['msg'] != CURLMSG_DONE) continue;
				$activity = 1;
				/**
				 * @var CurlHandle $handle
				 */
				$handle = $info['handle'];
				curl_multi_remove_handle($this->transfers, $handle);
				$coroutine_id = curl_getinfo($handle, CURLINFO_PRIVATE);
				if (!isset($this->coroutines[$coroutine_id])) {
					throw new Error("Invalid coroutine ID: " + $coroutine_id);
				}
				$this->coroutines[$coroutine_id]->advance($handle);
				$this->poll_coroutine_for_transfer($coroutine_id);
			}

			return $activity;
		}
	}

?>
