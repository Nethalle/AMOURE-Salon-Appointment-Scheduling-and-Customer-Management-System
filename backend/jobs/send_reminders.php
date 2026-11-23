<?php
// Run by Task Scheduler / cron. Command line only.
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Forbidden'); }