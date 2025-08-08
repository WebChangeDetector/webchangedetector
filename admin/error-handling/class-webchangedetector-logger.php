<?php

/**
 * WebChangeDetector Logger
 *
 * Simple file-based logging system with daily rotation and automatic cleanup.
 *
 * @package    WebChangeDetector
 * @subpackage WebChangeDetector/admin/error-handling
 */

namespace WebChangeDetector;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WebChangeDetector Logger Class
 *
 * Provides simple file-based logging with daily rotation.
 */
class WebChangeDetector_Logger
{

    /**
     * Log directory path.
     *
     * @var string
     */
    private $log_dir;

    /**
     * Whether debug logging is enabled.
     *
     * @var bool
     */
    private $debug_enabled;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set log directory to plugin root/logs
        $this->log_dir = plugin_dir_path(dirname(dirname(__FILE__))) . 'logs';
        $this->debug_enabled = get_option(WCD_WP_OPTION_KEY_DEBUG_LOGGING, false);

        // Ensure log directory exists
        $this->ensure_log_directory();

        // Schedule cleanup if not already scheduled
        if (! wp_next_scheduled('wcd_cleanup_old_logs')) {
            wp_schedule_event(time(), 'daily', 'wcd_cleanup_old_logs');
        }

        add_action('wcd_cleanup_old_logs', array($this, 'cleanup_old_logs'));
    }

    /**
     * Main logging function.
     *
     * @param string $message  Log message.
     * @param string $context  Log context/category (optional).
     * @param string $severity Log severity for future use (optional).
     * @return bool True on success, false on failure.
     */
    public function log($message, $context = 'general', $severity = 'info')
    {
        // Check if debug logging is enabled
        if (! $this->debug_enabled) {
            // Always log errors regardless of debug setting
            if (! in_array($severity, array('error', 'critical'), true)) {
                return false;
            }
        }

        // Get current date for daily log file
        $date = current_time('Y-m-d');
        $log_file = $this->log_dir . '/wcd-' . $date . '.log';

        // Format timestamp
        $timestamp = current_time('Y-m-d H:i:s');

        // Format log entry
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s",
            $timestamp,
            strtoupper($severity),
            $context,
            $message
        );

        // Write to file
        $result = error_log($log_entry . PHP_EOL, 3, $log_file);

        return $result !== false;
    }

    /**
     * Convenience method for error logging (always logs regardless of debug setting).
     *
     * @param string $message Log message.
     * @param string $context Log context/category.
     * @return bool True on success, false on failure.
     */
    public function error($message, $context = 'general')
    {
        return $this->log($message, $context, 'error');
    }

    /**
     * Convenience method for debug logging.
     *
     * @param string $message Log message.
     * @param string $context Log context/category.
     * @return bool True on success, false on failure.
     */
    public function debug($message, $context = 'general')
    {
        return $this->log($message, $context, 'debug');
    }

    /**
     * Convenience method for info logging.
     *
     * @param string $message Log message.
     * @param string $context Log context/category.
     * @return bool True on success, false on failure.
     */
    public function info($message, $context = 'general')
    {
        return $this->log($message, $context, 'info');
    }

    /**
     * Convenience method for warning logging.
     *
     * @param string $message Log message.
     * @param string $context Log context/category.
     * @return bool True on success, false on failure.
     */
    public function warning($message, $context = 'general')
    {
        return $this->log($message, $context, 'warning');
    }

    /**
     * Convenience method for critical logging.
     *
     * @param string $message Log message.
     * @param string $context Log context/category.
     * @return bool True on success, false on failure.
     */
    public function critical($message, $context = 'general')
    {
        return $this->log($message, $context, 'critical');
    }

    /**
     * Ensure log directory exists with proper security.
     */
    private function ensure_log_directory()
    {
        if (! is_dir($this->log_dir)) {
            wp_mkdir_p($this->log_dir);

            // Create .htaccess to prevent direct access
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->log_dir . '/.htaccess', $htaccess_content);

            // Create index.php for additional security
            file_put_contents($this->log_dir . '/index.php', '<?php // Silence is golden.');
        }
    }

    /**
     * Clean up log files older than 14 days.
     */
    public function cleanup_old_logs()
    {
        $files = glob($this->log_dir . '/wcd-*.log');

        if (! is_array($files)) {
            return;
        }

        $cutoff_time = time() - (14 * DAY_IN_SECONDS);

        foreach ($files as $file) {
            // Extract date from filename (wcd-YYYY-MM-DD.log)
            if (preg_match('/wcd-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $file_date = strtotime($matches[1]);
                if ($file_date && $file_date < $cutoff_time) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Get the current debug logging status.
     *
     * @return bool Whether debug logging is enabled.
     */
    public function is_debug_enabled()
    {
        return $this->debug_enabled;
    }

    /**
     * Update debug logging status.
     *
     * @param bool $enabled Whether to enable debug logging.
     */
    public function set_debug_enabled($enabled)
    {
        $this->debug_enabled = (bool) $enabled;
        update_option(WCD_WP_OPTION_KEY_DEBUG_LOGGING, $this->debug_enabled);
    }

    /**
     * Get available log files.
     *
     * @return array Array of log file info with date, size, and path.
     */
    public function get_available_log_files()
    {
        $files = glob($this->log_dir . '/wcd-*.log');
        $log_files = array();

        if (! is_array($files)) {
            return $log_files;
        }

        foreach ($files as $file) {
            if (preg_match('/wcd-(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                $date = $matches[1];
                $size = file_exists($file) ? filesize($file) : 0;

                $log_files[] = array(
                    'date' => $date,
                    'filename' => basename($file),
                    'size' => $size,
                    'size_formatted' => $this->format_file_size($size),
                    'path' => $file,
                );
            }
        }

        // Sort by date (newest first)
        usort($log_files, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $log_files;
    }

    /**
     * Download a specific log file.
     *
     * @param string $filename The log filename to download.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function download_log_file($filename)
    {
        // Validate filename format
        if (! preg_match('/^wcd-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
            return new \WP_Error('invalid_filename', 'Invalid log filename format.');
        }

        $file_path = $this->log_dir . '/' . $filename;

        // Check if file exists
        if (! file_exists($file_path)) {
            return new \WP_Error('file_not_found', 'Log file not found.');
        }

        // Check if file is readable
        if (! is_readable($file_path)) {
            return new \WP_Error('file_not_readable', 'Log file is not readable.');
        }

        // Check if headers have already been sent
        if (headers_sent($file, $line)) {
            // If headers already sent, use JavaScript redirect to download
            $content = file_get_contents($file_path);
            $base64 = base64_encode($content);
            $mime = 'text/plain';
            
            echo '<script>
                var link = document.createElement("a");
                link.download = "' . esc_js($filename) . '";
                link.href = "data:' . $mime . ';base64,' . $base64 . '";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.history.back();
            </script>';
            echo '<p>If the download does not start automatically, <a href="data:' . $mime . ';base64,' . $base64 . '" download="' . esc_attr($filename) . '">click here</a>.</p>';
            exit;
        }

        // Set headers for download
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        // Output file content
        readfile($file_path);
        exit;
    }

    /**
     * Format file size in human readable format.
     *
     * @param int $size File size in bytes.
     * @return string Formatted file size.
     */
    private function format_file_size($size)
    {
        if ($size == 0) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB');
        $factor = floor(log($size, 1024));

        return sprintf('%.1f %s', $size / pow(1024, $factor), $units[$factor]);
    }
}
