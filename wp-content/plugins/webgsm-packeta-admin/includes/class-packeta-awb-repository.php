<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Awb_Repository {

    public static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'webgsm_packeta_awb';
    }

    public static function install(): void {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            packet_id varchar(20) NOT NULL,
            barcode varchar(64) NOT NULL DEFAULT '',
            barcode_text varchar(64) NOT NULL DEFAULT '',
            order_ref varchar(120) NOT NULL DEFAULT '',
            carrier_name varchar(120) NOT NULL DEFAULT '',
            recipient_name varchar(200) NOT NULL DEFAULT '',
            recipient_phone varchar(40) NOT NULL DEFAULT '',
            flow_type varchar(20) NOT NULL DEFAULT '',
            courier_number varchar(80) NOT NULL DEFAULT '',
            shipment_id varchar(40) NOT NULL DEFAULT '',
            status_code int(11) NOT NULL DEFAULT 0,
            status_code_text varchar(64) NOT NULL DEFAULT '',
            status_text varchar(500) NOT NULL DEFAULT '',
            status_datetime datetime NULL,
            progress_step tinyint(3) NOT NULL DEFAULT 0,
            progress_percent tinyint(3) NOT NULL DEFAULT 0,
            is_final tinyint(1) NOT NULL DEFAULT 0,
            is_problem tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY packet_id (packet_id),
            KEY is_final (is_final),
            KEY updated_at (updated_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function upsert(array $data): int {
        global $wpdb;
        $table = self::table_name();
        $now = current_time('mysql');
        $packet_id = isset($data['packet_id']) ? (string) $data['packet_id'] : '';
        if ($packet_id === '') {
            return 0;
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `{$table}` WHERE packet_id = %s", $packet_id)
        );

        $row = [
            'packet_id' => $packet_id,
            'barcode' => isset($data['barcode']) ? (string) $data['barcode'] : '',
            'barcode_text' => isset($data['barcode_text']) ? (string) $data['barcode_text'] : '',
            'order_ref' => isset($data['order_ref']) ? (string) $data['order_ref'] : '',
            'carrier_name' => isset($data['carrier_name']) ? (string) $data['carrier_name'] : '',
            'recipient_name' => isset($data['recipient_name']) ? (string) $data['recipient_name'] : '',
            'recipient_phone' => isset($data['recipient_phone']) ? (string) $data['recipient_phone'] : '',
            'flow_type' => isset($data['flow_type']) ? (string) $data['flow_type'] : '',
            'courier_number' => isset($data['courier_number']) ? (string) $data['courier_number'] : '',
            'shipment_id' => isset($data['shipment_id']) ? (string) $data['shipment_id'] : '',
            'status_code' => isset($data['status_code']) ? (int) $data['status_code'] : 0,
            'status_code_text' => isset($data['status_code_text']) ? (string) $data['status_code_text'] : '',
            'status_text' => isset($data['status_text']) ? (string) $data['status_text'] : '',
            'status_datetime' => isset($data['status_datetime']) ? (string) $data['status_datetime'] : null,
            'progress_step' => isset($data['progress_step']) ? (int) $data['progress_step'] : 0,
            'progress_percent' => isset($data['progress_percent']) ? (int) $data['progress_percent'] : 0,
            'is_final' => !empty($data['is_final']) ? 1 : 0,
            'is_problem' => !empty($data['is_problem']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($existing) {
            $update = $row;
            foreach ($update as $key => $value) {
                if ($key === 'packet_id' || $key === 'updated_at') {
                    continue;
                }
                if (!array_key_exists($key, $data)) {
                    unset($update[$key]);
                    continue;
                }
                if (is_string($value) && $value === '' && $key !== 'status_text' && $key !== 'status_code_text') {
                    unset($update[$key]);
                }
            }
            $wpdb->update($table, $update, ['id' => (int) $existing]);

            return (int) $existing;
        }

        $row['created_at'] = $now;
        $wpdb->insert($table, $row);

        return (int) $wpdb->insert_id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function list_recent(int $limit = 100): array {
        global $wpdb;
        $table = self::table_name();
        $limit = max(1, min(500, $limit));

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT %d", $limit),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function list_active_for_sync(int $limit = 50): array {
        global $wpdb;
        $table = self::table_name();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE is_final = 0 ORDER BY updated_at ASC LIMIT %d",
                max(1, min(200, $limit))
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public static function get_by_packet_id(string $packet_id): ?array {
        global $wpdb;
        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE packet_id = %s", $packet_id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public static function update_status(string $packet_id, array $status_fields): void {
        $status_fields['packet_id'] = $packet_id;
        self::upsert($status_fields);
    }
}
