<?php
/**
 * EnjinMel SMTP Log Viewer
 *
 * Displays email logs with search, filtering, and pagination.
 *
 * @package EnjinMel_SMTP
 */

/**
 * EnjinMel SMTP Log Viewer Class
 */
class EnjinMel_SMTP_Log_Viewer
{

    /**
     * Initialize the log viewer.
     */
    public static function init()
    {
        add_action('admin_menu', array( __CLASS__, 'register_page' ));
        add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ));
        add_action('wp_ajax_enjinmel_smtp_delete_logs', array( __CLASS__, 'ajax_delete_logs' ));
        add_action('wp_ajax_enjinmel_smtp_export_logs', array( __CLASS__, 'ajax_export_logs' ));
        add_action('wp_ajax_enjinmel_smtp_clear_all_logs', array( __CLASS__, 'ajax_clear_all_logs' ));
    }

    /**
     * Register the log viewer admin page.
     */
    public static function register_page()
    {
        add_submenu_page(
            enjinmel_smtp_settings_group(),
            __('Email Logs', 'enjinmel-smtp'),
            __('Email Logs', 'enjinmel-smtp'),
            'manage_options',
            'enjinmel-smtp-logs',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Enqueue CSS and JS for the log viewer page.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( $hook )
    {
        if ('enjinmel-smtp_page_enjinmel-smtp-logs' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'enjinmel-smtp-log-viewer',
            plugins_url('assets/css/log-viewer.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'enjinmel-smtp-log-viewer',
            plugins_url('assets/js/log-viewer.js', dirname(__FILE__)),
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'enjinmel-smtp-log-viewer',
            'enjinmelSmtpLogViewer',
            array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('enjinmel_smtp_log_viewer'),
            'strings' => array(
            'confirmDelete'       => __('Are you sure you want to delete the selected logs?', 'enjinmel-smtp'),
            'confirmDeleteAll'    => __('Are you sure you want to delete ALL logs matching the current filter?', 'enjinmel-smtp'),
            'confirmClearAll'     => __('Are you sure you want to delete ALL email logs? This action cannot be undone!', 'enjinmel-smtp'),
            'deleteSuccess'       => __('Logs deleted successfully.', 'enjinmel-smtp'),
            'deleteFailed'        => __('Failed to delete logs.', 'enjinmel-smtp'),
            'clearAllSuccess'     => __('All logs cleared successfully.', 'enjinmel-smtp'),
            'clearAllFailed'      => __('Failed to clear all logs.', 'enjinmel-smtp'),
            'exportSuccess'       => __('Logs exported successfully.', 'enjinmel-smtp'),
            'exportFailed'        => __('Failed to export logs.', 'enjinmel-smtp'),
            'noLogsSelected'      => __('Please select at least one log to delete.', 'enjinmel-smtp'),
            ),
            )
        );
    }

    /**
     * Render the log viewer page.
     */
    public static function render_page()
    {
        if (! current_user_can('manage_options') ) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'enjinmel-smtp'));
        }

        $logs_data = self::get_logs();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Email Logs', 'enjinmel-smtp'); ?></h1>
            
        <?php self::render_filters(); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php echo esc_html__('Bulk Actions', 'enjinmel-smtp'); ?></option>
                        <option value="delete"><?php echo esc_html__('Delete', 'enjinmel-smtp'); ?></option>
                    </select>
                    <button type="button" id="doaction" class="button action"><?php echo esc_html__('Apply', 'enjinmel-smtp'); ?></button>
                </div>
                <div class="alignleft actions">
                    <button type="button" id="export-csv" class="button"><?php echo esc_html__('Export to CSV', 'enjinmel-smtp'); ?></button>
                    <button type="button" id="clear-all-logs" class="button button-secondary" style="margin-left: 5px;"><?php echo esc_html__('Clear All Logs', 'enjinmel-smtp'); ?></button>
                </div>
        <?php self::render_pagination($logs_data['total'], $logs_data['per_page'], $logs_data['current_page']); ?>
            </div>

        <?php self::render_logs_table($logs_data['logs']); ?>

            <div class="tablenav bottom">
        <?php self::render_pagination($logs_data['total'], $logs_data['per_page'], $logs_data['current_page']); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the filter form.
     */
    private static function render_filters()
    {
        $search      = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status      = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $date_from   = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to     = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
        $per_page    = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        ?>
        <div class="enjinmel-log-filters">
            <form method="get">
                <input type="hidden" name="page" value="enjinmel-smtp-logs">
                
                <div class="filter-row">
                    <input type="search" 
                           id="log-search" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php echo esc_attr__('Search by email or subject...', 'enjinmel-smtp'); ?>">
                    
                    <select name="status" id="log-status">
                        <option value=""><?php echo esc_html__('All Statuses', 'enjinmel-smtp'); ?></option>
                        <option value="sent" <?php selected($status, 'sent'); ?>><?php echo esc_html__('Sent', 'enjinmel-smtp'); ?></option>
                        <option value="failed" <?php selected($status, 'failed'); ?>><?php echo esc_html__('Failed', 'enjinmel-smtp'); ?></option>
                    </select>
                    
                    <label for="date-from"><?php echo esc_html__('From:', 'enjinmel-smtp'); ?></label>
                    <input type="date" 
                           id="date-from" 
                           name="date_from" 
                           value="<?php echo esc_attr($date_from); ?>">
                    
                    <label for="date-to"><?php echo esc_html__('To:', 'enjinmel-smtp'); ?></label>
                    <input type="date" 
                           id="date-to" 
                           name="date_to" 
                           value="<?php echo esc_attr($date_to); ?>">
                    
                    <label for="per-page"><?php echo esc_html__('Per page:', 'enjinmel-smtp'); ?></label>
                    <select name="per_page" id="per-page">
                        <option value="10" <?php selected($per_page, 10); ?>>10</option>
                        <option value="20" <?php selected($per_page, 20); ?>>20</option>
                        <option value="50" <?php selected($per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($per_page, 100); ?>>100</option>
                    </select>
                    
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Filter', 'enjinmel-smtp'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=enjinmel-smtp-logs')); ?>" class="button"><?php echo esc_html__('Reset', 'enjinmel-smtp'); ?></a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render the logs table.
     *
     * @param array $logs Array of log entries.
     */
    private static function render_logs_table( $logs )
    {
        ?>
        <table class="wp-list-table widefat fixed striped enjinmel-logs-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column column-timestamp"><?php echo esc_html__('Timestamp', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-to"><?php echo esc_html__('To', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-subject"><?php echo esc_html__('Subject', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-status"><?php echo esc_html__('Status', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-error"><?php echo esc_html__('Error Message', 'enjinmel-smtp'); ?></th>
                </tr>
            </thead>
            <tbody>
        <?php if (empty($logs) ) : ?>
                    <tr class="no-items">
                        <td colspan="6"><?php echo esc_html__('No logs found.', 'enjinmel-smtp'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr data-log-id="<?php echo esc_attr($log->id); ?>">
                            <th class="check-column">
                                <input type="checkbox" name="log[]" value="<?php echo esc_attr($log->id); ?>">
                            </th>
                            <td class="column-timestamp">
                        <?php echo esc_html($log->timestamp); ?>
                            </td>
                            <td class="column-to">
                        <?php echo esc_html($log->to_email); ?>
                            </td>
                            <td class="column-subject">
                        <?php echo esc_html($log->subject); ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge status-<?php echo esc_attr($log->status); ?>">
                        <?php echo esc_html(ucfirst($log->status)); ?>
                                </span>
                            </td>
                            <td class="column-error">
                        <?php if (! empty($log->error_message) ) : ?>
                                    <span class="error-message" title="<?php echo esc_attr($log->error_message); ?>">
                            <?php echo esc_html(self::truncate_text($log->error_message, 100)); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="error-message-empty">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th class="manage-column column-timestamp"><?php echo esc_html__('Timestamp', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-to"><?php echo esc_html__('To', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-subject"><?php echo esc_html__('Subject', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-status"><?php echo esc_html__('Status', 'enjinmel-smtp'); ?></th>
                    <th class="manage-column column-error"><?php echo esc_html__('Error Message', 'enjinmel-smtp'); ?></th>
                </tr>
            </tfoot>
        </table>
        <?php
    }

    /**
     * Render pagination controls.
     *
     * @param int $total        Total number of logs.
     * @param int $per_page     Logs per page.
     * @param int $current_page Current page number.
     */
    private static function render_pagination( $total, $per_page, $current_page )
    {
        $total_pages = ceil($total / $per_page);

        if ($total_pages <= 1 ) {
            return;
        }

        $current_url = remove_query_arg('paged');
        ?>
        <div class="tablenav-pages">
            <span class="displaying-num">
        <?php
        printf(
        /* translators: %s: Number of items. */
            esc_html(_n('%s item', '%s items', $total, 'enjinmel-smtp')),
            esc_html(number_format_i18n($total))
        );
        ?>
            </span>
            <span class="pagination-links">
        <?php if ($current_page > 1 ) : ?>
                    <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1, $current_url)); ?>">
                        <span class="screen-reader-text"><?php echo esc_html__('First page', 'enjinmel-smtp'); ?></span>
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                    <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', max(1, $current_page - 1), $current_url)); ?>">
                        <span class="screen-reader-text"><?php echo esc_html__('Previous page', 'enjinmel-smtp'); ?></span>
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                <?php else : ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
                <?php endif; ?>

                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text"><?php echo esc_html__('Current Page', 'enjinmel-smtp'); ?></label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo esc_attr($current_page); ?>" size="<?php echo esc_attr(strlen((string) $total_pages)); ?>" aria-describedby="table-paging">
                    <span class="tablenav-paging-text"> <?php echo esc_html__('of', 'enjinmel-smtp'); ?> <span class="total-pages"><?php echo esc_html(number_format_i18n($total_pages)); ?></span></span>
                </span>

        <?php if ($current_page < $total_pages ) : ?>
                    <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', min($total_pages, $current_page + 1), $current_url)); ?>">
                        <span class="screen-reader-text"><?php echo esc_html__('Next page', 'enjinmel-smtp'); ?></span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                    <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages, $current_url)); ?>">
                        <span class="screen-reader-text"><?php echo esc_html__('Last page', 'enjinmel-smtp'); ?></span>
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                <?php else : ?>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
                <?php endif; ?>
            </span>
        </div>
        <?php
    }

    /**
     * Get logs with filtering and pagination.
     *
     * @return array {
     * @type   array $logs         Array of log objects.
     * @type   int   $total        Total number of logs matching filter.
     * @type   int   $per_page     Items per page.
     * @type   int   $current_page Current page number.
     * }
     */
    private static function get_logs()
    {
        global $wpdb;

        $table        = enjinmel_smtp_active_log_table();
        $search       = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status       = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $date_from    = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to      = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
        $per_page     = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page     = max(1, min(100, $per_page));
        $current_page = max(1, $current_page);

        $where_clauses = array( '1=1' );
        $prepare_args  = array();

        if (! empty($search) ) {
            $where_clauses[] = '(to_email LIKE %s OR subject LIKE %s)';
            $search_term     = '%' . $wpdb->esc_like($search) . '%';
            $prepare_args[]  = $search_term;
            $prepare_args[]  = $search_term;
        }

        if (! empty($status) && in_array($status, array( 'sent', 'failed' ), true) ) {
            $where_clauses[] = 'status = %s';
            $prepare_args[]  = $status;
        }

        if (! empty($date_from) ) {
            $where_clauses[] = 'timestamp >= %s';
            $prepare_args[]  = $date_from . ' 00:00:00';
        }

        if (! empty($date_to) ) {
            $where_clauses[] = 'timestamp <= %s';
            $prepare_args[]  = $date_to . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where_clauses);

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        if (! empty($prepare_args) ) {
            $count_sql = $wpdb->prepare($count_sql, $prepare_args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
        $total = (int) $wpdb->get_var($count_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

        $offset = ( $current_page - 1 ) * $per_page;

        $logs_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $args     = array_merge($prepare_args, array( $per_page, $offset ));
        $logs_sql = $wpdb->prepare($logs_sql, $args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        $logs = $wpdb->get_results($logs_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

        return array(
        'logs'         => $logs,
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        );
    }

    /**
     * AJAX handler for deleting logs.
     */
    public static function ajax_delete_logs()
    {
        check_ajax_referer('enjinmel_smtp_log_viewer', 'nonce');

        if (! current_user_can('manage_options') ) {
            wp_send_json_error(array( 'message' => __('Unauthorized.', 'enjinmel-smtp') ), 403);
        }

        $log_ids = isset($_POST['log_ids']) ? array_map('absint', (array) $_POST['log_ids']) : array();

        if (empty($log_ids) ) {
            wp_send_json_error(array( 'message' => __('No logs selected.', 'enjinmel-smtp') ), 400);
        }

        global $wpdb;
        $table        = enjinmel_smtp_active_log_table();
        $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
        $deleted      = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", $log_ids)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

        if (false === $deleted ) {
            wp_send_json_error(array( 'message' => __('Failed to delete logs.', 'enjinmel-smtp') ), 500);
        }

        wp_send_json_success(array( 'message' => __('Logs deleted successfully.', 'enjinmel-smtp'), 'deleted' => $deleted ));
    }

    /**
     * AJAX handler for exporting logs to CSV.
     */
    public static function ajax_export_logs()
    {
        check_ajax_referer('enjinmel_smtp_log_viewer', 'nonce');

        if (! current_user_can('manage_options') ) {
            wp_die(esc_html__('Unauthorized.', 'enjinmel-smtp'));
        }

        global $wpdb;
        $table = enjinmel_smtp_active_log_table();

        $search    = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status    = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to   = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

        $where_clauses = array( '1=1' );
        $prepare_args  = array();

        if (! empty($search) ) {
            $where_clauses[] = '(to_email LIKE %s OR subject LIKE %s)';
            $search_term     = '%' . $wpdb->esc_like($search) . '%';
            $prepare_args[]  = $search_term;
            $prepare_args[]  = $search_term;
        }

        if (! empty($status) && in_array($status, array( 'sent', 'failed' ), true) ) {
            $where_clauses[] = 'status = %s';
            $prepare_args[]  = $status;
        }

        if (! empty($date_from) ) {
            $where_clauses[] = 'timestamp >= %s';
            $prepare_args[]  = $date_from . ' 00:00:00';
        }

        if (! empty($date_to) ) {
            $where_clauses[] = 'timestamp <= %s';
            $prepare_args[]  = $date_to . ' 23:59:59';
        }

        $where_sql = implode(' AND ', $where_clauses);

        $logs_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY timestamp DESC";
        if (! empty($prepare_args) ) {
            $logs_sql = $wpdb->prepare($logs_sql, $prepare_args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        $logs = $wpdb->get_results($logs_sql, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=enjinmel-smtp-logs-' . gmdate('Y-m-d-H-i-s') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if (! empty($logs) ) {
            fputcsv($output, array_keys($logs[0]));
            foreach ( $logs as $log ) {
                fputcsv($output, $log);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX handler for clearing all logs.
     */
    public static function ajax_clear_all_logs()
    {
        check_ajax_referer('enjinmel_smtp_log_viewer', 'nonce');

        if (! current_user_can('manage_options') ) {
            wp_send_json_error(array( 'message' => __('Unauthorized.', 'enjinmel-smtp') ), 403);
        }

        global $wpdb;
        $table = enjinmel_smtp_active_log_table();

        $deleted = $wpdb->query("TRUNCATE TABLE {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

        if (false === $deleted ) {
            wp_send_json_error(array( 'message' => __('Failed to clear all logs.', 'enjinmel-smtp') ), 500);
        }

        wp_send_json_success(array( 'message' => __('All logs cleared successfully.', 'enjinmel-smtp') ));
    }

    /**
     * Truncate text to a specified length.
     *
     * @param  string $text   Text to truncate.
     * @param  int    $length Maximum length.
     * @return string
     */
    private static function truncate_text( $text, $length )
    {
        if (strlen($text) <= $length ) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}
