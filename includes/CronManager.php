<?php
/**
 * Class Cron
 * Handles custom cron job registration and execution.
 * @package TwoSecond
 * @author TwoSecond Team
 */


namespace TwoSecond;

require_once(__DIR__ . '/TwoSecond.php');

class CronManager extends TwoSecond
{
    public $cronJobs = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->cronJobs = $this->twosecond_get_option('twosecond_cron_jobs', 1);
        $this->twosecond_remove_cron_job('twosecond_optimize_with_ai_cron');
        $this->twosecond_add_cron_job('twosecond_delete_core_web_vitals_log', 'twosecond_delete_core_web_vitals_log', 86400);
        $this->twosecond_add_cron_job('twosecond_delete_settings_change_log', 'twosecond_delete_settings_change_log', 86400);
        $this->twosecond_add_cron_job('twosecond_check_license_key ', 'twosecond_check_license_key ', 86400);
        $this->twosecond_add_cron_job('twosecond_cache_size_callback', 'twosecond_cache_size_callback', 3600);
    }

    /**
     * Adds a cron job if it doesn't already exist.
     *
     * @param string $jobName
     * @param string $functionName
     * @param int $interval Duration in seconds between executions
     */
    public function twosecond_add_cron_job(string $jobName, string $functionName, int $interval): void
    {
        $cronJobs = $this->cronJobs;
        if (!isset($cronJobs[$jobName]) || $cronJobs[$jobName]['function_name'] != $functionName || $cronJobs[$jobName]['duration'] != $interval) {
            $cronJobs[$jobName] = [
                'function_name' => $functionName,
                'duration' => $interval,
                'last_run' => 0
            ];
            $this->cronJobs = $cronJobs;
            $this->twosecond_update_option('twosecond_cron_jobs', $this->cronJobs, 0, 1);
        }
    }

    /**
     * Removes a cron job by name.
     *
     * @param string $jobName
     */
    public function twosecond_remove_cron_job(string $jobName): void
    {
        $cronJobs = $this->cronJobs;
        if (isset($cronJobs[$jobName])) {
            unset($cronJobs[$jobName]);
            $this->cronJobs = $cronJobs;
            $this->twosecond_update_option('twosecond_cron_jobs', $cronJobs, 0, 1);
        }
    }

    /**
     * Runs due cron jobs.
     */
    public function twosecond_run_cron_jobs(): void
    {
        $cronJobs = $this->cronJobs;
        $currentTime = time();
        foreach ($cronJobs as $jobName => &$job) {
            if (($currentTime - $job['last_run']) >= $job['duration']) {
                $function = $job['function_name'] ?? null;
                if ($function && method_exists($this, $function)) {
                    try {
                        $this->$function();
                        $job['last_run'] = $currentTime;
                    } catch (\Throwable $e) {}
                }
            }
        }
        unset($job);
        $this->cronJobs = $cronJobs;
        $this->twosecond_update_option('twosecond_cron_jobs', $cronJobs, 0, 1);
    }
}